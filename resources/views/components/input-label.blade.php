@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-sm font-medium text-ink-muted mb-1.5']) }}>
    {{ $value ?? $slot }}
</label>