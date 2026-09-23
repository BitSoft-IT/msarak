@extends('layouts.app')

@section('title', 'مقارنة التخصصات')

@section('content')
    <section aria-labelledby="compare-title" class="max-w-4xl">
        <a href="{{ route('specializations.index') }}" class="text-sm text-slate-500 underline">
            ← رجوع لدليل التخصصات
        </a>

        <h1 id="compare-title" class="mt-4 text-3xl font-bold leading-tight sm:text-4xl">
            مقارنة التخصصات
        </h1>

        <div class="mt-8 grid gap-6 sm:grid-cols-2">
            @foreach ([$first, $second] as $specialization)
                <article class="rounded-2xl border border-slate-200 p-6">
                    <h2 class="text-xl font-bold">{{ $specialization['name'] ?? 'بدون اسم' }}</h2>
                    <p class="mt-2 text-slate-600">{{ $specialization['description'] ?? '' }}</p>
                </article>
            @endforeach
        </div>

        @php
            $rows = [
                'طبيعة الدراسة' => 'study_nature',
                'أبرز الأنشطة' => 'key_activities',
                'المهارات المطلوبة' => 'required_skills',
                'المسارات الوظيفية' => 'career_paths',
            ];
        @endphp

        <div class="mt-8 overflow-x-auto">
            <table class="w-full min-w-[640px] border-collapse text-right">
                <thead>
                    <tr class="border-b border-slate-200">
                        <th class="p-3 font-bold">المحور</th>
                        <th class="p-3 font-bold">{{ $first['name'] ?? 'بدون اسم' }}</th>
                        <th class="p-3 font-bold">{{ $second['name'] ?? 'بدون اسم' }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $label => $field)
                        <tr class="border-b border-slate-100 align-top">
                            <td class="p-3 font-semibold text-slate-600">{{ $label }}</td>
                            @foreach ([$first, $second] as $specialization)
                                <td class="p-3 text-slate-700">
                                    @if (empty($specialization[$field]))
                                        <span class="text-slate-400">—</span>
                                    @elseif (is_array($specialization[$field]))
                                        <ul class="list-disc space-y-1 pr-5">
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
    </section>
@endsection