<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2.5 bg-ink border border-transparent rounded-md font-medium text-sm text-white hover:bg-signal focus:outline-none focus:ring-2 focus:ring-signal focus:ring-offset-2 active:bg-signal-hover transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>