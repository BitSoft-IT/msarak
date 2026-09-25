@extends('layouts.app')

@section('title', 'سجل نتائجي')

@section('content')
<div class="mx-auto max-w-2xl">

    <header class="mb-6">
        <h1 class="text-3xl font-extrabold leading-tight text-slate-900">سجل نتائجي</h1>
        <p class="mt-2 text-base leading-relaxed text-slate-700">
            نتائج استكشاف الميول التي أتممتها، الأحدث أولًا. كل نتيجة محفوظة كما صدرت وقتها.
        </p>
    </header>

    @if ($results->isEmpty())
        {{-- حالة No Result (F-04) --}}
        <x-ui.empty-state icon="clock"
            title="لا توجد نتائج محفوظة بعد"
            description="لم تُكمل أي تقييم حتى الآن. ابدأ رحلة استكشاف الميول وستظهر نتيجتك هنا مباشرة.">
            <a href="{{ route('assessment.intro') }}" class="btn btn-primary mt-2">ابدأ استكشاف ميولك</a>
        </x-ui.empty-state>
    @else
        <ul class="space-y-4">
            @foreach ($results as $item)
                <li>
                    <a href="{{ route('results.show', $item) }}"
                       class="card-surface card-lift group flex items-center gap-4 p-5">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 text-white shadow-sm shadow-brand-600/30"
                              aria-hidden="true">
                            <x-ui.icon name="check" class="h-5 w-5" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-base font-bold text-slate-900 group-hover:text-brand-700">
                                نتيجة التقييم — {{ $item->created_at?->format('Y-m-d') }}
                            </span>
                            <span class="mt-1 block text-sm text-slate-500">
                                إصدار الأداة {{ $item->scoring_version }} · إصدار الدليل {{ $item->catalog_version }}
                            </span>
                        </span>
                        <x-ui.icon name="chevron-start" class="h-5 w-5 shrink-0 text-slate-400 transition-transform group-hover:-translate-x-0.5 group-hover:text-brand-600" />
                    </a>
                </li>
            @endforeach
        </ul>
    @endif

    <div class="mt-8">
        <a href="{{ route('assessment.intro') }}" class="btn btn-secondary">تقييم جديد</a>
    </div>
</div>
@endsection
