<?php

namespace App\Http\Controllers\Admin;

use App\Models\Report;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminReportsManagementController extends AdminController
{
    private const TYPE_LABELS = [
        'bug' => 'Bug',
        'user' => 'User',
        'book' => 'Book',
        'suggestion' => 'Suggestion',
        'other' => 'Other',
    ];

    private const STATUS_LABELS = [
        'pending' => 'Pending',
        'in_progress' => 'In Progress',
        'resolved' => 'Resolved',
        'dismissed' => 'Dismissed',
    ];

    private const STATUS_COLORS = [
        'pending' => '#f59e0b',
        'in_progress' => '#3b82f6',
        'resolved' => '#10b981',
        'dismissed' => '#6b7280',
    ];

    public function index(Request $request): View|RedirectResponse
    {
        $admin = $this->requireAdmin($request);

        if ($admin instanceof RedirectResponse) {
            return $admin;
        }

        if ($request->isMethod('post')) {
            return $this->handleAction($request, $admin->name);
        }

        $statusFilter = $request->query('status', 'all');
        $typeFilter = $request->query('type', 'all');

        $query = Report::query()->orderByDesc('created_at');

        if ($statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        if ($typeFilter !== 'all') {
            $query->where('type', $typeFilter);
        }

        return view('admin.reports-management', [
            'admin' => $admin,
            'reports' => $query->get(),
            'statusFilter' => $statusFilter,
            'typeFilter' => $typeFilter,
            'stats' => [
                'total' => Report::query()->count(),
                'pending' => Report::query()->where('status', 'pending')->count(),
                'resolved' => Report::query()->where('status', 'resolved')->count(),
            ],
            'typeLabels' => self::TYPE_LABELS,
            'statusLabels' => self::STATUS_LABELS,
            'statusColors' => self::STATUS_COLORS,
            'message' => session('success'),
            'error' => session('error'),
        ]);
    }

    private function handleAction(Request $request, string $adminName): RedirectResponse
    {
        $action = $request->input('action');

        if ($action === 'update_status') {
            $validated = $request->validate([
                'report_id' => ['required', 'string', 'exists:reports,id'],
                'status' => ['required', 'string', 'in:pending,in_progress,resolved,dismissed'],
                'admin_notes' => ['nullable', 'string'],
            ]);

            $report = Report::query()->findOrFail($validated['report_id']);
            $status = $validated['status'];
            $adminNotes = trim($validated['admin_notes'] ?? '');

            $report->update([
                'status' => $status,
                'admin_notes' => $adminNotes,
                'resolved_by' => $status === 'resolved' ? $adminName : null,
                'resolved_at' => $status === 'resolved' ? now() : null,
                'updated_at' => now(),
            ]);

            return back()->with('success', 'Report status updated successfully.');
        }

        if ($action === 'delete') {
            $validated = $request->validate([
                'report_id' => ['required', 'string', 'exists:reports,id'],
            ]);

            Report::query()->where('id', $validated['report_id'])->delete();

            return back()->with('success', 'Report deleted successfully.');
        }

        return back()->with('error', 'Unknown action.');
    }
}
