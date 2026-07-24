<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AdminAuthController extends AdminController
{
    public function show(Request $request): View|RedirectResponse
    {
        if ($request->session()->get('admin_logged_in')) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $admin = Admin::query()
            ->where('email', trim($validated['email']))
            ->where('status', 'active')
            ->first();

        if (! $admin || ! Hash::check($validated['password'], $admin->password_hash)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Invalid email or password.']);
        }

        $request->session()->put([
            'admin_id' => $admin->id,
            'admin_email' => $admin->email,
            'admin_name' => $admin->name,
            'admin_role' => $admin->role,
            'admin_logged_in' => true,
            'admin_login_time' => time(),
        ]);
        $request->session()->regenerate();

        $admin->last_login = now();
        $admin->save();

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget([
            'admin_id',
            'admin_email',
            'admin_name',
            'admin_role',
            'admin_logged_in',
            'admin_login_time',
        ]);

        return redirect()
            ->route('admin.login')
            ->with('success', 'You have been signed out of the admin portal.');
    }
}
