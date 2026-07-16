<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

abstract class AdminController extends Controller
{
    protected function adminFromSession(Request $request): ?Admin
    {
        $adminId = $request->session()->get('admin_id');

        if (! $adminId) {
            return null;
        }

        return Admin::query()->find($adminId);
    }

    protected function requireAdmin(Request $request): Admin|RedirectResponse
    {
        $admin = $this->adminFromSession($request);

        if (! $admin) {
            return redirect()->route('admin.login');
        }

        return $admin;
    }
}
