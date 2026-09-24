@props([
    'variant' => 'primary',
    'size' => 'md',
    'loading' => false,
])

@php
    $variants = [
        'primary' => 'bg-brand-600 text-white hover:bg-brand-700',
        'secondary' => 'bg-brand-50 text-brand-700 hover:bg-brand-100',
        'outline' => 'border border-slate-300 text-slate-800 hover:bg-slate-100',
        'ghost' => 'text-slate-700 hover:bg-slate-100',
        'danger' => 'bg-danger-600 text-white hover:bg-danger-700',
        'success' => 'bg-success-700 text-white hover:bg-success-800',
    ];

    $sizes = [
        'md' => 'px-5 py-2.5 text-base',
        'lg' => 'px-7 py-3.5 text-lg',
    ];
@endphp

<button {{ $attributes->class([
    'inline-flex min-h-11 items-center justify-center gap-2 rounded-lg font-semibold transition-colors',
    'disabled:pointer-events-none disabled:opacity-50',
    $variants[$variant] ?? $variants['primary'],
    $sizes[$size] ?? $sizes['md'],
]) }} @if ($loading) disabled aria-busy="true" @endif>
    @if ($loading)
        <svg class="h-4 w-4 shrink-0 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z" />
        </svg>
    @endif
    {{ $slot }}
</button>
