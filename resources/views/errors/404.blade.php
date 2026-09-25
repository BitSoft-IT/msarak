@extends('layouts.app')

@section('title', 'الصفحة غير موجودة')

@section('content')
    <div class="mx-auto flex max-w-md flex-col items-center py-12 text-center sm:py-16">
        <span class="flex h-16 w-16 items-center justify-center rounded-full bg-brand-50 text-brand-700">
            <x-ui.icon name="info" class="h-8 w-8" />
        </span>
        <x-ui.heading :level="1" id="not-found-title" class="mt-6">الصفحة غير موجودة</x-ui.heading>
        <p class="mt-3 text-base leading-relaxed text-slate-600">
            لم نتمكن من العثور على الصفحة التي تبحث عنها.
        </p>
        <div class="mt-8">
            <x-ui.link href="{{ url('/') }}" class="inline-flex min-h-11 items-center rounded-lg px-5 py-2 font-semibold">
                العودة إلى الرئيسية
            </x-ui.link>
        </div>
    </div>
@endsection
