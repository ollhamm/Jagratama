@props([
    'isOpen' => false,
    'showCloseButton' => true,
])

<div x-data="{
    open: @js($isOpen),
    init() {
        this.$watch('open', value => {
            if (value) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = 'unset';
            }
        });
    }
}" x-show="open" x-cloak @keydown.escape.window="open = false"
    class="modal fixed inset-0 z-99999 flex items-center justify-center overflow-y-auto p-5"
    {{ $attributes->except('class') }}>

    <!-- Backdrop -->
    <div @click="open = false" class="fixed inset-0 h-full w-full bg-gray-400/50 backdrop-blur-sm"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
    </div>

    <!-- Modal Content -->
    <div @click.stop class="relative w-full rounded-3xl bg-white dark:bg-gray-900 {{ $attributes->get('class') }}"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95"
        x-transition:enter-end="opacity-100 transform scale-100" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 transform scale-100"
        x-transition:leave-end="opacity-0 transform scale-95">


        <!-- Modal Body -->
        <div>
            {{ $slot }}
        </div>
    </div>
</div>

<style>
    [x-cloak] {
        display: none;
    }
</style>
