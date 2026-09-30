<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2.5 bg-signal border border-transparent
    rounded-lg text-sm font-medium text-white tracking-wide
    hover:bg-signal-hover focus:outline-none focus:ring-2 focus:ring-signal focus:ring-offset-2
    transition disabled:opacity-50']) }}>
    {{ $slot }}
</button>