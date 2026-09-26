@props(['name' => null, 'label' => null])

@php
    // أيقونات SVG مضمّنة (بلا مكتبة — §4). الأسهم الاتجاهية تُنعكس في RTL عبر rtl:rotate-180.
    // يوضع المسار داخل <svg> من الـslot عند الحاجة لأيقونة غير معرّفة هنا.
    $icons = [
        'arrow-start' => ['d' => 'M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18', 'rtl' => true],
        'arrow-end' => ['d' => 'M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3', 'rtl' => true],
        'chevron-end' => ['d' => 'm8.25 4.5 7.5 7.5-7.5 7.5', 'rtl' => true],
        'chevron-start' => ['d' => 'M15.75 19.5 8.25 12l7.5-7.5', 'rtl' => true],
        'menu' => ['d' => 'M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5', 'rtl' => false],
        'pencil' => ['d' => 'm16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.862 4.487Zm0 0-1.687 1.688', 'rtl' => false],
        'check' => ['d' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z', 'rtl' => false],
        'plus' => ['d' => 'M12 4.5v15m7.5-7.5h-15', 'rtl' => false],
        'x-mark' => ['d' => 'M6 18 18 6M6 6l12 12', 'rtl' => false],
        'lock' => ['d' => 'M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z', 'rtl' => false],
        'user-plus' => ['d' => 'M18 7.5v3m0 0v3m0-3h3m-3 0h-3M4.5 19.5a7.5 7.5 0 0 1 15 0M12 12a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9Z', 'rtl' => false],
        'envelope' => ['d' => 'M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75', 'rtl' => false],
        'info' => ['d' => 'M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z', 'rtl' => false],
        'warning' => ['d' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z', 'rtl' => false],
        'clock' => ['d' => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z', 'rtl' => false],
        'activity' => ['d' => 'M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5m.75-9 3-3 2.148 2.148A12.061 12.061 0 0 1 16.5 7.605', 'rtl' => false],
        'target' => ['d' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0-4.5a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9Zm0-3a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z', 'rtl' => false],    ];

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
