@extends('layouts.app')

@section('title', 'إصدار التقييم — مسودة')

@section('content')
@php
    // F-05: عرض فقط — الأسئلة وخياراتها كما في قاعدة البيانات بترتيب position.
    // الأزرار المتاحة تُقفل على المسودة؛ التفويض الفعلي في role:admin وB-05.
    $isDraft = $version->status === 'draft';
    $badges = [
        'draft' => ['neutral', 'مسودة'],
        'active' => ['success', 'نشط'],
        'retired' => ['warning', 'مؤرشف'],
    ];
    $badge = $badges[$version->status] ?? ['neutral', $version->status];
    $count = $version->questions->count();
    $arDate = static fn ($d): string => $d ? \Illuminate\Support\Carbon::parse($d)->locale('ar')->translatedFormat('j F Y') : '';
@endphp

<div class="mx-auto max-w-3xl">

    <a href="{{ route('admin.assessment-versions.index') }}" class="inline-flex min-h-11 items-center gap-1.5 text-sm font-medium text-brand-700 hover:text-brand-800">
        <x-ui.icon name="chevron-end" class="h-4 w-4" />
        كل الإصدارات
    </a>

    <header class="card-surface mt-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 text-base font-extrabold text-white shadow-sm shadow-brand-600/30"
                      aria-hidden="true">v{{ $version->version_number }}</span>
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">الإصدار {{ $version->version_number }}</h1>
                    <div class="mt-1.5 flex flex-wrap items-center gap-2 text-sm text-slate-500">
                        <x-ui.badge :variant="$badge[0]">{{ $badge[1] }}</x-ui.badge>
                        <span>{{ $count }} من 18 سؤالًا</span>
                        @if ($version->published_at)
                            <span>· نُشر في {{ $arDate($version->published_at) }}</span>
                        @endif
                    </div>
                </div>
            </div>

            @if ($isDraft)
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('admin.assessment-versions.questions.create', $version) }}" class="btn btn-primary btn-pill text-sm">إضافة سؤال</a>

                    {{-- Publish — التأكيد بموافقة صريحة (checkbox إلزامي) بلا JS --}}
                    <form method="POST" action="{{ route('admin.assessment-versions.publish', $version) }}"
                         class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50/60 px-3 py-2">
                        @csrf
                        <label class="flex items-center gap-2 text-xs font-semibold text-slate-700" for="publish-confirm">
                            <input id="publish-confirm" type="checkbox" required
                                   class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-600/40">
                            أوافق على النشر
                        </label>
                        <button type="submit" class="btn btn-success btn-pill text-sm">نشر الإصدار</button>
                    </form>
                </div>
            @endif
        </div>

        {{-- شريط اكتمال الأسئلة — عرض بصري، القواعد في B-05 --}}
        <div class="mt-5">
            <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100"
                 role="img" aria-label="اكتمال الأسئلة: {{ $count }} من 18">
                <div class="h-full rounded-full bg-brand-600" style="width: {{ min(100, round($count / 18 * 100)) }}%"></div>
            </div>
        </div>
    </header>

    <section aria-labelledby="questions-title" class="mt-6">
        <h2 id="questions-title" class="text-lg font-bold text-slate-900">أسئلة الإصدار</h2>

        @if ($version->questions->isEmpty())
            <x-ui.empty-state class="mt-4" icon="info"
                title="لم تُضف أسئلة بعد"
                description="{{ $isDraft ? 'ابدأ بإضافة السؤال الأول — الأداة تتطلب 18 سؤالًا، لكل سؤال 4 تصرفات.' : 'لا توجد أسئلة محفوظة لهذا الإصدار.' }}">
                @if ($isDraft)
                    <a href="{{ route('admin.assessment-versions.questions.create', $version) }}" class="btn btn-primary mt-2">إضافة سؤال</a>
                @endif
            </x-ui.empty-state>
        @else
            @php $domainLabels = config('riasec.domains'); @endphp
            <ul class="mt-4 space-y-4">
                @foreach ($version->questions as $question)
                    <li class="card-surface p-5">
                        {{-- ترويسة البطاقة: الرقم والعنوان + إجراءات المسودة --}}
                        <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-2">
                            <div class="flex items-center gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-brand-500 to-brand-700 text-sm font-extrabold text-white shadow-sm shadow-brand-600/25"
                                      aria-hidden="true">{{ $question->position }}</span>
                                <h3 class="text-sm font-bold text-slate-900">السؤال {{ $question->position }}</h3>
                            </div>
                            @if ($isDraft)
                                <div class="flex shrink-0 flex-wrap items-center justify-end gap-x-2.5 gap-y-1.5">
                                    <a href="{{ route('admin.assessment-versions.questions.edit', [$version, $question]) }}"
                                       class="btn btn-soft btn-pill text-sm">تعديل<span class="sr-only"> السؤال {{ $question->position }}</span></a>
                                    {{-- حذف بحاجز تأكيد: checkbox إلزامي يمنع الإرسال بـHTML أصلي،
                                         والزرق خامل بصريًا حتى التأكيد عبر peer (بلا JS) — نمط «أوافق على النشر» --}}
                                    <form method="POST" action="{{ route('admin.assessment-versions.questions.destroy', [$version, $question]) }}"
                                          class="flex items-center gap-2">
                                        @csrf @method('DELETE')
                                        <input type="checkbox" id="confirm-delete-{{ $question->id }}" required
                                               class="peer h-4 w-4 shrink-0 cursor-pointer rounded border-slate-300 accent-danger-600 focus:ring-danger-600/40">
                                        <label for="confirm-delete-{{ $question->id }}"
                                               class="cursor-pointer select-none text-xs font-semibold text-slate-500 transition-colors peer-checked:text-danger-700">تأكيد</label>
                                        <button type="submit"
                                                class="btn btn-pill text-sm font-semibold text-danger-700 hover:bg-danger-50 peer-not-checked:pointer-events-none peer-not-checked:opacity-50">حذف<span class="sr-only"> السؤال {{ $question->position }}</span></button>
                                    </form>
                                </div>
                            @endif
                        </div>

                        {{-- الموقف --}}
                        <p class="mt-4 text-[15px] leading-relaxed text-slate-900">{{ $question->scenario }}</p>

                        {{-- التصرفات --}}
                        <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50/60 p-3.5">
                            <p class="text-xs font-bold text-slate-500">التصرفات المتاحة</p>
                            <ul class="mt-2 space-y-2">
                                @foreach ($question->questionOptions as $option)
                                    @php $codeLabel = $domainLabels[$option->riasec_code] ?? $option->riasec_code; @endphp
                                    <li class="flex items-center gap-2.5 text-sm text-slate-700">
                                        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded bg-white text-[11px] font-bold text-slate-500 ring-1 ring-inset ring-slate-200"
                                              aria-hidden="true">{{ $option->position }}</span>
                                        <span class="min-w-0 flex-1">{{ $option->option_text }}</span>
                                        <span class="shrink-0 rounded-full bg-brand-50 px-2 py-0.5 font-mono text-[11px] font-bold text-brand-700"
                                              title="{{ $codeLabel }}">{{ $option->riasec_code }}<span class="ms-1 hidden font-sans font-semibold sm:inline">· {{ $codeLabel }}</span></span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

</div>
@endsection
