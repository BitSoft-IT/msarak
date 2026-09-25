@props([
    'name' => null,
    'type' => 'text',
    'invalid' => false,
])

@php
    $errorMessage = null;
    if (!$invalid && $name && isset($errors)) {
        $invalid = $errors->has($name);
    }
@endphp

<input
    @if ($name) name="{{ $name }}" @endif
    type="{{ $type }}"
    @if ($invalid) aria-invalid="true" @if($attributes->get('id')) aria-describedby="{{ $attributes->get('id') }}-error" @endif @endif
    {{ $attributes->class([
        'block min-h-11 w-full rounded-lg border bg-white px-3 py-2 text-base text-slate-900',
        'placeholder:text-slate-500 focus:border-brand-600',
        $invalid ? 'border-danger-600' : 'border-slate-300',
    ]) }}
>
