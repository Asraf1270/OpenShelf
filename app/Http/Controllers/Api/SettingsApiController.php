<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ChangePasswordController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\EditProfileController;
use Illuminate\Http\Request;

class SettingsApiController extends Controller
{
    public function handle(Request $request)
    {
        $userId = $request->session()->get('user_id');

        if (! $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Please log in to update settings.',
            ], 401);
        }

        if ($request->isMethod('get')) {
            return response()->json([
                'success' => false,
                'message' => 'Method not allowed.',
            ], 405);
        }

        $action = $request->input('action');

        return match ($action) {
            'update_profile' => app(EditProfileController::class)->update($request),
            'change_password' => app(ChangePasswordController::class)->update($request),
            default => response()->json([
                'success' => false,
                'message' => 'Invalid action.',
            ]),
        };
    }
}
