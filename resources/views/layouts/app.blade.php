<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main class="flex min-h-dvh flex-col">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
