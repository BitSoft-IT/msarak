@props(['level' => 2, 'id' => null])

@php
    $tag = 'h'.max(1, min(6, (int) $level));
    $styles = [
        1 => 'text-3xl leading-[1.35] font-bold sm:text-4xl',
        2 => 'text-2xl leading-[1.4] font-bold sm:text-3xl',
        3 => 'text-xl leading-[1.45] font-semibold sm:text-2xl',
        4 => 'text-lg leading-[1.45] font-semibold',
        5 => 'text-base leading-[1.65] font-semibold',
        6 => 'text-sm leading-[1.65] font-semibold',
    ];
@endphp

<{{ $tag }} @if ($id) id="{{ $id }}" @endif {{ $attributes->class([$styles[$tag[1]] ?? $styles[2]]) }}>
    {{ $slot }}
</{{ $tag }}>
