@props(['name' => null, 'label' => null])

@php
    // أيقونات SVG مضمّنة (بلا مكتبة — §4). الأسهم الاتجاهية تُنعكس في RTL عبر rtl:rotate-180.
    // يوضع المسار داخل <svg> من الـslot عند الحاجة لأيقونة غير معرّفة هنا.
    $icons = [
        'arrow-start' => ['d' => 'M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18', 'rtl' => true],
        'arrow-end' => ['d' => 'M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3', 'rtl' => true],
        'chevron-end' => ['d' => 'm8.25 4.5 7.5 7.5-7.5 7.5', 'rtl' => true],
        'chevron-start' => ['d' => 'M15.75 19.5 8.25 12l7.5-7.5', 'rtl' => true],
        'check' => ['d' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z', 'rtl' => false],
        'plus' => ['d' => 'M12 4.5v15m7.5-7.5h-15', 'rtl' => false],
        'x-mark' => ['d' => 'M6 18 18 6M6 6l12 12', 'rtl' => false],
        'lock' => ['d' => 'M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z', 'rtl' => false],
        'user-plus' => ['d' => 'M18 7.5v3m0 0v3m0-3h3m-3 0h-3M4.5 19.5a7.5 7.5 0 0 1 15 0M12 12a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9Z', 'rtl' => false],
        'envelope' => ['d' => 'M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75', 'rtl' => false],
        'info' => ['d' => 'M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z', 'rtl' => false],
        'warning' => ['d' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z', 'rtl' => false],
        'clock' => ['d' => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z', 'rtl' => false],
    ];

    $icon = $name && isset($icons[$name]) ? $icons[$name] : null;
@endphp

@if ($icon)
    <svg class="{{ $attributes->get('class', 'h-5 w-5') }} @if ($icon['rtl']) rtl:rotate-180 @endif" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
        @if ($label) role="img" aria-label="{{ $label }}" @else aria-hidden="true" @endif>
        <path d="{{ $icon['d'] }}" />
    </svg>
@else
    {{-- أيقونة مخصصة: يُمرَّر المسار عبر الـslot --}}
    <svg {{ $attributes->except('name', 'label')->merge(['class' => 'h-5 w-5']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
        @if ($label) role="img" aria-label="{{ $label }}" @else aria-hidden="true" @endif>{{ $slot }}</svg>
@endif
