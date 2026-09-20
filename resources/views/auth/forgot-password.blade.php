@extends('layouts.app')

@section('title', 'استعادة كلمة المرور')

@section('content')
    <section aria-labelledby="forgot-title" class="mx-auto max-w-md">
        <h1 id="forgot-title" class="text-2xl font-bold">استعادة كلمة المرور</h1>

        @if (session('status'))
            <p class="mt-4 rounded-md border border-slate-200 bg-white p-3 text-sm">
                {{ session('status') }}
            </p>
        @endif

        <form method="POST" action="{{ route('password.email') }}"
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

            <button
                type="submit"
                class="w-full rounded-md bg-slate-900 px-4 py-2 font-bold text-white hover:bg-slate-700"
            >
                إرسال رابط الاستعادة
            </button>
        </form>

        <p class="mt-4 text-center text-sm text-slate-600">
            <a href="{{ route('login') }}"
               class="font-medium text-brand-700 hover:underline">
                العودة إلى تسجيل الدخول
            </a>
        </p>
    </section>
@endsection