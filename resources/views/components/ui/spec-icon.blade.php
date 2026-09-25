@props([
    'id' => null,
    'name' => null,
    'size' => 'md',
    'part' => 'chip',
    'tone' => 'brand',
])

@php
    // أيقونة دلالية لكل تخصص — Duotone بطبقتين من لون واحد.
    // تصحيح §7.8: الشريحة كانت brand مشبعًا فاللون الوحيد المقروء عليها هو الأبيض (شكوى Assignee).
    // الآن: خلفية فاتحة هادئة + حبر brand-700 — لا لون دلالي لكل تخصص (قرار Alhareith)،
    // والتمييز بشكل الأيقونة وكتلتها. accent يُستخدم كوسم موضع (العمود الثاني) فقط.
    // أي id غير معروف → الحرف-الشعار (fallback).

    $icons = [
        'computer_science' => [
            'f' => 'M4.5 5.5h15a1.5 1.5 0 0 1 1.5 1.5v8.5a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 15.5V7a1.5 1.5 0 0 1 1.5-1.5Z',
            'l' => 'M8.75 9.75 6.25 12l2.5 2.25m6.5-4.5L17.75 12l-2.5 2.25',
        ],
        'human_medicine' => [
            'f' => 'M12 19.5s-6.5-4.1-6.5-8.7c0-2 1.6-3.5 3.5-3.5 1.35 0 2.55.8 3 2 .45-1.2 1.65-2 3-2 1.9 0 3.5 1.5 3.5 3.5 0 4.6-6.5 8.7-6.5 8.7Z',
            'l' => 'M8.75 12.25h1.75l.9-1.6 1.5 3 .8-1.4h1.55',
        ],
        'nursing' => [
            'f' => 'M12 3.75 5.25 6.25V11c0 4.3 2.85 7.2 6.75 9 3.9-1.8 6.75-4.7 6.75-9V6.25L12 3.75Z',
            'l' => 'M12 9.25v5m-2.25-2.5h4.5',
        ],
        'civil_engineering' => [
            'f' => 'M12 4.25 6.9 19.5h10.2L12 4.25Z',
            'l' => 'M9 14.75h6',
        ],
        'architecture' => [
            'f' => 'M12 4.5 3.75 10.75h16.5L12 4.5Z',
            'l' => 'M6.75 11v8.5m5.25-8.5v8.5m5.25-8.5v8.5M4 19.5h16',
        ],
        'business_administration' => [
            'f' => 'M3.75 8.25h16.5v10a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5v-10Z',
            'l' => 'M9.75 8.25V6.5A1.5 1.5 0 0 1 11.25 5h1.5A1.5 1.5 0 0 1 14.25 6.5v1.75M3.75 12.75h16.5',
        ],
        'accounting' => [
            'f' => 'M6 4.5h12a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1v-13a1 1 0 0 1 1-1Z',
            'l' => 'M8.25 7.75h7.5m-7.5 4.5h.008m3.75 0h.008m3.75 0h.008m-7.5 3.75h.008m3.75 0h.008m3.75 0h.008',
        ],
        'law' => [
            'f' => 'M2.75 12.5a2.25 2.25 0 0 0 4.5 0L5 7.5 2.75 12.5Zm14 0a2.25 2.25 0 0 0 4.5 0L19 7.5l-2.25 5Z',
            'l' => 'M12 5.5v13.5m-3.75 0h7.5M5 7.5h14',
        ],
        'graphic_design_multimedia' => [
            'f' => 'M3.75 6.75h14a1.5 1.5 0 0 1 1.5 1.5v7.5a1.5 1.5 0 0 1-1.5 1.5h-14a1.5 1.5 0 0 1-1.5-1.5v-7.5a1.5 1.5 0 0 1 1.5-1.5Z',
            'l' => 'M8.75 9.75v4.5l4.5-2.25-4.5-2.25Z',
        ],
        'english_language_translation' => [
            'f' => 'M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
            'l' => 'M3 12h18M12 3c2.7 2.6 4 5.6 4 9s-1.3 6.4-4 9m0-18c-2.7 2.6-4 5.6-4 9s1.3 6.4 4 9',
        ],
    ];

    $tones = [
        'brand' => [
            'chip' => 'bg-gradient-to-br from-brand-100 to-brand-200 ring-1 ring-inset ring-brand-300/70',
            'fg' => 'text-brand-800',
            'dot' => 'bg-brand-600',
        ],
        'accent' => [
            'chip' => 'bg-gradient-to-br from-accent-100 to-accent-50 ring-1 ring-inset ring-accent-500/40',
            'fg' => 'text-slate-900',
            'dot' => 'bg-accent-500',
        ],
    ];

    $icon = $id && isset($icons[$id]) ? $icons[$id] : null;
    $t = $tones[$tone] ?? $tones['brand'];

    // انقلاب الـhover: طبقة تدرّج داكنة من نفس جهة النبرة تظهر + الحبر يتحول للأبيض.
    // تدرّج CSS لا يُنتقَل بتغيير from/to — لذلك طبقة absolute بـopacity بدل تبديل التدرّج.
    // brand ← brand-500→700؛ accent ← accent-500→600 (أبيض على accent-600 ≈ 3.6:1 ✅).
    $hoverChip = [
        'brand' => 'from-brand-500 to-brand-700',
        'accent' => 'from-accent-500 to-accent-600',
    ][$tone] ?? 'from-brand-500 to-brand-700';
    $fg = $t['fg'] . ' transition-colors group-hover:text-white';

    $sizes = [
        'md' => ['chip' => 'h-11 w-11 rounded-xl', 'icon' => 'h-7 w-7', 'text' => 'text-lg font-extrabold'],
        'lg' => ['chip' => 'h-14 w-14 rounded-2xl', 'icon' => 'h-9 w-9', 'text' => 'text-2xl font-extrabold'],
    ];
    $size = $sizes[$size] ?? $sizes['md'];
@endphp

@if ($part === 'dot')
    <span {{ $attributes->class(['inline-block h-2.5 w-2.5 shrink-0 rounded-full ' . $t['dot']]) }} aria-hidden="true"></span>
@else
    <span class="relative flex shrink-0 items-center justify-center overflow-hidden {{ $size['chip'] }} {{ $t['chip'] }} shadow-xs" aria-hidden="true">
        <span class="absolute inset-0 bg-gradient-to-br {{ $hoverChip }} opacity-0 transition-opacity duration-200 group-hover:opacity-100" aria-hidden="true"></span>
        @if ($icon)
            <svg class="relative {{ $size['icon'] }} {{ $fg }} spec-draw" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="{{ $icon['f'] }}" fill="currentColor" stroke="none" opacity="0.32" />
                <path pathLength="100" d="{{ $icon['l'] }}" />
            </svg>
        @else
            <span class="relative {{ $size['text'] }} {{ $fg }}">{{ mb_substr(trim((string) $name), 0, 1) }}</span>
        @endif
    </span>
@endif
