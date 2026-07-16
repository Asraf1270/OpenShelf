<?php

namespace App\Http\Controllers;

use App\Models\SupportUs;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupportUsController extends Controller
{
    private const ACCOUNT_NUMBERS = [
        'bkash' => '01576690638',
        'nagad' => '01576690638',
        'rocket' => '015766906386',
    ];

    public function show(Request $request): View|RedirectResponse
    {
        $userId = $request->session()->get('user_id');

        if (! $userId) {
            $request->session()->put('redirect_after_login', route('support-us'));

            return redirect()->route('login');
        }

        $user = User::query()
            ->select('id', 'name', 'email', 'phone', 'department', 'session', 'room_number')
            ->findOrFail($userId);

        return view('support-us', [
            'user' => $user,
            'accountNumbers' => self::ACCOUNT_NUMBERS,
            'supportFormValues' => $this->formValuesFromOldInput($request),
            'success' => session('success'),
            'errors' => session('errors')?->all() ?? [],
            'seoTitle' => 'Support Us - OpenShelf',
            'seoDesc' => 'Support OpenShelf with a donation via bKash, Nagad, or Rocket.',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('user_id');

        if (! $userId) {
            $request->session()->put('redirect_after_login', route('support-us'));

            return redirect()->route('login');
        }

        $user = User::query()
            ->select('id', 'name', 'email', 'phone', 'department', 'session', 'room_number')
            ->findOrFail($userId);

        $validated = $request->validate([
            'provider' => ['required', 'in:'.implode(',', array_keys(self::ACCOUNT_NUMBERS))],
            'amount' => ['required', 'string'],
            'transaction_id' => ['required', 'string', 'max:100'],
        ]);

        $amount = str_replace(',', '', $validated['amount']);

        if (! is_numeric($amount) || (float) $amount <= 0) {
            return back()
                ->withInput()
                ->withErrors(['Please enter a valid amount for your donation.']);
        }

        $provider = $validated['provider'];

        try {
            SupportUs::create([
                'id' => strtoupper('SUP' . substr(uniqid('', true), -12)),
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'user_phone' => $user->phone,
                'user_department' => $user->department,
                'user_session' => $user->session,
                'user_room' => $user->room_number,
                'provider' => $provider,
                'account_number' => self::ACCOUNT_NUMBERS[$provider],
                'amount' => number_format((float) $amount, 2, '.', ''),
                'transaction_id' => trim($validated['transaction_id']),
                'status' => 'pending',
                'submitted_at' => now(),
            ]);
        } catch (\Throwable) {
            return back()
                ->withInput()
                ->withErrors(['Unable to save your request. Please try again later.']);
        }

        return redirect()
            ->route('support-us')
            ->with('success', 'Your support submission has been received. Our team will verify it shortly.');
    }

    private function formValuesFromOldInput(Request $request): array
    {
        $defaults = [
            'bkash' => ['amount' => '', 'transaction_id' => ''],
            'nagad' => ['amount' => '', 'transaction_id' => ''],
            'rocket' => ['amount' => '', 'transaction_id' => ''],
        ];

        $provider = old('provider');

        if ($provider && isset($defaults[$provider])) {
            $defaults[$provider] = [
                'amount' => old('amount', ''),
                'transaction_id' => old('transaction_id', ''),
            ];
        }

        return $defaults;
    }
}
