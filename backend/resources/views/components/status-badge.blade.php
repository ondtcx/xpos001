@props(['tone' => 'neutral'])

@php
    $tones = [
        'success' => 'bg-emerald-100 text-emerald-700',
        'warning' => 'bg-amber-100 text-amber-800',
        'danger' => 'bg-red-100 text-red-700',
        'info' => 'bg-emerald-100 text-emerald-700',
        'neutral' => 'bg-gray-100 text-gray-600',
    ];

    $classes = $tones[$tone] ?? $tones['neutral'];
@endphp

<span {{ $attributes->merge(['class' => 'rounded-full px-2.5 py-1 text-xs font-medium ' . $classes]) }}>
    {{ $slot }}
</span>
