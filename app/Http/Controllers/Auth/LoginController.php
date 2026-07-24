<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\RememberToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    public function show(Request $request)
    {
        if ($request->session()->has('user_id')) {
            return redirect()->route('books');
        }

        if ($request->has('redirect')) {
            $request->session()->put('redirect_after_login', $request->get('redirect'));
        }

        return view('login', [
            'success' => session('success'),
        ]);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'phone' => ['required', 'regex:/^01[3-9]\d{8}$/'],
            'password' => ['required', 'string'],
        ], [
            'phone.regex' => 'Please enter a valid Bangladeshi phone number (11 digits starting with 01).',
        ]);

        $user = User::where('phone', trim($validated['phone']))->first();

        if (! $user || ! Hash::check($validated['password'], $user->password_hash)) {
            return back()->withInput()->withErrors([
                'phone' => 'Invalid phone number or password.',
            ]);
        }

        if (! $user->verified) {
            $request->session()->put('verify_email', $user->email);

            return redirect()->route('register.verify');
        }

        if ($user->status !== 'active') {
            return back()->withInput()->withErrors([
                'phone' => 'Your account is currently ' . $user->status . '. Please contact support.',
            ]);
        }

        $request->session()->put([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_role' => $user->role,
            'user_hall' => $user->hall,
            'login_time' => time(),
        ]);
        $request->session()->regenerate();

        $user->last_login = now();
        $user->save();

        $redirectTarget = $request->session()->pull('redirect_after_login', '/');
        $response = redirect()->to($redirectTarget);

        if ($request->boolean('remember_me')) {
            $token = Str::random(40);
            $hashedToken = hash('sha256', $token);

            RememberToken::query()
                ->where('user_id', $user->id)
                ->where('expiry', '<=', time())
                ->delete();

            RememberToken::create([
                'user_id' => $user->id,
                'token' => $hashedToken,
                'expiry' => time() + (60 * 60 * 24 * 30),
                'user_agent' => $request->userAgent() ?? 'unknown',
                'ip_address' => $request->ip(),
            ]);

            $response->withCookie(cookie('remember_token', $user->id . ':' . $token, 60 * 24 * 30));
        } else {
            $response->withCookie(cookie()->forget('remember_token'));
        }

        return $response;
    }

    public function logout(Request $request)
    {
        $userId = $request->session()->get('user_id');
        $userName = $request->session()->get('user_name', 'Unknown');
        $userRole = $request->session()->get('user_role');

        if ($request->cookie('remember_token')) {
            $token = $request->cookie('remember_token');

            if (str_contains($token, ':')) {
                [$tokenUserId, $tokenValue] = explode(':', $token, 2);

                RememberToken::query()
                    ->where('user_id', $tokenUserId)
                    ->where('token', hash('sha256', $tokenValue))
                    ->delete();
            }
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($userId) {
            $this->logUserActivity($userId, 'logout');

            if ($userRole === 'admin') {
                $this->logAdminAudit($userName);
            }
        }

        $redirectUrl = $request->query('redirect', route('home'));

        return redirect()->to($redirectUrl)
            ->with('success', 'You have been successfully logged out.')
            ->withCookie(cookie()->forget('remember_token'));
    }

    private function logUserActivity(string $userId, string $action): void
    {
        $logFile = storage_path('logs/user_activity.log');
        $entry = now()->format('Y-m-d H:i:s') . " | User: {$userId} | Action: {$action} | IP: " . request()->ip() . PHP_EOL;
        file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    }

    private function logAdminAudit(string $userName): void
    {
        $logFile = storage_path('logs/admin_audit.log');
        $entry = now()->format('Y-m-d H:i:s') . " | Admin: {$userName} | Action: logout | IP: " . request()->ip() . PHP_EOL;
        file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    }
}
