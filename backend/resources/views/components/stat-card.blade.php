@props(['label', 'href' => null])

@php($tag = $href ? 'a' : 'div')

<{{ $tag }} {{ $attributes->merge(['class' => 'rounded-lg bg-white p-6 ring-1 ring-gray-200']) }}
    @if($href) href="{{ $href }}" @endif>
    <p class="text-sm font-medium text-gray-500">{{ $label }}</p>
    <div class="mt-2">{{ $slot }}</div>
</{{ $tag }}>
