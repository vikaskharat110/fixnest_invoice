@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge([
    'class' => 'block w-full rounded-lg border-border bg-white text-ink text-sm shadow-sm
                focus:border-signal focus:ring-1 focus:ring-signal
                px-3 py-2.5'
]) }}>