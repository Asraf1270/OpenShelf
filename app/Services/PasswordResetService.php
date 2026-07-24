<?php

namespace App\Services;

use App\Models\LoginOtp;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class PasswordResetService
{
    private const OTP_EXPIRY_SECONDS = 600;

    private const MAX_ATTEMPTS = 5;

    public function __construct(private MailerService $mailer)
    {
    }

    public function findUserByPhoneAndEmail(string $phone, string $email): ?User
    {
        return User::query()
            ->where('phone', trim($phone))
            ->where('email', strtolower(trim($email)))
            ->first();
    }

    public function createAndSendOtp(User $user): ?string
    {
        $otp = sprintf('%06d', random_int(0, 999999));
        $otpId = 'fpr_' . uniqid() . '_' . bin2hex(random_bytes(4));

        LoginOtp::query()
            ->where('email', $user->email)
            ->orWhere('expires_at', '<', now())
            ->delete();

        LoginOtp::create([
            'id' => $otpId,
            'email' => $user->email,
            'otp_hash' => Hash::make($otp),
            'attempts' => 0,
            'verified' => false,
            'expires_at' => now()->addSeconds(self::OTP_EXPIRY_SECONDS),
            'created_at' => now(),
        ]);

        $sent = $this->mailer->sendTemplate(
            $user->email,
            $user->name,
            'forget_password',
            [
                'subject' => 'Password Reset Verification - OpenShelf',
                'otp' => $otp,
                'expiry_minutes' => (int) (self::OTP_EXPIRY_SECONDS / 60),
                'user_name' => $user->name,
            ],
            $user->id,
        );

        if (! $sent) {
            LoginOtp::query()->where('id', $otpId)->delete();

            return null;
        }

        return $otpId;
    }

    public function verifyOtp(string $otpId, string $submittedOtp): ?string
    {
        if ($otpId === '') {
            return null;
        }

        $otpRecord = LoginOtp::find($otpId);

        if (! $otpRecord || $otpRecord->expires_at->isPast()) {
            if ($otpRecord) {
                $otpRecord->delete();
            }

            return null;
        }

        if ($otpRecord->attempts >= self::MAX_ATTEMPTS) {
            $otpRecord->delete();

            return null;
        }

        $otpRecord->increment('attempts');

        if (! Hash::check($submittedOtp, $otpRecord->otp_hash)) {
            return null;
        }

        $otpRecord->update(['verified' => true]);

        return $otpRecord->email;
    }

    public function resetPassword(string $userId, string $password): bool
    {
        $user = User::find($userId);

        if (! $user) {
            return false;
        }

        $user->password_hash = Hash::make($password);
        $user->save();

        return true;
    }
}
