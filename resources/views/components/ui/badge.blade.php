@props(['variant' => 'neutral'])

@php
    $variants = [
        'neutral' => 'bg-slate-100 text-slate-800',
        'brand' => 'bg-brand-50 text-brand-700',
        'success' => 'bg-success-50 text-success-700',
        'warning' => 'bg-warning-50 text-warning-700',
        'danger' => 'bg-danger-50 text-danger-700',
        'accent' => 'bg-accent-100 text-accent-600',
    ];
@endphp

<span {{ $attributes->class([
    'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-bold',
    $variants[$variant] ?? $variants['neutral'],
]) }}>{{ $slot }}</span>
