@props(['circle' => false])

<div role="status" aria-hidden="true" {{ $attributes->class([
    'animate-pulse bg-slate-200',
    $circle ? 'rounded-full' : 'rounded-lg',
]) }}></div>
