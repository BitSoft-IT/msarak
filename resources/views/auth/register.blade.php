@extends('layouts.app')

@section('title', 'إنشاء حساب')

@section('content')
    <div class="auth-shell">
        <section aria-labelledby="register-title" class="auth-card">
            <h1 id="register-title" class="text-2xl font-bold">إنشاء حساب</h1>

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
                        <p id="name-error" class="mt-1 text-sm text-red-700">{{ $message }}</p>
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
                        <p id="email-error" class="mt-1 text-sm text-red-700">{{ $message }}</p>
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
                                aria-pressed="false" aria-label="إظهار كلمة المرور">👁️</button>
                    </div>
                    @error('password')
                        <p id="password-error" class="mt-1 text-sm text-red-700">{{ $message }}</p>
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
                                aria-pressed="false" aria-label="إظهار كلمة المرور">👁️</button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-full">إنشاء الحساب</button>
            </form>

            <p class="mt-4 text-center text-sm text-slate-600">
                لديك حساب؟
                <a href="{{ route('login') }}" class="font-medium text-brand-700 hover:underline">سجل الدخول</a>
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