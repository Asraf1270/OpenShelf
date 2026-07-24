<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class AdminLogsController extends AdminController
{
    private const LOG_TYPES = [
        'admin' => 'admin_audit.log',
        'user' => 'user_activity.log',
        'error' => 'error.log',
        'mail' => 'mail.log',
    ];

    public function index(Request $request): View|RedirectResponse
    {
        $admin = $this->requireAdmin($request);

        if ($admin instanceof RedirectResponse) {
            return $admin;
        }

        $logType = $this->resolveLogType($request->query('type', 'admin'));
        $logFile = $this->logFilePath($logType);
        $logs = [];

        if (file_exists($logFile)) {
            $lines = explode("\n", trim((string) file_get_contents($logFile)));
            $logs = array_reverse(array_slice($lines, -200));
        } elseif ($logType === 'error' && file_exists(storage_path('logs/laravel.log'))) {
            $lines = explode("\n", trim((string) file_get_contents(storage_path('logs/laravel.log'))));
            $logs = array_reverse(array_slice($lines, -200));
        }

        return view('admin.logs', [
            'admin' => $admin,
            'logType' => $logType,
            'logs' => $logs,
            'message' => session('success'),
            'error' => session('error'),
        ]);
    }

    public function clear(Request $request): RedirectResponse
    {
        $admin = $this->requireAdmin($request);

        if ($admin instanceof RedirectResponse) {
            return $admin;
        }

        $logType = $this->resolveLogType($request->query('type', 'admin'));
        $logFile = $this->logFilePath($logType);

        if (file_exists($logFile)) {
            file_put_contents($logFile, '');

            return redirect()
                ->route('admin.logs.index', ['type' => $logType])
                ->with('success', 'Logs cleared successfully.');
        }

        return redirect()
            ->route('admin.logs.index', ['type' => $logType])
            ->with('error', 'Log file not found.');
    }

    public function download(Request $request): Response|RedirectResponse
    {
        $admin = $this->requireAdmin($request);

        if ($admin instanceof RedirectResponse) {
            return $admin;
        }

        $logType = $this->resolveLogType($request->query('type', 'admin'));
        $logFile = $this->logFilePath($logType);
        $filename = $logType . '_logs_' . now()->format('Y-m-d') . '.log';

        if (! file_exists($logFile)) {
            return redirect()
                ->route('admin.logs.index', ['type' => $logType])
                ->with('error', 'Log file not found.');
        }

        return response(file_get_contents($logFile), 200, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function resolveLogType(?string $type): string
    {
        return array_key_exists($type, self::LOG_TYPES) ? $type : 'admin';
    }

    private function logFilePath(string $logType): string
    {
        return storage_path('logs/' . self::LOG_TYPES[$logType]);
    }
}
