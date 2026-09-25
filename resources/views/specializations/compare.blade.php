@extends('layouts.app')

@section('title', 'مقارنة التخصصات')

@section('content')
    <section aria-labelledby="compare-title" class="mx-auto max-w-4xl">
        <a href="{{ route('specializations.index') }}"
           class="inline-flex min-h-11 items-center gap-1.5 text-sm font-medium text-slate-600 transition-colors hover:text-brand-700">
            <x-ui.icon name="chevron-start" class="h-4 w-4" />
            رجوع لدليل التخصصات
        </a>

        <h1 id="compare-title" class="mt-4 text-3xl font-extrabold leading-tight text-slate-900 sm:text-4xl">
            مقارنة التخصصات
        </h1>

        {{-- لغة المبارزة: شارة «مقابل» على كل العروض — فاصلة أفقية بين البطاقتين على الجوال، ومتمركزة على المكتب --}}
        <div class="relative mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 sm:gap-6">
            <article class="card-surface group relative order-1 overflow-hidden pt-7">
                <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-l from-brand-500 to-brand-700" aria-hidden="true"></div>
                <div class="flex items-center gap-3.5">
                    <x-ui.spec-icon :id="$first['id'] ?? null" :name="$first['name'] ?? ''" />
                    <h2 class="text-xl leading-snug font-bold text-slate-900">{{ $first['name'] ?? 'بدون اسم' }}</h2>
                </div>
                <p class="mt-3 leading-relaxed text-slate-600">{{ $first['description'] ?? '' }}</p>
            </article>

            {{-- الشارة: في تدفق الشبكة على الجوال (بين البطاقتين)، ومطلقة متوسطة على المكتب --}}
            <div class="relative z-10 order-2 flex items-center gap-3 sm:absolute sm:inset-x-0 sm:top-1/2 sm:flex-none sm:-translate-y-1/2 sm:justify-center sm:pointer-events-none" aria-hidden="true">
                <span class="h-px flex-1 bg-slate-200 sm:hidden" aria-hidden="true"></span>
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-brand-600 to-brand-800 text-xs font-extrabold text-white shadow-pop ring-4 ring-slate-50">مقابل</span>
                <span class="h-px flex-1 bg-slate-200 sm:hidden" aria-hidden="true"></span>
            </div>

            <article class="card-surface group relative order-3 overflow-hidden pt-7">
                <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-l from-accent-500 to-accent-600" aria-hidden="true"></div>
                <div class="flex items-center gap-3.5">
                    <x-ui.spec-icon :id="$second['id'] ?? null" :name="$second['name'] ?? ''" tone="accent" />
                    <h2 class="text-xl leading-snug font-bold text-slate-900">{{ $second['name'] ?? 'بدون اسم' }}</h2>
                </div>
                <p class="mt-3 leading-relaxed text-slate-600">{{ $second['description'] ?? '' }}</p>
            </article>
        </div>

        @php
            $rows = [
                'طبيعة الدراسة' => 'study_nature',
                'أبرز الأنشطة' => 'key_activities',
                'المهارات المطلوبة' => 'required_skills',
                'المسارات الوظيفية' => 'career_paths',
            ];
        @endphp

        {{-- جدول المبارزة: تلوين عمودي خفيف يعكس هوية كل جهة + دلالات صف/عمود --}}
        <div class="card-surface mt-8 overflow-hidden p-0">
            <div class="table-scroll-fade overflow-x-auto">
                <table class="w-full min-w-160 border-separate border-spacing-0 text-start">
                    <thead>
                        <tr>
                            <th scope="col" class="table-sticky-edge sticky start-0 z-20 w-32 border-b border-slate-200 bg-slate-50 p-4 text-sm font-bold text-slate-500 sm:w-40">المحور</th>
                            <th scope="col" class="border-b border-slate-200 bg-brand-50/70 p-4">
                                <span class="inline-flex items-center gap-2 font-bold text-slate-900">
                                    <x-ui.spec-icon :id="$first['id'] ?? null" part="dot" />
                                    {{ $first['name'] ?? 'بدون اسم' }}
                                </span>
                            </th>
                            <th scope="col" class="border-b border-slate-200 bg-accent-50 p-4">
                                <span class="inline-flex items-center gap-2 font-bold text-slate-900">
                                    <x-ui.spec-icon :id="$second['id'] ?? null" part="dot" tone="accent" />
                                    {{ $second['name'] ?? 'بدون اسم' }}
                                </span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="[&>tr:last-child>*]:border-b-0">
                        @foreach ($rows as $label => $field)
                            <tr class="align-top">
                                <th scope="row" class="table-sticky-edge sticky start-0 z-10 border-b border-slate-100 bg-slate-50 p-4 text-sm leading-relaxed font-semibold text-slate-500">{{ $label }}</th>
                                @foreach ([$first, $second] as $column => $specialization)
                                    <td class="border-b border-slate-100 p-4 leading-relaxed text-slate-700 {{ $column === 0 ? 'bg-brand-50/30' : 'bg-accent-50/40' }}">
                                        @if (empty($specialization[$field]))
                                            <span class="text-slate-300">—</span>
                                        @elseif (is_array($specialization[$field]))
                                            <ul class="list-disc space-y-1.5 ps-5 {{ $column === 0 ? 'marker:text-brand-600' : 'marker:text-accent-600' }}">
                                                @foreach ($specialization[$field] as $item)
                                                    <li>{{ $item }}</li>
                                                @endforeach
                                            </ul>
                                        @else
                                            {{ $specialization[$field] }}
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
