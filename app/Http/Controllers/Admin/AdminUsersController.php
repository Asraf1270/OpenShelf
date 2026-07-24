<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Services\MailerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class AdminUsersController extends AdminController
{
    public function __construct(private MailerService $mailerService)
    {
    }

    public function index(Request $request): View|RedirectResponse
    {
        $admin = $this->requireAdmin($request);

        if ($admin instanceof RedirectResponse) {
            return $admin;
        }

        if ($request->isMethod('post')) {
            return $this->handleAction($request, $admin->id);
        }

        return view('admin.users', $this->viewData($request, $admin));
    }

    private function handleAction(Request $request, string $adminId): RedirectResponse
    {
        $action = $request->input('action');

        if ($action === 'approve') {
            $validated = $request->validate([
                'user_id' => ['required', 'string', 'exists:users,id'],
            ]);

            $user = User::query()->findOrFail($validated['user_id']);
            $this->markUserStatus($user, $adminId, true);
            $this->sendApprovalEmail($user);

            return back()->with('success', 'User approved successfully.');
        }

        if ($action === 'reject') {
            $validated = $request->validate([
                'user_id' => ['required', 'string', 'exists:users,id'],
                'rejection_reason' => ['required', 'string'],
            ]);

            $user = User::query()->findOrFail($validated['user_id']);
            $reason = trim($validated['rejection_reason']);
            $this->markUserStatus($user, $adminId, false, $reason);
            $this->sendRejectionEmail($user, $reason);

            return back()->with('success', 'User rejected successfully.');
        }

        if ($action === 'delete') {
            $validated = $request->validate([
                'user_id' => ['required', 'string', 'exists:users,id'],
            ]);

            $user = User::query()->findOrFail($validated['user_id']);
            $this->archiveProfileFile($user->id);
            $user->delete();

            return back()->with('success', 'User deleted successfully.');
        }

        if ($action === 'bulk_approve') {
            $validated = $request->validate([
                'user_ids' => ['required', 'array'],
                'user_ids.*' => ['string', 'exists:users,id'],
            ]);

            $users = User::query()->whereIn('id', $validated['user_ids'])->get();

            foreach ($users as $user) {
                $this->markUserStatus($user, $adminId, true);
                $this->sendApprovalEmail($user);
            }

            return back()->with('success', 'Approved ' . $users->count() . ' users successfully.');
        }

        if ($action === 'bulk_reject') {
            $validated = $request->validate([
                'user_ids' => ['required', 'array'],
                'user_ids.*' => ['string', 'exists:users,id'],
                'bulk_rejection_reason' => ['required', 'string'],
            ]);

            $reason = trim($validated['bulk_rejection_reason']);
            $users = User::query()->whereIn('id', $validated['user_ids'])->get();

            foreach ($users as $user) {
                $this->markUserStatus($user, $adminId, false, $reason);
                $this->sendRejectionEmail($user, $reason);
            }

            return back()->with('success', 'Rejected ' . $users->count() . ' users successfully.');
        }

        if ($action === 'bulk_delete') {
            $validated = $request->validate([
                'user_ids' => ['required', 'array'],
                'user_ids.*' => ['string', 'exists:users,id'],
            ]);

            $users = User::query()->whereIn('id', $validated['user_ids'])->get();

            foreach ($users as $user) {
                $this->archiveProfileFile($user->id);
                $user->delete();
            }

            return back()->with('success', 'Deleted ' . $users->count() . ' users successfully.');
        }

        return back()->with('error', 'Unsupported action.');
    }

    private function viewData(Request $request, $admin): array
    {
        $status = $request->string('status')->toString() ?: 'all';
        $search = trim($request->string('search')->toString());

        $query = User::query();

        if ($status === 'pending') {
            $query->where('verified', false)->where('status', '!=', 'rejected');
        } elseif ($status === 'approved') {
            $query->where('verified', true)->where('status', 'active');
        } elseif ($status === 'rejected') {
            $query->where('status', 'rejected');
        }

        if ($search !== '') {
            $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%');
            });
        }

        /** @var LengthAwarePaginator $users */
        $users = $query
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        return [
            'admin' => $admin,
            'status' => $status,
            'search' => $search,
            'users' => $users,
            'totalUsersCount' => User::query()->count(),
            'pendingUsersCount' => User::query()->where('verified', false)->where('status', '!=', 'rejected')->count(),
            'approvedUsersCount' => User::query()->where('verified', true)->where('status', 'active')->count(),
            'rejectedUsersCount' => User::query()->where('status', 'rejected')->count(),
        ];
    }

    private function markUserStatus(User $user, string $adminId, bool $approved, string $reason = ''): void
    {
        $user->verified = $approved;
        $user->status = $approved ? 'active' : 'rejected';
        $user->rejection_reason = $approved ? null : $reason;
        $user->verified_by = $adminId;
        $user->verified_at = now();
        $user->updated_at = now();
        $user->save();

        $profilePath = base_path('users/' . $user->id . '.json');

        if (! File::exists($profilePath)) {
            return;
        }

        $profile = json_decode(File::get($profilePath), true) ?: [];
        $profile['account_info'] = $profile['account_info'] ?? [];
        $profile['account_info']['verified'] = $approved;
        $profile['account_info']['status'] = $approved ? 'active' : 'rejected';
        $profile['account_info']['verified_at'] = now()->toDateTimeString();
        $profile['account_info']['verified_by'] = $adminId;

        if (! $approved) {
            $profile['account_info']['rejection_reason'] = $reason;
        }

        File::put($profilePath, json_encode($profile, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function archiveProfileFile(string $userId): void
    {
        $profilePath = base_path('users/' . $userId . '.json');

        if (! File::exists($profilePath)) {
            return;
        }

        $archiveDir = base_path('data/archive/users');

        if (! File::exists($archiveDir)) {
            File::makeDirectory($archiveDir, 0755, true);
        }

        File::move($profilePath, $archiveDir . '/' . $userId . '_' . time() . '.json');
    }

    private function sendApprovalEmail(User $user): void
    {
        if (! $user->email) {
            return;
        }

        $this->mailerService->sendTemplate(
            $user->email,
            $user->name,
            'account_approved',
            [
                'subject' => 'Your OpenShelf Account Has Been Approved!',
                'user_name' => $user->name,
                'login_url' => route('login'),
                'base_url' => config('app.url'),
            ],
            $user->id,
        );
    }

    private function sendRejectionEmail(User $user, string $reason): void
    {
        if (! $user->email) {
            return;
        }

        $this->mailerService->sendTemplate(
            $user->email,
            $user->name,
            'account_rejected',
            [
                'subject' => 'Account Status Update - OpenShelf',
                'user_name' => $user->name,
                'rejection_reason' => $reason,
                'support_email' => 'support@duopenshelf.top',
                'base_url' => config('app.url'),
            ],
            $user->id,
        );
    }
}
