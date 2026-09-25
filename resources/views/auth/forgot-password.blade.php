@extends('layouts.app')

@section('title', 'استعادة كلمة المرور')

@section('content')
    <div class="auth-shell">
        <section aria-labelledby="forgot-title" class="auth-card">
            {{-- علامة الهوية على الجوال (اللوحة الجانبية مخفية تحت lg) --}}
            <div class="mb-5 flex items-center gap-2 lg:hidden" aria-hidden="true">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-brand-500 to-brand-700 text-sm font-extrabold text-white shadow-sm shadow-brand-600/30">م</span>
                <span class="text-lg font-bold text-brand-700">مسارك</span>
            </div>

            <div class="auth-head">
                <span class="auth-head-icon">
                    <x-ui.icon name="envelope" class="h-5 w-5" />
                </span>
                <h1 id="forgot-title" class="text-2xl font-bold">استعادة كلمة المرور</h1>
            </div>
            <p class="mt-2 text-sm text-slate-600">أدخل بريدك الإلكتروني وسنرسل لك رابطًا لإعادة تعيين كلمة المرور.</p>

            <form method="POST" action="{{ route('password.email') }}" class="auth-form mt-6 space-y-4">
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
                        <p id="email-error" class="mt-1 text-sm text-danger-700">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary w-full">إرسال رابط الاستعادة</button>
            </form>

            <p class="mt-4 text-center text-sm text-slate-600">
                <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center font-medium text-brand-700 underline-offset-4 hover:underline">العودة إلى تسجيل الدخول</a>
            </p>
        </section>

        <aside class="auth-panel" aria-hidden="true">
    <span class="auth-panel-icon">
        <svg class="h-7 w-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
        </svg>
    </span>
    <h2 class="text-3xl font-bold">لا تقلق، الأمر شائع</h2>
    <p class="mt-4 text-lg text-brand-50">أدخل بريدك وسنرسل لك رابطًا آمنًا لاستعادة الوصول إلى حسابك.</p>
</aside>
    </div>
@endsection