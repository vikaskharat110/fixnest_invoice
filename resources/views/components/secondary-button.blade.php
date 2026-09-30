<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-4 py-2.5 bg-surface border border-border rounded-lg font-medium text-sm text-ink hover:bg-canvas focus:outline-none focus:ring-2 focus:ring-signal focus:ring-offset-2 disabled:opacity-25 transition']) }}>
    {{ $slot }}
</button>
