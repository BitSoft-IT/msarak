@extends('layouts.app')

@section('title', 'انتهت صلاحية الجلسة')

@section('content')
    <div class="mx-auto flex max-w-md flex-col items-center py-12 text-center sm:py-16">
        <span class="flex h-16 w-16 items-center justify-center rounded-full bg-warning-50 text-warning-700">
            <x-ui.icon name="clock" class="h-8 w-8" />
        </span>
        <x-ui.heading :level="1" id="expired-title" class="mt-6">انتهت صلاحية الجلسة</x-ui.heading>
        <p class="mt-3 text-base leading-relaxed text-slate-600">
            انتهت صلاحية هذه الصفحة لأسباب تتعلق بالأمان. أعد المحاولة.
        </p>
        <div class="mt-8">
            <x-ui.link href="{{ url('/') }}" class="inline-flex min-h-11 items-center rounded-lg px-5 py-2 font-semibold">
                العودة إلى الرئيسية
            </x-ui.link>
        </div>
    </div>
@endsection
