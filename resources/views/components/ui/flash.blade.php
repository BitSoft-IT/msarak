@props(['message' => null])

@php
    // يقرأ رسائل الجلسة المعتمدة لدى Laravel: success / error / warning / status
    // أول رسالة موجودة تُعرض بنمطها الدلالي. النصوص من الجلسة — لا نصوص ثابتة هنا.
    $found = null;
    foreach (['success', 'error', 'warning', 'status'] as $key) {
        if (session($key)) {
            $found = [$key, session($key)];
            break;
        }
    }

    $variant = match ($found[0] ?? '') {
        'error' => 'danger',
        'warning' => 'warning',
        default => 'success', // success و status رسائل نجاح
    };
@endphp

@if ($found || $slot->isNotEmpty())
    <x-ui.alert variant="{{ $found ? $variant : ($attributes->get('variant', 'info')) }}" {{ $attributes->except('variant') }}>
        {{ $found[1] ?? $slot }}
    </x-ui.alert>
@endif
