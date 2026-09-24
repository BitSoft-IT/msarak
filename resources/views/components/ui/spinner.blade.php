@props(['label' => null])

@if ($label)
    <span role="status" class="inline-flex items-center">
        <svg class="{{ $attributes->get('class', 'h-5 w-5') }} animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z" />
        </svg>
        <span class="sr-only">{{ $label }}</span>
    </span>
@else
    <svg {{ $attributes->class(['h-5 w-5 animate-spin text-brand-600']) }} viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z" />
    </svg>
@endif
