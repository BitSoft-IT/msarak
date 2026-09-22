@extends('layouts.app')

@section('title', 'تعيين كلمة مرور جديدة')

@section('content')
    <div class="auth-shell">
        <section aria-labelledby="reset-title" class="auth-card">
            <h1 id="reset-title" class="text-2xl font-bold">تعيين كلمة مرور جديدة</h1>

            <form method="POST" action="{{ route('password.store') }}" class="auth-form mt-6 space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div>
                    <label for="email" class="field-label">البريد الإلكتروني</label>
                    <input
                        id="email" name="email" type="email"
                        value="{{ old('email', $email) }}"
                        required autocomplete="email"
                        class="field-input ltr-text"
                        @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                    >
                    @error('email')
                        <p id="email-error" class="mt-1 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="field-label">كلمة المرور الجديدة (8 محارف على الأقل)</label>
                    <div class="field-wrap">
                        <input
                            id="password" name="password" type="password"
                            required autocomplete="new-password"
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

                <div>
                    <label for="password_confirmation" class="field-label">تأكيد كلمة المرور الجديدة</label>
                    <div class="field-wrap">
                        <input
                            id="password_confirmation" name="password_confirmation" type="password"
                            required autocomplete="new-password"
                            class="field-input field-input-with-toggle ltr-text"
                        >
                        <button type="button" class="field-toggle" data-password-toggle data-target="password_confirmation"
                                aria-pressed="false" aria-label="إظهار كلمة المرور">👁️</button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-full">تغيير كلمة المرور</button>
            </form>
        </section>

        <aside class="auth-panel" aria-hidden="true">
    <span class="auth-panel-icon">
        <svg class="h-7 w-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
        </svg>
    </span>
    <h2 class="text-3xl font-bold">خطوة أخيرة</h2>
    <p class="mt-4 text-lg text-brand-50">اختر كلمة مرور قوية لحماية حسابك، ولن نطلب منك هذا الرابط مرة أخرى.</p>
</aside>
    </div>
@endsection