<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoginOtp;
use App\Models\RememberToken;
use App\Models\User;
use App\Services\MailerService;
use App\Services\PasswordResetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AuthApiController extends Controller
{
    public function __construct(
        private MailerService $mailer,
        private PasswordResetService $passwordResetService,
    ) {
    }

    /**
     * POST /api/auth/register
     * Register a new user account.
     */
    public function register(Request $request): JsonResponse
    {
        // Normalize camelCase and alternative field names
        $input = $request->all();
        if (! isset($input['room_number']) && isset($input['roomNumber'])) {
            $input['room_number'] = $input['roomNumber'];
        }
        if (! isset($input['password_confirmation']) && isset($input['confirm_password'])) {
            $input['password_confirmation'] = $input['confirm_password'];
        }

        $validator = Validator::make($input, [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'department' => ['required', 'string', 'max:255'],
            'session' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'phone' => ['required', 'regex:/^01[3-9]\d{8}$/', Rule::unique('users', 'phone')],
            'room_number' => ['required', 'string', 'max:50'],
            'hall' => ['required', 'in:1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18'],
            'gender' => ['required', 'string', 'in:male,female'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'terms' => ['nullable'],
        ], [
            'session.regex' => 'Session must be in format YYYY-YY (e.g. 2020-21).',
            'phone.regex' => 'Please enter a valid Bangladeshi phone number (11 digits starting with 01).',
            'gender.required' => 'Gender selection is required.',
            'gender.in' => 'Please select a valid gender (male or female).',
            'hall.in' => 'Please select a valid residential hall.',
            'password.confirmed' => 'Password confirmation does not match.',
            'email.unique' => 'This email address is already registered.',
            'phone.unique' => 'This phone number is already registered.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $hall = (int) $input['hall'];
        $gender = strtolower(trim($input['gender']));

        $genderError = $this->validateGenderForHall($hall, $gender);
        if ($genderError !== null) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => [
                    'gender' => [$genderError],
                ],
            ], 422);
        }

        $user = new User();
        $user->id = $this->generateUserId();
        $user->name = trim($input['name']);
        $user->email = strtolower(trim($input['email']));
        $user->department = trim($input['department']);
        $user->session = trim($input['session']);
        $user->phone = trim($input['phone']);
        $user->room_number = trim($input['room_number']);
        $user->hall = (string) $hall;
        $user->gender = $gender;
        $user->password_hash = Hash::make($input['password']);
        $user->otp_code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->otp_expiry = Carbon::now()->addMinutes(15);
        $user->verified = false;
        $user->role = 'user';
        $user->profile_pic = 'default-avatar.jpg';
        $user->status = 'unverified';
        $user->save();

        $this->mailer->sendTemplate(
            $user->email,
            $user->name,
            'registration_otp',
            [
                'subject' => 'Verify Your OpenShelf Account',
                'otp' => $user->otp_code,
                'expiry_minutes' => 15,
                'user_name' => $user->name,
                'ip_address' => $request->ip(),
            ],
            $user->id,
        );

        if ($request->hasSession()) {
            $request->session()->put('verify_email', $user->email);
        }

        return response()->json([
            'success' => true,
            'message' => 'Registration successful. Please verify your email with the OTP sent.',
            'data' => [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'requires_verification' => true,
            ],
        ], 201);
    }

    /**
     * POST /api/auth/login
     * Authenticate user and issue a mobile token/session.
     */
    public function login(Request $request): JsonResponse
    {
        $loginInput = trim((string) ($request->input('login') ?? $request->input('phone') ?? $request->input('email') ?? ''));
        $password = (string) $request->input('password', '');

        if ($loginInput === '' || $password === '') {
            return response()->json([
                'success' => false,
                'message' => 'Credentials and password are required.',
            ], 422);
        }

        // Find user by email or phone
        $user = str_contains($loginInput, '@')
            ? User::where('email', strtolower($loginInput))->first()
            : User::where('phone', $loginInput)->first();

        if (! $user || ! Hash::check($password, $user->password_hash)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid phone/email or password.',
            ], 401);
        }

        if (! $user->verified) {
            if ($request->hasSession()) {
                $request->session()->put('verify_email', $user->email);
            }

            return response()->json([
                'success' => false,
                'message' => 'Your account is not verified yet. Please verify your account with OTP.',
                'requires_verification' => true,
                'data' => [
                    'email' => $user->email,
                    'phone' => $user->phone,
                ],
            ], 403);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Your account is currently ' . $user->status . '. Please contact support.',
            ], 403);
        }

        // Generate mobile remember token
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
            'user_agent' => $request->userAgent() ?? 'OpenShelf Mobile App',
            'ip_address' => $request->ip(),
        ]);

        $plainToken = $user->id . ':' . $token;

        $user->last_login = now();
        $user->save();

        if ($request->hasSession()) {
            $request->session()->put([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_role' => $user->role,
                'user_hall' => $user->hall,
                'login_time' => time(),
            ]);
            $request->session()->regenerate();
        }

        $this->logUserActivity($user->id, 'login');

        return response()->json([
            'success' => true,
            'message' => 'Logged in successfully.',
            'token' => $plainToken,
            'token_type' => 'Bearer',
            'user' => $this->formatUser($user),
        ])->withCookie(cookie('remember_token', $plainToken, 60 * 24 * 30));
    }

    /**
     * POST /api/auth/logout
     * Log out current user, revoke active token, and clear session.
     */
    public function logout(Request $request): JsonResponse
    {
        $rawToken = $request->bearerToken()
            ?? $request->header('X-Auth-Token')
            ?? $request->cookie('remember_token');

        if ($rawToken) {
            if (str_contains($rawToken, ':')) {
                [$tokenUserId, $tokenValue] = explode(':', $rawToken, 2);
                RememberToken::query()
                    ->where('user_id', $tokenUserId)
                    ->where('token', hash('sha256', $tokenValue))
                    ->delete();
            } else {
                RememberToken::query()
                    ->where('token', hash('sha256', $rawToken))
                    ->delete();
            }
        }

        $userId = $request->hasSession() ? $request->session()->get('user_id') : null;
        if ($userId) {
            RememberToken::query()
                ->where('user_id', $userId)
                ->where('expiry', '<=', time())
                ->delete();

            $this->logUserActivity($userId, 'logout');

            if ($request->session()->get('user_role') === 'admin') {
                $this->logAdminAudit($request->session()->get('user_name', 'Unknown'));
            }
        }

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json([
            'success' => true,
            'message' => 'Successfully logged out.',
        ])->withCookie(cookie()->forget('remember_token'));
    }

    /**
     * POST /api/auth/forgot-password
     * Request a password reset OTP.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $phone = trim((string) $request->input('phone', ''));
        $email = strtolower(trim((string) $request->input('email', '')));

        if ($phone === '' && $email === '') {
            return response()->json([
                'success' => false,
                'message' => 'Please provide an email address or phone number.',
            ], 422);
        }

        $user = null;
        if ($phone !== '' && $email !== '') {
            $user = $this->passwordResetService->findUserByPhoneAndEmail($phone, $email);
        } elseif ($email !== '') {
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid email address format.',
                ], 422);
            }
            $user = User::where('email', $email)->first();
        } else {
            $user = User::where('phone', $phone)->first();
        }

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'No account found matching the provided details.',
            ], 404);
        }

        $otpId = $this->passwordResetService->createAndSendOtp($user);

        if (! $otpId) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send verification email. Please try again later.',
            ], 500);
        }

        if ($request->hasSession()) {
            $request->session()->put([
                'forget_pwd_step' => 'verify',
                'forget_pwd_phone' => $user->phone,
                'forget_pwd_email' => $user->email,
                'forget_pwd_otp_id' => $otpId,
                'forget_pwd_user_id' => $user->id,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Verification code sent to your email.',
            'data' => [
                'otp_id' => $otpId,
                'email' => $user->email,
                'phone' => $user->phone,
                'expiry_minutes' => 10,
            ],
        ]);
    }

    /**
     * POST /api/auth/verify-otp
     * Verify signup OTP or password reset OTP.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'otp' => ['required', 'digits:6'],
            'type' => ['nullable', 'in:register,reset'],
            'otp_id' => ['nullable', 'string'],
            'email' => ['nullable', 'string'],
            'phone' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $submittedOtp = trim((string) $request->input('otp'));
        $type = $request->input('type');
        $otpId = trim((string) ($request->input('otp_id') ?? ($request->hasSession() ? $request->session()->get('forget_pwd_otp_id') : '')));

        // Determine if this is a reset OTP verification
        $isReset = ($type === 'reset') || ($otpId !== '');

        if ($isReset) {
            return $this->verifyPasswordResetOtp($request, $submittedOtp, $otpId);
        }

        return $this->verifyRegistrationOtp($request, $submittedOtp);
    }

    /**
     * POST /api/auth/reset-password
     * Reset password using OTP or verified reset session/token.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $input = $request->all();
        if (! isset($input['password_confirmation']) && isset($input['confirm_password'])) {
            $input['password_confirmation'] = $input['confirm_password'];
        }

        $validator = Validator::make($input, [
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'otp_id' => ['nullable', 'string'],
            'otp' => ['nullable', 'digits:6'],
            'email' => ['nullable', 'email'],
        ], [
            'password.confirmed' => 'Password confirmation does not match.',
            'password.min' => 'Password must be at least 8 characters long.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $otpId = trim((string) ($input['otp_id'] ?? ($request->hasSession() ? $request->session()->get('forget_pwd_otp_id') : '')));
        $email = strtolower(trim((string) ($input['email'] ?? ($request->hasSession() ? $request->session()->get('forget_pwd_email') : ''))));
        $submittedOtp = isset($input['otp']) ? trim((string) $input['otp']) : null;
        $user = null;

        // Path 1: Check by otp_id
        if ($otpId !== '') {
            $otpRecord = LoginOtp::find($otpId);

            if (! $otpRecord || $otpRecord->expires_at->isPast()) {
                if ($otpRecord) {
                    $otpRecord->delete();
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Password reset session has expired. Please request a new verification code.',
                ], 422);
            }

            // If not verified yet, but OTP is provided in this request, verify now
            if (! $otpRecord->verified) {
                if (! $submittedOtp) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Please verify your OTP code before resetting password.',
                    ], 422);
                }

                if (! Hash::check($submittedOtp, $otpRecord->otp_hash)) {
                    $otpRecord->increment('attempts');

                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid verification code.',
                    ], 422);
                }

                $otpRecord->update(['verified' => true]);
            }

            $user = User::where('email', $otpRecord->email)->first();
            $otpRecord->delete();
        } elseif ($email !== '' && $submittedOtp !== null) {
            // Path 2: Email + OTP direct verification and reset
            $otpRecord = LoginOtp::where('email', $email)
                ->where('expires_at', '>', now())
                ->latest('created_at')
                ->first();

            if (! $otpRecord || ! Hash::check($submittedOtp, $otpRecord->otp_hash)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired verification code.',
                ], 422);
            }

            $user = User::where('email', $email)->first();
            $otpRecord->delete();
        } elseif ($request->hasSession() && $request->session()->get('forget_pwd_user_id')) {
            // Path 3: Session-based reset
            $user = User::find($request->session()->get('forget_pwd_user_id'));
        }

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to identify account for password reset. Please start the process again.',
            ], 422);
        }

        $user->password_hash = Hash::make($input['password']);
        $user->save();

        // Clear any remaining OTPs for this email
        LoginOtp::where('email', $user->email)->delete();

        if ($request->hasSession()) {
            $request->session()->forget([
                'forget_pwd_step',
                'forget_pwd_phone',
                'forget_pwd_email',
                'forget_pwd_otp_id',
                'forget_pwd_user_id',
            ]);
        }

        $this->logUserActivity($user->id, 'password_reset');

        return response()->json([
            'success' => true,
            'message' => 'Password reset successful! You can now log in with your new password.',
        ]);
    }

    /**
     * Handle registration OTP verification.
     */
    private function verifyRegistrationOtp(Request $request, string $submittedOtp): JsonResponse
    {
        $email = strtolower(trim((string) ($request->input('email') ?? ($request->hasSession() ? $request->session()->get('verify_email') : ''))));
        $phone = trim((string) $request->input('phone', ''));

        $user = null;
        if ($email !== '') {
            $user = User::where('email', $email)->first();
        } elseif ($phone !== '') {
            $user = User::where('phone', $phone)->first();
        }

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to find user account. Please register again.',
            ], 404);
        }

        if ($user->verified) {
            return response()->json([
                'success' => true,
                'message' => 'Your account has already been verified. Please log in.',
                'type' => 'register',
            ]);
        }

        if (! $user->otp_expiry || now()->greaterThan($user->otp_expiry)) {
            return response()->json([
                'success' => false,
                'message' => 'The verification code has expired. Please register again or request a new code.',
            ], 422);
        }

        if ($submittedOtp !== $user->otp_code) {
            return response()->json([
                'success' => false,
                'message' => 'The verification code is invalid.',
            ], 422);
        }

        $user->verified = true;
        $user->status = 'active';
        $user->otp_code = null;
        $user->otp_expiry = null;
        $user->save();

        $this->mailer->sendTemplate(
            $user->email,
            $user->name,
            'welcome',
            [
                'subject' => 'Welcome to OpenShelf!',
                'user_name' => $user->name,
            ],
            $user->id
        );

        if ($request->hasSession()) {
            $request->session()->forget('verify_email');
            $request->session()->put([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_role' => $user->role,
                'user_hall' => $user->hall,
                'login_time' => time(),
            ]);
        }

        // Issue token for mobile app auto-login
        $token = Str::random(40);
        $hashedToken = hash('sha256', $token);

        RememberToken::create([
            'user_id' => $user->id,
            'token' => $hashedToken,
            'expiry' => time() + (60 * 60 * 24 * 30),
            'user_agent' => $request->userAgent() ?? 'OpenShelf Mobile App',
            'ip_address' => $request->ip(),
        ]);

        $plainToken = $user->id . ':' . $token;

        $this->logUserActivity($user->id, 'verify_signup');

        return response()->json([
            'success' => true,
            'message' => 'Your account has been verified successfully.',
            'type' => 'register',
            'token' => $plainToken,
            'token_type' => 'Bearer',
            'user' => $this->formatUser($user),
        ])->withCookie(cookie('remember_token', $plainToken, 60 * 24 * 30));
    }

    /**
     * Handle password reset OTP verification.
     */
    private function verifyPasswordResetOtp(Request $request, string $submittedOtp, string $otpId): JsonResponse
    {
        $email = strtolower(trim((string) $request->input('email', '')));
        $otpRecord = null;

        if ($otpId !== '') {
            $otpRecord = LoginOtp::find($otpId);
        } elseif ($email !== '') {
            $otpRecord = LoginOtp::where('email', $email)
                ->where('expires_at', '>', now())
                ->latest('created_at')
                ->first();
        }

        if (! $otpRecord || $otpRecord->expires_at->isPast()) {
            if ($otpRecord) {
                $otpRecord->delete();
            }

            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired verification code.',
            ], 422);
        }

        if ($otpRecord->attempts >= 5) {
            $otpRecord->delete();

            return response()->json([
                'success' => false,
                'message' => 'Maximum verification attempts exceeded. Please request a new verification code.',
            ], 422);
        }

        if (! Hash::check($submittedOtp, $otpRecord->otp_hash)) {
            $otpRecord->increment('attempts');

            return response()->json([
                'success' => false,
                'message' => 'The verification code is invalid.',
            ], 422);
        }

        $otpRecord->update(['verified' => true]);

        if ($request->hasSession()) {
            $request->session()->put([
                'forget_pwd_step' => 'reset',
                'forget_pwd_otp_id' => $otpRecord->id,
                'forget_pwd_email' => $otpRecord->email,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Verification code verified successfully. You can now reset your password.',
            'type' => 'reset',
            'data' => [
                'otp_id' => $otpRecord->id,
                'email' => $otpRecord->email,
            ],
        ]);
    }

    /**
     * Format user model for API response.
     */
    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'department' => $user->department,
            'session' => $user->session,
            'room_number' => $user->room_number,
            'hall' => (string) $user->hall,
            'hall_name' => $user->hall_name,
            'gender' => $user->gender,
            'role' => $user->role,
            'status' => $user->status,
            'verified' => (bool) $user->verified,
            'profile_pic' => $user->profile_pic,
            'profile_image_url' => $user->profile_image_url,
            'bio' => $user->bio,
            'boipoka_badge' => $user->boipoka_badge,
            'boipoka_position' => $user->boipoka_position,
            'created_at' => $user->created_at?->toISOString(),
        ];
    }

    /**
     * Validate gender matching residential hall rules.
     */
    private function validateGenderForHall(int $hall, string $gender): ?string
    {
        if ($hall >= 1 && $hall <= 13 && $gender !== 'male') {
            return 'Male gender is required for the selected residential hall.';
        }

        if ($hall >= 14 && $hall <= 18 && $gender !== 'female') {
            return 'Female gender is required for the selected residential hall.';
        }

        return null;
    }

    /**
     * Generate unique 16-character alphanumeric user ID.
     */
    private function generateUserId(): string
    {
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ123456789';
        $userId = '';
        $max = strlen($characters) - 1;

        for ($i = 0; $i < 16; $i++) {
            $userId .= $characters[random_int(0, $max)];
        }

        return $userId;
    }

    /**
     * Log user activity.
     */
    private function logUserActivity(string $userId, string $action): void
    {
        $logFile = storage_path('logs/user_activity.log');
        $entry = now()->format('Y-m-d H:i:s') . " | User: {$userId} | Action: {$action} | IP: " . request()->ip() . PHP_EOL;
        @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Log admin audit activity.
     */
    private function logAdminAudit(string $userName): void
    {
        $logFile = storage_path('logs/admin_audit.log');
        $entry = now()->format('Y-m-d H:i:s') . " | Admin: {$userName} | Action: logout | IP: " . request()->ip() . PHP_EOL;
        @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    }
}
