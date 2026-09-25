@props([
    'name' => null,
    'invalid' => false,
])

@php
    if (!$invalid && $name && isset($errors)) {
        $invalid = $errors->has($name);
    }
@endphp

<select
    @if ($name) name="{{ $name }}" @endif
    @if ($invalid) aria-invalid="true" @if($attributes->get('id')) aria-describedby="{{ $attributes->get('id') }}-error" @endif @endif
    {{ $attributes->class([
        'block min-h-11 w-full rounded-lg border bg-white px-3 py-2 text-base text-slate-900',
        'focus:border-brand-600',
        $invalid ? 'border-danger-600' : 'border-slate-300',
    ]) }}
>{{ $slot }}</select>
