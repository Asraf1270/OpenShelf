<?php

namespace App\Http\Controllers\Admin;

use App\Services\AdminReportsService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class AdminReportsController extends AdminController
{
    public function __construct(private AdminReportsService $adminReportsService)
    {
    }

    public function index(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        $admin = $this->requireAdmin($request);

        if ($admin instanceof \Illuminate\Http\RedirectResponse) {
            return $admin;
        }

        $summary = $this->adminReportsService->summaryStats();

        return view('admin.reports', [
            'admin' => $admin,
            'reportType' => $request->query('type', 'overview'),
            'summary' => $summary,
            'userGrowth' => $this->adminReportsService->monthlyStats('users', 'created_at'),
            'bookGrowth' => $this->adminReportsService->monthlyStats('books', 'created_at'),
            'topBooks' => $this->adminReportsService->topBooks(),
        ]);
    }

    public function export(Request $request): Response|\Illuminate\Http\RedirectResponse
    {
        $admin = $this->requireAdmin($request);

        if ($admin instanceof \Illuminate\Http\RedirectResponse) {
            return $admin;
        }

        $type = $request->query('type', 'users');

        if (! in_array($type, ['users', 'books', 'requests'], true)) {
            $type = 'users';
        }

        $csv = $this->adminReportsService->exportCsv($type);
        $filename = $type . '_export_' . now()->format('Y-m-d') . '.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
