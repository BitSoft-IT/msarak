<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email', ''))),
        ]);

        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $email = (string) $request->input('email');
        $ip = (string) $request->ip();

        $accountKey = 'login:account:'.sha1($email);
        $sourceKey = 'login:source:'.sha1($ip);

        $accountBlocked = RateLimiter::tooManyAttempts($accountKey, 5);
        $sourceBlocked = RateLimiter::tooManyAttempts($sourceKey, 5);

        if ($accountBlocked || $sourceBlocked) {
            $seconds = max(
                RateLimiter::availableIn($accountKey),
                RateLimiter::availableIn($sourceKey)
            );

            throw ValidationException::withMessages([
                'email' => 'محاولات دخول كثيرة. حاول مرة أخرى بعد '.$seconds.' ثانية.',
            ]);
        }

        if (! Auth::attempt([
            'email' => $email,
            'password' => (string) $request->input('password'),
        ])) {
            RateLimiter::hit($accountKey, 900);
            RateLimiter::hit($sourceKey, 900);

            throw ValidationException::withMessages([
                'email' => 'بيانات الدخول غير صحيحة.',
            ]);
        }

        RateLimiter::clear($accountKey);

        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
