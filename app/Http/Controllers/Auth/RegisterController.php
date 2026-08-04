<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\MailerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class RegisterController extends Controller
{
    public function __construct(private MailerService $mailer)
    {
    }

    public function show()
    {
        return view('register');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:3'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'department' => ['required', 'string', 'max:255'],
            'session' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'phone' => ['required', 'regex:/^01[3-9]\d{8}$/', Rule::unique('users', 'phone')],
            'roomNumber' => ['required', 'string', 'max:50'],
            'hall' => ['required', 'in:1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18'],
            'gender' => ['required', 'string', 'in:male,female'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'terms' => ['accepted'],
        ], [
            'session.regex' => 'Session must be in format YYYY-YY.',
            'phone.regex' => 'Please enter a valid Bangladeshi phone number.',
            'gender.required' => 'Gender selection is required.',
            'gender.in' => 'Please select a valid gender.',
            'terms.accepted' => 'You must accept the terms and privacy policy.',
        ]);

        $genderError = $this->validateGenderForHall((int) $validated['hall'], $validated['gender']);

        if ($genderError !== null) {
            return back()->withInput()->withErrors(['gender' => $genderError]);
        }

        $user = new User();
        $user->id = $this->generateUserId();
        $user->name = trim($validated['name']);
        $user->email = strtolower(trim($validated['email']));
        $user->department = trim($validated['department']);
        $user->session = trim($validated['session']);
        $user->phone = trim($validated['phone']);
        $user->room_number = trim($validated['roomNumber']);
        $user->hall = $validated['hall'];
        $user->gender = $validated['gender'];
        $user->password_hash = Hash::make($validated['password']);
        $user->otp_code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->otp_expiry = now()->addMinutes(15);
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

        $request->session()->put('verify_email', $user->email);

        return redirect()->route('register.verify')->with('success', 'Registration successful. Please verify your email.');
    }

    public function verify(Request $request)
    {
        return view('register.verify', [
            'email' => $request->session()->get('verify_email'),
        ]);
    }

    public function handleVerify(Request $request)
    {
        $validated = $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        $email = $request->session()->get('verify_email');

        if (! $email) {
            return redirect()->route('register')->with('error', 'Your verification session has expired. Please register again.');
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            return redirect()->route('register')->with('error', 'Unable to verify your account. Please register again.');
        }

        if ($user->verified) {
            return redirect()->route('login')->with('success', 'Your account has already been verified. Please log in.');
        }

        if (! $user->otp_expiry || now()->greaterThan($user->otp_expiry)) {
            return back()->withInput()->withErrors(['otp' => 'The verification code has expired. Please register again or contact support.']);
        }

        if ($validated['otp'] !== $user->otp_code) {
            return back()->withInput()->withErrors(['otp' => 'The verification code is invalid.']);
        }

        $user->verified = true;
        $user->status = 'active';
        $user->otp_code = null;
        $user->otp_expiry = null;
        $user->save();

        $request->session()->forget('verify_email');

        return redirect()->route('login')->with('success', 'Your account is verified. You may now sign in.');
    }

    private function validateGenderForHall(int $hall, string $gender): ?string
    {
        if ($hall >= 1 && $hall <= 13 && $gender !== 'male') {
            return 'Male gender is invalid for you.';
        }

        if ($hall >= 14 && $hall <= 18 && $gender !== 'female') {
            return 'Female gender is invalid for you.';
        }

        return null;
    }

    private function generateUserId(): string
    {
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ123456789';
        $userId = '';

        for ($i = 0; $i < 16; $i++) {
            $userId .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $userId;
    }
}
