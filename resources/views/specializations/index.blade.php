@extends('layouts.app')

@section('title', 'دليل التخصصات')

@section('content')
    <section aria-labelledby="specializations-title" class="max-w-5xl">
        <h1 id="specializations-title" class="text-3xl font-bold leading-tight sm:text-4xl">
            دليل التخصصات
        </h1>
        <p class="mt-4 text-lg leading-relaxed text-slate-700">
            تصفح التخصصات الأكاديمية المتاحة، واطّلع على تفاصيل كل تخصص، أو قارن بين تخصصين.
        </p>

        <div id="compare-bar" class="mt-6 hidden items-center justify-between gap-4 rounded-xl bg-brand-50 p-4 text-sm">
            <p class="text-slate-700">
                تم اختيار: <span id="compare-bar-names" class="font-semibold"></span>
            </p>
            <div class="flex items-center gap-3">
                <a id="compare-bar-link" href="#" class="rounded-lg bg-brand-600 px-4 py-2 font-semibold text-white">
                    قارن الآن
                </a>
                <button type="button" id="compare-bar-clear" class="text-slate-500 underline">
                    إفراغ الاختيار
                </button>
            </div>
        </div>

        <div class="mt-8 grid gap-6 sm:grid-cols-2">
            @foreach ($specializations as $specialization)
                <article class="rounded-2xl border border-slate-200 p-6">
                    <h2 class="text-xl font-bold">{{ $specialization['name'] ?? 'بدون اسم' }}</h2>
                    <p class="mt-2 text-slate-600 line-clamp-3">
                        {{ $specialization['description']??'لا يوجد تفاصيل' }}
                    </p>
                    <div class="mt-4 flex items-center gap-4 text-sm">
                        <a href="{{ route('specializations.show', $specialization['id'] ?? '') }}"
                           class="font-semibold text-brand-700 underline">
                            التفاصيل
                        </a>
                        <button type="button"
                                class="compare-toggle font-semibold text-slate-600 underline"
                                data-id="{{ $specialization['id'] ?? '' }}"
                                data-name="{{ $specialization['name'] ?? '' }}">
                            أضف للمقارنة
                        </button>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
@endsection