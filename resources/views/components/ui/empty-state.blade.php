@props([
    'title' => null,
    'description' => null,
    'icon' => null,
])

<div {{ $attributes->class(['flex flex-col items-center justify-center rounded-card border border-dashed border-slate-300 bg-white px-6 py-12 text-center']) }}>
    @if ($icon)
        <span class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-500">
            <x-ui.icon :name="$icon" class="h-6 w-6" />
        </span>
    @endif

    @if ($title)
        <p class="text-base font-bold text-slate-900">{{ $title }}</p>
    @endif

    @if ($description)
        <p class="mt-1 max-w-sm text-sm leading-relaxed text-slate-600">{{ $description }}</p>
    @endif

    {{ $slot }}
</div>
