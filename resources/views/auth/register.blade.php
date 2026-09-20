@extends('layouts.app')

@section('title', 'إنشاء حساب')

@section('content')
    <section aria-labelledby="register-title" class="mx-auto max-w-md">
        <h1 id="register-title" class="text-2xl font-bold">إنشاء حساب</h1>

        <form method="POST" action="{{ route('register') }}"
              class="mt-6 space-y-4 rounded-lg border border-slate-200 bg-white p-6">
            @csrf

            <div>
                <label for="name" class="mb-1 block font-medium">الاسم</label>
                <input
                    id="name"
                    name="name"
                    type="text"
                    value="{{ old('name') }}"
                    required
                    autofocus
                    autocomplete="name"
                    class="w-full rounded-md border border-slate-300 px-3 py-2"
                >
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="mb-1 block font-medium">البريد الإلكتروني</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    required
                    autocomplete="email"
                    dir="ltr"
                    class="w-full rounded-md border border-slate-300 px-3 py-2 text-left"
                >
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="mb-1 block font-medium">
                    كلمة المرور (8 محارف على الأقل)
                </label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    autocomplete="new-password"
                    dir="ltr"
                    class="w-full rounded-md border border-slate-300 px-3 py-2 text-left"
                >
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="mb-1 block font-medium">
                    تأكيد كلمة المرور
                </label>
                <input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    required
                    autocomplete="new-password"
                    dir="ltr"
                    class="w-full rounded-md border border-slate-300 px-3 py-2 text-left"
                >
            </div>

            <button
                type="submit"
                class="w-full rounded-md bg-slate-900 px-4 py-2 font-bold text-white hover:bg-slate-700"
            >
                إنشاء الحساب
            </button>
        </form>

        <p class="mt-4 text-center text-sm text-slate-600">
            لديك حساب؟
            <a href="{{ route('login') }}"
               class="font-medium text-brand-700 hover:underline">
                سجل الدخول
            </a>
        </p>
    </section>
@endsection