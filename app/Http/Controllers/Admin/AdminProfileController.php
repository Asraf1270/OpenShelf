<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AdminProfileController extends AdminController
{
    public function index(Request $request): View|RedirectResponse
    {
        $admin = $this->requireAdmin($request);

        if ($admin instanceof RedirectResponse) {
            return $admin;
        }

        if ($request->isMethod('post')) {
            return $this->update($request, $admin);
        }

        return view('admin.profile', compact('admin'));
    }

    private function update(Request $request, $admin): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'current_password' => ['nullable', 'string'],
            'new_password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        if (! empty($validated['new_password'])) {
            if (empty($validated['current_password'])) {
                return back()->withInput()->with('error', 'Current password is required to change password.');
            }

            if (! Hash::check($validated['current_password'], $admin->password_hash)) {
                return back()->withInput()->with('error', 'Current password is incorrect.');
            }
        }

        $admin->name = $validated['name'];

        if (! empty($validated['new_password'])) {
            $admin->password_hash = Hash::make($validated['new_password']);
        }

        $admin->save();

        $request->session()->put('admin_name', $admin->name);

        return back()->with('success', 'Profile updated successfully.');
    }
}
