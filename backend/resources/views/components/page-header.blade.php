@props(['title', 'description' => null])

<div {{ $attributes->merge(['class' => 'flex items-center justify-between gap-3']) }}>
    <div>
        <h2 class="text-2xl font-semibold tracking-tight text-balance text-gray-900">
            {{ $title }}
        </h2>
        @if ($description)
            <p class="mt-1 text-sm text-gray-500 text-pretty">{{ $description }}</p>
        @endif
    </div>
    @isset($action)
        <div>{{ $action }}</div>
    @endisset
</div>
