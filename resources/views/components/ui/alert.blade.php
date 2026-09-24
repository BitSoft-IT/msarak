@props(['variant' => 'info'])

@php
    $variants = [
        'success' => ['class' => 'border-success-300 bg-success-50 text-success-700', 'role' => 'status'],
        'warning' => ['class' => 'border-warning-300 bg-warning-50 text-warning-700', 'role' => 'status'],
        'danger' => ['class' => 'border-danger-300 bg-danger-50 text-danger-700', 'role' => 'alert'],
        'info' => ['class' => 'border-brand-200 bg-brand-50 text-brand-800', 'role' => 'status'],
    ];

    $variant = $variants[$variant] ?? $variants['info'];
@endphp

<div role="{{ $variant['role'] }}" {{ $attributes->class(['rounded-lg border-s-4 p-4 text-base', $variant['class']]) }}>
    {{ $slot }}
</div>
