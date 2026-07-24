<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

class AdminSystemController extends AdminController
{
    public function index(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        $admin = $this->requireAdmin($request);

        if ($admin instanceof \Illuminate\Http\RedirectResponse) {
            return $admin;
        }

        return view('admin.system-control', [
            'admin' => $admin,
        ]);
    }

    public function executeCommand(Request $request)
    {
        $admin = $this->adminFromSession($request);

        if (!$admin) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $command = $request->input('command');

        $allowedCommands = [
            'optimize:clear' => 'Cleared all caches safely.',
            'cache:clear'    => 'Application cache cleared.',
            'config:cache'   => 'Configuration cached successfully.',
            'route:cache'    => 'Routes cached successfully.',
            'view:cache'     => 'Views cached successfully.',
            'storage:link'   => 'Storage links created successfully.',
            'view:clear'     => 'View cache cleared successfully.',
            'route:clear'    => 'Route cache cleared successfully.',
            'config:clear'   => 'Config cache cleared successfully.',
        ];

        if (!array_key_exists($command, $allowedCommands)) {
            return response()->json(['success' => false, 'message' => 'Invalid command or not allowed.'], 400);
        }

        try {
            Artisan::call($command);
            $output = Artisan::output();
            
            return response()->json([
                'success' => true,
                'message' => $allowedCommands[$command],
                'output'  => $output ?: 'Success (no output)',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to execute command: ' . $e->getMessage(),
                'output'  => $e->getTraceAsString(),
            ], 500);
        }
    }
}
