<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ChangePasswordController extends Controller
{
    public function show(Request $request)
    {
        $userId = $request->session()->get('user_id');

        if (! $userId) {
            $request->session()->put('redirect_after_login', '/settings/change-password');

            return redirect()->route('login');
        }

        return view('settings.change-password', [
            'seoTitle' => 'Privacy & Security - OpenShelf',
            'seoDesc' => 'Change your OpenShelf account password and keep your credentials secure.',
        ]);
    }

    public function update(Request $request)
    {
        $userId = $request->session()->get('user_id');

        if (! $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Please log in to update settings.',
            ], 401);
        }

        $user = User::find($userId);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:6'],
            'confirm_password' => ['required', 'same:new_password'],
        ], [
            'current_password.required' => 'Current password is required',
            'new_password.required' => 'New password is required',
            'new_password.min' => 'New password must be at least 6 characters',
            'confirm_password.required' => 'Please confirm your new password',
            'confirm_password.same' => 'Passwords do not match',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ]);
        }

        if (! Hash::check($request->input('current_password'), $user->password_hash)) {
            return response()->json([
                'success' => false,
                'errors' => ['current_password' => ['Incorrect current password']],
            ]);
        }

        try {
            $user->password_hash = Hash::make($request->input('new_password'));
            $user->save();
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update password: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully!',
        ]);
    }
}
