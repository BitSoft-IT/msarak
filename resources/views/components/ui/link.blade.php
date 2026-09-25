@props(['href' => '#'])

<a href="{{ $href }}" {{ $attributes->class(['font-medium text-brand-700 underline-offset-2 hover:underline']) }}>{{ $slot }}</a>
