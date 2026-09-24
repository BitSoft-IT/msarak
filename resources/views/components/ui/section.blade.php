@props(['labelledby' => null])

<section @if ($labelledby) aria-labelledby="{{ $labelledby }}" @endif {{ $attributes->class(['py-12 lg:py-16']) }}>
    {{ $slot }}
</section>
