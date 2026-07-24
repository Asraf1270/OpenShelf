<?php

namespace App\Http\Controllers\Admin;

use App\Models\SupportUs;
use App\Services\AdminSupportUsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminSupportUsController extends AdminController
{
    public function __construct(private AdminSupportUsService $adminSupportUsService)
    {
    }

    public function index(Request $request): View|RedirectResponse
    {
        $admin = $this->requireAdmin($request);

        if ($admin instanceof RedirectResponse) {
            return $admin;
        }

        if ($request->isMethod('post')) {
            return $this->handleAction($request, $admin);
        }

        $status = $request->query('status', 'all');
        $search = trim($request->query('search', ''));

        return view('admin.support-us', [
            'admin' => $admin,
            'supportRequests' => $this->adminSupportUsService->list($status, $search),
            'status' => $status,
            'search' => $search,
        ]);
    }

    private function handleAction(Request $request, $admin): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:approve'],
            'support_id' => ['required', 'string', 'exists:support_us,id'],
        ]);

        if ($validated['action'] !== 'approve') {
            return back()->with('error', 'Unsupported action.');
        }

        $support = SupportUs::query()->findOrFail($validated['support_id']);

        if ($support->status !== 'pending') {
            return back()->with('error', 'Only pending requests can be approved.');
        }

        $this->adminSupportUsService->approve($support, $admin->id, $admin->name);

        return back()->with('success', 'Support request approved and transaction created successfully.');
    }
}
