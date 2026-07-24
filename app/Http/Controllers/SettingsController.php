<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->session()->get('user_id');

        if (! $userId) {
            $request->session()->put('redirect_after_login', '/settings');

            return redirect()->route('login');
        }

        $user = User::query()
            ->select('id', 'name', 'email', 'department', 'hall', 'profile_pic')
            ->find($userId);

        if (! $user) {
            abort(404, 'User not found.');
        }

        return view('settings', [
            'seoTitle' => 'Settings - OpenShelf',
            'seoDesc' => 'Manage your OpenShelf profile details, library preferences, and security settings.',
            'user' => $user,
        ]);
    }
}
