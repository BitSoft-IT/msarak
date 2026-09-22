@extends('layouts.app')

@section('title', 'تسجيل الدخول')

@section('content')
    <div class="auth-shell">
        <section aria-labelledby="login-title" class="auth-card">
            <h1 id="login-title" class="text-2xl font-bold">تسجيل الدخول</h1>

            @if (session('status'))
                <p class="alert alert-success mt-4">{{ session('status') }}</p>
            @endif

            <form method="POST" action="{{ route('login') }}" class="auth-form mt-6 space-y-4">
                @csrf

                <div>
                    <label for="email" class="field-label">البريد الإلكتروني</label>
                    <input
                        id="email" name="email" type="email"
                        value="{{ old('email') }}"
                        required autofocus autocomplete="email"
                        class="field-input ltr-text"
                        @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                    >
                    @error('email')
                        <p id="email-error" class="mt-1 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="field-label">كلمة المرور</label>
                    <div class="field-wrap">
                        <input
                            id="password" name="password" type="password"
                            required autocomplete="current-password"
                            class="field-input field-input-with-toggle ltr-text"
                            @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                        >
                        <button type="button" class="field-toggle" data-password-toggle data-target="password"
                                aria-pressed="false" aria-label="إظهار كلمة المرور">👁️</button>
                    </div>
                    @error('password')
                        <p id="password-error" class="mt-1 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary w-full">دخول</button>
            </form>

            <p class="mt-4 text-center text-sm text-slate-600">
                <a href="{{ route('password.request') }}" class="font-medium text-brand-700 hover:underline">نسيت كلمة المرور؟</a>
                <span class="mx-2">·</span>
                <a href="{{ route('register') }}" class="font-medium text-brand-700 hover:underline">إنشاء حساب</a>
            </p>
        </section>

        <aside class="auth-panel" aria-hidden="true">
    <span class="auth-panel-icon">
        <svg class="h-7 w-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12H3m0 0 4-4m-4 4 4 4M15 5v-.5A2.5 2.5 0 0 1 17.5 2h2A2.5 2.5 0 0 1 22 4.5v15a2.5 2.5 0 0 1-2.5 2.5h-2a2.5 2.5 0 0 1-2.5-2.5V19" />
        </svg>
    </span>
    <h2 class="text-3xl font-bold">أهلًا بعودتك</h2>
    <p class="mt-4 text-lg text-brand-50">سجّل دخولك لمتابعة رحلتك في اكتشاف التخصص الأنسب لك.</p>
</aside>
    </div>
@endsection