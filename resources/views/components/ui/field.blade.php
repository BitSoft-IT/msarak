@props([
    'name' => null,
    'label' => null,
    'for' => null,
    'hint' => null,
    'error' => null,
])

@php
    $id = $for ?: $name;
    $errorMessage = $error ?? ($name && isset($errors) ? $errors->first($name) : null);
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'space-y-1.5']) }}>
    @if ($label)
        <label @if ($id) for="{{ $id }}" @endif class="block text-sm font-semibold text-slate-800">{{ $label }}</label>
    @endif

    {{ $slot }}

    @if ($hint && !$errorMessage)
        <p class="text-sm text-slate-600">{{ $hint }}</p>
    @endif

    @if ($errorMessage)
        @if ($id)
            <p id="{{ $id }}-error" class="text-sm text-danger-700">{{ $errorMessage }}</p>
        @else
            <p class="text-sm text-danger-700">{{ $errorMessage }}</p>
        @endif
    @endif
</div>
