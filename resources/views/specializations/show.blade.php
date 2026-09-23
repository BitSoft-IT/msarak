@extends('layouts.app')

@section('title', $specialization['name'] ?? 'تخصص')

@section('content')
    <article class="max-w-3xl">
        <a href="{{ route('specializations.index') }}" class="text-sm text-slate-500 underline">
            ← رجوع لدليل التخصصات
        </a>

        <h1 class="mt-4 text-3xl font-bold leading-tight sm:text-4xl">
            {{ $specialization['name'] ?? 'بدون اسم' }}
        </h1>
        <p class="mt-4 text-lg leading-relaxed text-slate-700">
            {{ $specialization['description'] ?? '' }}
        </p>

        @if (!empty($specialization['study_nature']))
            <section class="mt-8">
                <h2 class="text-lg font-bold">طبيعة الدراسة</h2>
                <p class="mt-2 text-slate-700">{{ $specialization['study_nature'] }}</p>
            </section>
        @endif

        @if (!empty($specialization['key_activities']))
            <section class="mt-8">
                <h2 class="text-lg font-bold">أبرز الأنشطة</h2>
                <ul class="mt-2 list-disc space-y-1 pr-5 text-slate-700">
                    @foreach ($specialization['key_activities'] as $activity)
                        <li>{{ $activity }}</li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if (!empty($specialization['required_skills']))
            <section class="mt-8">
                <h2 class="text-lg font-bold">المهارات المطلوبة</h2>
                <ul class="mt-2 list-disc space-y-1 pr-5 text-slate-700">
                    @foreach ($specialization['required_skills'] as $skill)
                        <li>{{ $skill }}</li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if (!empty($specialization['career_paths']))
            <section class="mt-8">
                <h2 class="text-lg font-bold">المسارات الوظيفية</h2>
                <ul class="mt-2 list-disc space-y-1 pr-5 text-slate-700">
                    @foreach ($specialization['career_paths'] as $path)
                        <li>{{ $path }}</li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if (!empty($specialization['id']))
            <a href="{{ route('specializations.compare', ['first' => $specialization['id']]) }}"
               class="mt-8 inline-block rounded-lg bg-brand-600 px-4 py-2 font-semibold text-white">
                قارن هذا التخصص
            </a>
        @endif
    </article>
@endsection