<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\PasswordResetService;
use Illuminate\Http\Request;

class ForgotPasswordController extends Controller
{
    public function __construct(private PasswordResetService $passwordResetService)
    {
    }

    public function show(Request $request)
    {
        if ($request->boolean('reset_session')) {
            $this->clearResetSession($request);

            return redirect()->route('password.forgot');
        }

        return view('forgot-password', [
            'step' => $request->session()->get('forget_pwd_step', 'identify'),
            'phone' => $request->session()->get('forget_pwd_phone', ''),
            'email' => $request->session()->get('forget_pwd_email', ''),
            'error' => session('error'),
            'success' => session('success'),
        ]);
    }

    public function handle(Request $request)
    {
        $action = $request->input('action');

        if ($action === 'identify') {
            return $this->handleIdentify($request);
        }

        if ($action === 'verify') {
            return $this->handleVerify($request);
        }

        if ($action === 'reset') {
            return $this->handleReset($request);
        }

        return back()->with('error', 'Invalid action.');
    }

    private function handleIdentify(Request $request)
    {
        $phone = trim($request->input('phone', ''));
        $email = trim($request->input('email', ''));

        if ($phone === '' || $email === '') {
            return back()->withInput()->with('error', 'Both phone and email are required.');
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return back()->withInput()->with('error', 'Invalid email address.');
        }

        $user = $this->passwordResetService->findUserByPhoneAndEmail($phone, $email);

        if (! $user) {
            return back()->withInput()->with('error', 'No account found with these details.');
        }

        $otpId = $this->passwordResetService->createAndSendOtp($user);

        if (! $otpId) {
            return back()->withInput()->with('error', 'Failed to send verification email. Please try again.');
        }

        $request->session()->put([
            'forget_pwd_step' => 'verify',
            'forget_pwd_phone' => $phone,
            'forget_pwd_email' => $email,
            'forget_pwd_otp_id' => $otpId,
            'forget_pwd_user_id' => $user->id,
        ]);

        return redirect()->route('password.forgot')->with('success', 'Verification code sent to your email.');
    }

    private function handleVerify(Request $request)
    {
        $submittedOtp = trim($request->input('otp', ''));
        $otpId = $request->session()->get('forget_pwd_otp_id', '');

        if ($submittedOtp === '') {
            return back()->with('error', 'Verification code is required.');
        }

        $verifiedEmail = $this->passwordResetService->verifyOtp($otpId, $submittedOtp);

        if (! $verifiedEmail) {
            return back()->with('error', 'Invalid or expired verification code.');
        }

        $request->session()->put('forget_pwd_step', 'reset');

        return redirect()->route('password.forgot')->with('success', 'Account verified! Please set your new password.');
    }

    private function handleReset(Request $request)
    {
        $password = $request->input('password', '');
        $confirmPassword = $request->input('confirm_password', '');
        $userId = $request->session()->get('forget_pwd_user_id', '');

        if (strlen($password) < 8) {
            return back()->with('error', 'Password must be at least 8 characters long.');
        }

        if ($password !== $confirmPassword) {
            return back()->with('error', 'Passwords do not match.');
        }

        if ($userId === '') {
            $this->clearResetSession($request);

            return redirect()->route('password.forgot')->with('error', 'Session expired. Please start over.');
        }

        if (! $this->passwordResetService->resetPassword($userId, $password)) {
            return back()->with('error', 'Failed to update password. Please try again.');
        }

        $this->clearResetSession($request);

        return redirect()->route('login')->with('success', 'Password reset successful! You can now login.');
    }

    private function clearResetSession(Request $request): void
    {
        $request->session()->forget([
            'forget_pwd_step',
            'forget_pwd_phone',
            'forget_pwd_email',
            'forget_pwd_otp_id',
            'forget_pwd_user_id',
        ]);
    }
}
