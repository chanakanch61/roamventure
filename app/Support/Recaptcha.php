<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Google reCAPTCHA v3 for the public booking form (RECAPTCHA_SITE_KEY / RECAPTCHA_SECRET_KEY in .env).
 * With either key empty the check is off, so local and staging sites keep working without Google.
 */
class Recaptcha
{
    private const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    public function enabled(): bool
    {
        return $this->site_key() !== '' && trim((string) config('services.recaptcha.secret_key')) !== '';
    }

    /** The key the browser uses to request a token; '' while the check is off. */
    public function site_key(): string
    {
        $key = trim((string) config('services.recaptcha.site_key'));

        return $key !== '' && trim((string) config('services.recaptcha.secret_key')) !== '' ? $key : '';
    }

    /**
     * True when the token is valid, was issued for $action, and scores at or above RECAPTCHA_MIN_SCORE.
     * If Google cannot be reached the request is turned away and an error is logged: passing it instead
     * would let a broken server setup (no CA bundle, blocked outbound HTTPS) switch protection off unnoticed.
     */
    public function passes($token, string $action): bool
    {
        if (! $this->enabled()) {
            return true;
        }
        if (! is_string($token) || $token === '' || strlen($token) > 4000) {
            return false;
        }
        try {
            $response = Http::asForm()->timeout(5)->connectTimeout(3)->post(self::VERIFY_URL, [
                'secret' => trim((string) config('services.recaptcha.secret_key')),
                'response' => $token,
                'remoteip' => request()->ip(),
            ]);
        } catch (Throwable $e) {
            Log::error('reCAPTCHA verify unreachable, booking turned away: '.$e->getMessage());

            return false;
        }
        if (! $response->successful()) {
            Log::error('reCAPTCHA verify failed, booking turned away: HTTP '.$response->status());

            return false;
        }
        $result = (array) $response->json();
        $score = (float) ($result['score'] ?? 0);
        $passed = ($result['success'] ?? false) === true
            && ($result['action'] ?? '') === $action
            && $score >= (float) config('services.recaptcha.min_score', 0.5);
        if (! $passed) {
            Log::info('reCAPTCHA rejected', [
                'action' => $result['action'] ?? null,
                'score' => $result['score'] ?? null,
                'hostname' => $result['hostname'] ?? null,
                'errors' => $result['error-codes'] ?? [],
                'ip' => request()->ip(),
            ]);
        }

        return $passed;
    }
}
