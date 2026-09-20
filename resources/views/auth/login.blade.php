@extends('layouts.app')

@section('title', 'تسجيل الدخول')

@section('content')
    <section aria-labelledby="login-title" class="mx-auto max-w-md">
        <h1 id="login-title" class="text-2xl font-bold">تسجيل الدخول</h1>

        @if (session('status'))
            <p class="mt-4 rounded-md border border-slate-200 bg-white p-3 text-sm">
                {{ session('status') }}
            </p>
        @endif

        <form method="POST" action="{{ route('login') }}"
              class="mt-6 space-y-4 rounded-lg border border-slate-200 bg-white p-6">
            @csrf

            <div>
                <label for="email" class="mb-1 block font-medium">البريد الإلكتروني</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    autocomplete="email"
                    dir="ltr"
                    class="w-full rounded-md border border-slate-300 px-3 py-2 text-left"
                >
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="mb-1 block font-medium">كلمة المرور</label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    autocomplete="current-password"
                    dir="ltr"
                    class="w-full rounded-md border border-slate-300 px-3 py-2 text-left"
                >
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                class="w-full rounded-md bg-slate-900 px-4 py-2 font-bold text-white hover:bg-slate-700"
            >
                دخول
            </button>
        </form>

        <p class="mt-4 text-center text-sm text-slate-600">
            <a href="{{ route('password.request') }}"
               class="font-medium text-brand-700 hover:underline">
                نسيت كلمة المرور؟
            </a>

            <span class="mx-2">·</span>

            <a href="{{ route('register') }}"
               class="font-medium text-brand-700 hover:underline">
                إنشاء حساب
            </a>
        </p>
    </section>
@endsection