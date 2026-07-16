<?php

namespace App\Http\Controllers\Admin;

use App\Services\AdminBackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class AdminBackupController extends AdminController
{
    public function __construct(private AdminBackupService $adminBackupService)
    {
    }

    public function index(Request $request): View|RedirectResponse
    {
        $admin = $this->requireAdmin($request);

        if ($admin instanceof RedirectResponse) {
            return $admin;
        }

        File::ensureDirectoryExists($this->adminBackupService->backupPath());

        if ($request->isMethod('post')) {
            return $this->handleAction($request);
        }

        return view('admin.backup', [
            'admin' => $admin,
            'backups' => $this->adminBackupService->listBackups(),
            'message' => session('success'),
            'error' => session('error'),
        ]);
    }

    public function restore(Request $request): RedirectResponse
    {
        $admin = $this->requireAdmin($request);

        if ($admin instanceof RedirectResponse) {
            return $admin;
        }

        $validated = $request->validate([
            'name' => ['required', 'string'],
        ]);

        try {
            $autoTimestamp = $this->adminBackupService->restoreBackup($validated['name']);

            return redirect()
                ->route('admin.backup.index')
                ->with('success', 'Backup restored successfully. Auto-backup created: ' . $autoTimestamp);
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('admin.backup.index')
                ->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return redirect()
                ->route('admin.backup.index')
                ->with('error', 'Database restoration failed: ' . $e->getMessage());
        }
    }

    private function handleAction(Request $request): RedirectResponse
    {
        $action = $request->input('action');

        if ($action === 'create') {
            $timestamp = $this->adminBackupService->createBackup();

            return back()->with('success', 'Backup created: ' . $timestamp);
        }

        if ($action === 'delete') {
            $validated = $request->validate([
                'name' => ['required', 'string'],
            ]);

            if ($this->adminBackupService->deleteBackup($validated['name'])) {
                return back()->with('success', 'Backup deleted.');
            }

            return back()->with('error', 'Failed to delete backup.');
        }

        return back()->with('error', 'Unknown action.');
    }
}
