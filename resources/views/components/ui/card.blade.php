@props(['as' => 'div'])

<{{ $as }} {{ $attributes->class([
    'rounded-card border border-slate-200 bg-white p-6 shadow-card',
]) }}>
    {{ $slot }}
</{{ $as }}>
