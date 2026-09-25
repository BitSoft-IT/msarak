@extends('layouts.app')

@section('title', 'إنشاء حساب')

@section('content')
    <div class="auth-shell">
        <section aria-labelledby="register-title" class="auth-card">
            {{-- علامة الهوية على الجوال (اللوحة الجانبية مخفية تحت lg) --}}
            <div class="mb-5 flex items-center gap-2 lg:hidden" aria-hidden="true">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-brand-500 to-brand-700 text-sm font-extrabold text-white shadow-sm shadow-brand-600/30">م</span>
                <span class="text-lg font-bold text-brand-700">مسارك</span>
            </div>

            <div class="auth-head">
                <span class="auth-head-icon">
                    <x-ui.icon name="user-plus" class="h-5 w-5" />
                </span>
                <h1 id="register-title" class="text-2xl font-bold">إنشاء حساب</h1>
            </div>

            <form method="POST" action="{{ route('register') }}" class="auth-form mt-6 space-y-4">
                @csrf

                <div>
                    <label for="name" class="field-label">الاسم</label>
                    <input
                        id="name" name="name" type="text"
                        value="{{ old('name') }}"
                        required autofocus autocomplete="name"
                        class="field-input"
                        @error('name') aria-invalid="true" aria-describedby="name-error" @enderror
                    >
                    @error('name')
                        <p id="name-error" class="mt-1 text-sm text-danger-700">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="field-label">البريد الإلكتروني</label>
                    <input
                        id="email" name="email" type="email"
                        value="{{ old('email') }}"
                        required autocomplete="email"
                        class="field-input ltr-text"
                        @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                    >
                    @error('email')
                        <p id="email-error" class="mt-1 text-sm text-danger-700">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="field-label">كلمة المرور (8 محارف على الأقل)</label>
                    <div class="field-wrap">
                        <input
                            id="password" name="password" type="password"
                            required autocomplete="new-password"
                            class="field-input field-input-with-toggle ltr-text"
                            @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                        >
                        <button type="button" class="field-toggle" data-password-toggle data-target="password"
        aria-pressed="false" aria-label="إظهار كلمة المرور">
    <svg data-icon-show class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
    </svg>
    <svg data-icon-hide class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
    </svg>
</button>
                    </div>
                    @error('password')
                        <p id="password-error" class="mt-1 text-sm text-danger-700">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="field-label">تأكيد كلمة المرور</label>
                    <div class="field-wrap">
                        <input
                            id="password_confirmation" name="password_confirmation" type="password"
                            required autocomplete="new-password"
                            class="field-input field-input-with-toggle ltr-text"
                        >
                        <button type="button" class="field-toggle" data-password-toggle data-target="password_confirmation"
        aria-pressed="false" aria-label="إظهار كلمة المرور">
    <svg data-icon-show class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
    </svg>
    <svg data-icon-hide class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
    </svg>
</button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-full">إنشاء الحساب</button>
            </form>

            <p class="mt-4 text-center text-sm text-slate-600">
                لديك حساب؟
                <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center font-medium text-brand-700 underline-offset-4 hover:underline">سجل الدخول</a>
            </p>
        </section>

        <aside class="auth-panel" aria-hidden="true">
    <span class="auth-panel-icon">
        <svg class="h-7 w-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3M4.5 19.5a7.5 7.5 0 0 1 15 0M12 12a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9Z" />
        </svg>
    </span>
    <h2 class="text-3xl font-bold">مساحتك الخاصة تبدأ هنا</h2>
    <p class="mt-4 text-lg text-brand-50">أنشئ حسابك لتستكشف دليل التخصصات وتحفظ نتائج تقييمك في مكان واحد.</p>
</aside>
    </div>
@endsection