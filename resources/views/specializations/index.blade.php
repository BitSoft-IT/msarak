@extends('layouts.app')

@section('title', 'دليل التخصصات')

@section('content')
    <section aria-labelledby="specializations-title" class="mx-auto max-w-5xl">
        {{-- رأس الصفحة — هيرو بنفس لغة الهوية (تدرّج + هالات blur) --}}
        <div class="relative overflow-hidden rounded-card border border-brand-100/70 bg-gradient-to-br from-white via-brand-50/50 to-brand-100/40 p-6 shadow-card sm:p-8">
            <div class="pointer-events-none absolute -end-16 -top-16 h-56 w-56 rounded-full bg-brand-400/15 blur-3xl" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -start-12 bottom-0 h-40 w-40 rounded-full bg-accent-500/10 blur-3xl" aria-hidden="true"></div>

            <div class="relative z-10 flex flex-wrap items-center justify-between gap-4">
                <div class="max-w-xl">
                    <h1 id="specializations-title" class="text-3xl font-extrabold leading-tight text-slate-900 sm:text-4xl">
                        دليل التخصصات
                    </h1>
                    <p class="mt-4 text-lg leading-relaxed text-slate-700">
                        تصفح التخصصات الأكاديمية المتاحة، واطّلع على تفاصيل كل تخصص، أو قارن بين تخصصين.
                    </p>
                </div>
                {{-- أيقونة الدليل (شبكة) --}}
                <span class="hidden shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-500 to-brand-700 p-5 text-white shadow-lg shadow-brand-600/25 sm:inline-flex" aria-hidden="true">
                    <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="4" y="4" width="7" height="7" rx="1.5" />
                        <rect x="13" y="4" width="7" height="7" rx="1.5" />
                        <rect x="4" y="13" width="7" height="7" rx="1.5" />
                        <rect x="13" y="13" width="7" height="7" rx="1.5" />
                    </svg>
                </span>
            </div>
        </div>

        {{-- شبكة التخصصات: بطاقة معرض بأيقونة دلالية وشريط علوي موحد الهوية --}}
        <div class="mt-8 grid gap-6 sm:grid-cols-2">
            @foreach ($specializations as $specialization)
                @php
                    $specName = $specialization['name'] ?? 'بدون اسم';
                @endphp
                <article class="card-surface card-lift group flex flex-col overflow-hidden p-0">
                    <div class="h-1.5 w-full bg-gradient-to-l from-brand-500 via-brand-600 to-accent-500/80 opacity-50 transition-opacity duration-200 group-hover:opacity-100" aria-hidden="true"></div>
                    <div class="flex flex-1 flex-col p-6">
                        <div class="flex items-start justify-between gap-3">
                            <x-ui.spec-icon :id="$specialization['id'] ?? null" :name="$specName" />
                            <span class="text-xs font-bold text-slate-400" aria-hidden="true">{{ $loop->iteration }}</span>
                        </div>
                        <h2 class="mt-4 text-xl font-bold text-slate-900">{{ $specName }}</h2>
                        <p class="mt-2 flex-1 leading-relaxed text-slate-600 line-clamp-3">
                            {{ $specialization['description']??'لا يوجد تفاصيل' }}
                        </p>
                        <div class="mt-4 flex flex-wrap items-center gap-3 border-t border-slate-100 pt-4">
                            <a href="{{ route('specializations.show', $specialization['id'] ?? '') }}"
                               class="btn btn-primary btn-pill text-sm">
                                التفاصيل
                            </a>
                            <button type="button"
                                    class="compare-toggle btn btn-soft btn-pill text-sm"
                                    data-id="{{ $specialization['id'] ?? '' }}"
                                    data-name="{{ $specialization['name'] ?? '' }}">
                                <x-ui.icon name="plus" class="h-4 w-4 pointer-events-none" />
                                أضف للمقارنة
                            </button>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        {{-- حالة الفراغ — تظهر فقط إن لم توجد تخصصات (نص موقت بانتظار اعتماد ملاطف) --}}
        @if (empty($specializations) || count($specializations) === 0)
            <x-ui.empty-state title="لا توجد تخصصات معروضة حاليًا" description="سنضيف التخصصات قريبًا. تابعنا." icon="info" class="mt-8" />
        @endif
    </section>

    {{-- صينية المقارنة: لاصقة أسفل الشاشة تبقى ظاهرة أثناء التصفح (عقد specializations.js: ids + flex|hidden) --}}
    <div id="compare-bar" class="fixed inset-x-4 bottom-4 z-40 mx-auto flex hidden max-w-2xl items-center justify-between gap-4 rounded-card bg-white p-4 text-sm shadow-pop ring-1 ring-brand-200">
        <p class="text-slate-700">
            تم اختيار: <span id="compare-bar-names" class="font-semibold text-brand-800"></span>
        </p>
        <div class="flex shrink-0 items-center gap-3">
            <a id="compare-bar-link" href="#" class="btn btn-primary btn-pill text-sm">
                قارن الآن
            </a>
            <button type="button" id="compare-bar-clear"
                    class="btn btn-secondary btn-pill text-sm hover:border-danger-300 hover:bg-danger-50 hover:text-danger-700">
                <x-ui.icon name="x-mark" class="h-4 w-4 pointer-events-none" />
                إفراغ الاختيار
            </button>
        </div>
    </div>
@endsection
