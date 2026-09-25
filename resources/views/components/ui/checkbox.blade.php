@props([
    'name' => null,
    'label' => null,
    'id' => null,
])

@php
    $id = $id ?: ($name ?: null);
@endphp

<div class="flex items-start gap-2.5">
    <input
        @if ($id) id="{{ $id }}" @endif
        @if ($name) name="{{ $name }}" @endif
        type="checkbox"
        {{ $attributes->class(['mt-1 h-4 w-4 shrink-0 rounded border-slate-300 text-brand-600 focus:ring-brand-600 cursor-pointer']) }}
    >
    @if ($label)
        <label @if ($id) for="{{ $id }}" @endif class="text-sm leading-relaxed text-slate-800">{{ $label }}</label>
    @endif
    {{ $slot }}
</div>
