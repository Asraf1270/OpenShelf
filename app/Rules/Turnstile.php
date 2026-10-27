<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Turnstile implements ValidationRule
{
    public function __construct(
        protected ?string $customMessage = null
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Skip verification in testing environment or if Turnstile is disabled
        if (! config('services.turnstile.enabled', true) || app()->environment('testing')) {
            return;
        }

        if (empty($value) || ! is_string($value)) {
            $fail($this->customMessage ?? 'Security verification failed. Please complete the captcha.');
            return;
        }

        $secret = config('services.turnstile.secret');
        if (empty($secret)) {
            return;
        }

        try {
            $response = Http::asForm()
                ->timeout(10)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => $secret,
                    'response' => $value,
                    'remoteip' => request()->ip(),
                ]);

            if (! $response->successful() || ! $response->json('success')) {
                Log::warning('Cloudflare Turnstile verification failed', [
                    'errors' => $response->json('error-codes', []),
                    'ip' => request()->ip(),
                ]);

                $fail($this->customMessage ?? 'Security verification failed. Please try again.');
            }
        } catch (\Throwable $e) {
            Log::error('Cloudflare Turnstile verification error: ' . $e->getMessage());
            $fail($this->customMessage ?? 'Security verification could not be completed. Please try again.');
        }
    }
}
