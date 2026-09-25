@props([
    'name' => null,
    'invalid' => false,
])

@php
    if (!$invalid && $name && isset($errors)) {
        $invalid = $errors->has($name);
    }

    $rows = $attributes->get('rows', 4);
    $attributes = $attributes->except('rows');
@endphp

<textarea
    @if ($name) name="{{ $name }}" @endif
    rows="{{ $rows }}"
    @if ($invalid) aria-invalid="true" @if($attributes->get('id')) aria-describedby="{{ $attributes->get('id') }}-error" @endif @endif
    {{ $attributes->class([
        'block w-full rounded-lg border bg-white px-3 py-2 text-base text-slate-900',
        'placeholder:text-slate-500 focus:border-brand-600',
        $invalid ? 'border-danger-600' : 'border-slate-300',
    ]) }}
>{{ $slot }}</textarea>
