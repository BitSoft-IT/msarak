@extends('layouts.app')

@section('title', $specialization['name'] ?? 'تخصص')

@section('content')
    <article class="mx-auto max-w-3xl">
        <a href="{{ route('specializations.index') }}"
           class="inline-flex min-h-11 items-center gap-1.5 text-sm font-medium text-slate-600 transition-colors hover:text-brand-700">
            <x-ui.icon name="chevron-start" class="h-4 w-4" />
            رجوع لدليل التخصصات
        </a>

        <header class="group relative card-surface mt-4 overflow-hidden">
            {{-- هالات زخرفية كما في البطاقات --}}
            <div class="pointer-events-none absolute -end-12 -top-12 h-40 w-40 rounded-full bg-brand-400/10 blur-2xl" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -start-8 bottom-0 h-28 w-28 rounded-full bg-accent-500/10 blur-2xl" aria-hidden="true"></div>

            <div class="relative z-10 flex items-start gap-4">
                {{-- الأيقونة الدلالية: نفس لغة بطاقات الدليل --}}
                <x-ui.spec-icon :id="$specialization['id'] ?? null" :name="$specialization['name'] ?? ''" size="lg" />
                <div>
                    <h1 class="text-3xl font-extrabold leading-tight text-slate-900 sm:text-4xl">
                        {{ $specialization['name'] ?? 'بدون اسم' }}
                    </h1>
                    <p class="mt-4 text-lg leading-relaxed text-slate-700">
                        {{ $specialization['description'] ?? '' }}
                    </p>
                </div>
            </div>
        </header>

        @if (!empty($specialization['study_nature']))
            <section class="card-surface mt-6">
                <h2 class="flex items-center gap-2.5 text-lg font-bold text-slate-900">
                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-700" aria-hidden="true">
                        <x-ui.icon name="info" class="h-4 w-4" />
                    </span>
                    طبيعة الدراسة
                </h2>
                <p class="mt-3 leading-relaxed text-slate-700">{{ $specialization['study_nature'] }}</p>
            </section>
        @endif

        @if (!empty($specialization['key_activities']))
            <section class="card-surface mt-6">
                <h2 class="flex items-center gap-2.5 text-lg font-bold text-slate-900">
                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-700" aria-hidden="true">
                        <x-ui.icon name="check" class="h-4 w-4" />
                    </span>
                    أبرز الأنشطة
                </h2>
                <ul class="mt-3 list-disc space-y-1.5 ps-5 leading-relaxed text-slate-700 marker:text-brand-600">
                    @foreach ($specialization['key_activities'] as $activity)
                        <li>{{ $activity }}</li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if (!empty($specialization['required_skills']))
            <section class="card-surface mt-6">
                <h2 class="flex items-center gap-2.5 text-lg font-bold text-slate-900">
                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-700" aria-hidden="true">
                        <x-ui.icon name="check" class="h-4 w-4" />
                    </span>
                    المهارات المطلوبة
                </h2>
                <ul class="mt-3 list-disc space-y-1.5 ps-5 leading-relaxed text-slate-700 marker:text-brand-600">
                    @foreach ($specialization['required_skills'] as $skill)
                        <li>{{ $skill }}</li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if (!empty($specialization['career_paths']))
            <section class="card-surface mt-6">
                <h2 class="flex items-center gap-2.5 text-lg font-bold text-slate-900">
                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-700" aria-hidden="true">
                        <x-ui.icon name="arrow-end" class="h-4 w-4" />
                    </span>
                    المسارات الوظيفية
                </h2>
                <ul class="mt-3 list-disc space-y-1.5 ps-5 leading-relaxed text-slate-700 marker:text-brand-600">
                    @foreach ($specialization['career_paths'] as $path)
                        <li>{{ $path }}</li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if (!empty($specialization['id']))
            <div class="mt-8">
                <button type="button"
                        class="compare-toggle btn btn-primary btn-pill min-w-40 text-sm"
                        data-id="{{ $specialization['id'] }}"
                        data-name="{{ $specialization['name'] ?? '' }}"
                        data-redirect="{{ route('specializations.index') }}">
                    <x-ui.icon name="plus" class="h-4 w-4" />
                    قارن هذا التخصص
                </button>
            </div>
        @endif
    </article>
@endsection
