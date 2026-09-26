@extends('layouts.app')

@section('title', 'إصدارات التقييم')

@section('content')
@php
    // F-05: قراءة فقط — البيانات والترتيب من صفوف النظام كما هي.
    // التفويض كله في middleware role:admin؛ إخفاء الأزرار هنا تلميح وليس تفويضًا.
    $draft = $versions->firstWhere('status', 'draft');
    $arDate = static fn ($d): string => $d ? \Illuminate\Support\Carbon::parse($d)->locale('ar')->translatedFormat('j F Y') : '';
@endphp

<div class="mx-auto max-w-3xl">

    <header class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold leading-tight text-slate-900">إصدارات التقييم</h1>
            <p class="mt-2 text-base text-slate-700">إدارة مسودات التقييم ونشر الإصدارات المعتمدة.</p>
        </div>

        @unless ($draft)
            {{-- الإنشاء POST — الزر نموذج صغير برسالة Backend نفسها --}}
            <form method="POST" action="{{ route('admin.assessment-versions.store') }}">
                @csrf
                <button type="submit" class="btn btn-primary">إنشاء مسودة جديدة</button>
            </form>
        @else
            <a href="{{ route('admin.assessment-versions.show', $draft) }}" class="btn btn-primary">متابعة المسودة الحالية</a>
        @endunless
    </header>

    @if ($versions->isEmpty())
        <x-ui.empty-state class="mt-6" icon="info"
            title="لا توجد إصدارات بعد"
            description="ابدأ بإنشاء مسودة التقييم الأولى، ثم أضف أسئلتها وانشرها عندما تكتمل." />
    @else
        <ul class="mt-6 space-y-4">
            @foreach ($versions as $item)
                <li>
                    <a href="{{ route('admin.assessment-versions.show', $item) }}"
                       class="card-surface card-lift group flex items-center gap-4 p-5">
                        @php
                            $badge = match ($item->status) {
                                // المسودة هي العنصر الذي يتطلب فعلًا — لون brand ينطق «أكملني»
                                'draft' => ['brand', 'مسودة'],
                                'active' => ['success', 'نشط'],
                                'retired' => ['warning', 'مؤرشف'],
                                default => ['neutral', $item->status],
                            };
                        @endphp
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 text-base font-extrabold text-white shadow-sm shadow-brand-600/30"
                              aria-hidden="true">v{{ $item->version_number }}</span>
                        <span class="min-w-0 flex-1">
                            <span class="flex flex-wrap items-center gap-2">
                                <span class="block text-base font-bold text-slate-900 group-hover:text-brand-700">
                                    الإصدار {{ $item->version_number }}
                                </span>
                                <x-ui.badge :variant="$badge[0]">{{ $badge[1] }}</x-ui.badge>
                            </span>
                            <span class="mt-1 block text-sm text-slate-500">
                                {{ $item->questions_count }} من 18 سؤالًا
                                @if ($item->published_at)
                                    · نُشر في {{ $arDate($item->published_at) }}
                                @endif
                            </span>
                        </span>
                        <x-ui.icon name="chevron-start" class="h-5 w-5 shrink-0 text-slate-400 transition-transform group-hover:-translate-x-0.5 group-hover:text-brand-600" />
                    </a>
                </li>
            @endforeach
        </ul>
    @endif

</div>
@endsection
