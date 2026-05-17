<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Dashboard' }} Jagratama</title>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://unpkg.com/feather-icons"></script>

    <!-- Select2 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css">
    <style>
        /* ===== Select2 — Tailwind theme override ===== */
        .select2-container { width: 100% !important; }

        .select2-container--default .select2-selection--single {
            height: 2.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            background-color: transparent;
            display: flex;
            align-items: center;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #1f2937;
            font-size: 0.875rem;
            line-height: 1.25rem;
            padding-left: 1rem;
            padding-right: 2rem;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 2.75rem;
            right: 0.5rem;
        }
        .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: #9ca3af;
        }
        .select2-container--default.select2-container--focus .select2-selection--single,
        .select2-container--default.select2-container--open .select2-selection--single {
            border-color: var(--color-brand-500, #3b82f6);
            outline: none;
        }
        .select2-dropdown {
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / .1);
        }
        .select2-container--default .select2-search--dropdown .select2-search__field {
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            outline: none;
        }
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: var(--color-brand-500, #3b82f6);
        }
        .select2-container--default .select2-results__option[aria-selected=true] {
            background-color: #eff6ff;
            color: #1d4ed8;
        }

        /* ===== Dark mode ===== */
        .dark .select2-container--default .select2-selection--single {
            border-color: #374151;
            background-color: transparent;
        }
        .dark .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: rgba(255,255,255,0.9);
        }
        .dark .select2-dropdown {
            background-color: #111827;
            border-color: #374151;
            color: rgba(255,255,255,0.9);
        }
        .dark .select2-container--default .select2-search--dropdown .select2-search__field {
            background-color: #1f2937;
            border-color: #374151;
            color: rgba(255,255,255,0.9);
        }
        .dark .select2-container--default .select2-results__option {
            background-color: #111827;
            color: rgba(255,255,255,0.8);
        }
        .dark .select2-container--default .select2-results__option[aria-selected=true] {
            background-color: #1e3a5f;
            color: #93c5fd;
        }
        .dark .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: var(--color-brand-500, #3b82f6);
            color: #fff;
        }
        .dark .select2-selection__arrow b { border-color: #9ca3af transparent transparent; }
        .dark .select2-container--open .select2-selection__arrow b { border-color: transparent transparent #9ca3af; }

        /* Prevent dropdown from causing horizontal scrollbar */
        body { overflow-x: hidden; }
        .select2-dropdown { overflow: hidden; }

        /* Disabled state */
        .select2-container--default.select2-container--disabled .select2-selection--single {
            background-color: #f9fafb;
            cursor: not-allowed;
            opacity: 0.6;
        }
        .dark .select2-container--default.select2-container--disabled .select2-selection--single {
            background-color: #1f2937;
        }
    </style>

    <!-- Alpine.js -->
    {{-- <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script> --}}

    <!-- Theme Store -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('theme', {
                init() {
                    const savedTheme = localStorage.getItem('theme');
                    const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' :
                        'light';
                    this.theme = savedTheme || systemTheme;
                    this.updateTheme();
                },
                theme: 'light',
                toggle() {
                    this.theme = this.theme === 'light' ? 'dark' : 'light';
                    localStorage.setItem('theme', this.theme);
                    this.updateTheme();
                },
                updateTheme() {
                    const html = document.documentElement;
                    const body = document.body;
                    if (this.theme === 'dark') {
                        html.classList.add('dark');
                        body.classList.add('dark', 'bg-gray-900');
                    } else {
                        html.classList.remove('dark');
                        body.classList.remove('dark', 'bg-gray-900');
                    }
                }
            });

            Alpine.store('sidebar', {
                // Initialize based on screen size
                isExpanded: window.innerWidth >= 1280, // true for desktop, false for mobile
                isMobileOpen: false,
                isHovered: false,

                toggleExpanded() {
                    this.isExpanded = !this.isExpanded;
                    // When toggling desktop sidebar, ensure mobile menu is closed
                    this.isMobileOpen = false;
                },

                toggleMobileOpen() {
                    this.isMobileOpen = !this.isMobileOpen;
                    // Don't modify isExpanded when toggling mobile menu
                },

                setMobileOpen(val) {
                    this.isMobileOpen = val;
                },

                setHovered(val) {
                    // Only allow hover effects on desktop when sidebar is collapsed
                    if (window.innerWidth >= 1280 && !this.isExpanded) {
                        this.isHovered = val;
                    }
                }
            });
        });
    </script>

    <!-- Apply dark mode immediately to prevent flash -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme');
            const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            const theme = savedTheme || systemTheme;
            const body = document.body;
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
                body?.classList.add('dark', 'bg-gray-900');
            } else {
                document.documentElement.classList.remove('dark');
                body?.classList.remove('dark', 'bg-gray-900');
            }
        })();
    </script>
    
</head>

<body
    x-data="{ 'loaded': true}"
    x-init="$store.sidebar.isExpanded = window.innerWidth >= 1280;
    const checkMobile = () => {
        if (window.innerWidth < 1280) {
            $store.sidebar.setMobileOpen(false);
            $store.sidebar.isExpanded = false;
        } else {
            $store.sidebar.isMobileOpen = false;
            $store.sidebar.isExpanded = true;
        }
    };
    window.addEventListener('resize', checkMobile);">

    {{-- preloader --}}
    <x-common.preloader/>
    {{-- preloader end --}}

    <div class="min-h-screen xl:flex">
        @include('layouts.backdrop')
        @include('layouts.sidebar')

        <div class="flex-1 transition-all duration-300 ease-in-out"
            :class="{
                'xl:ml-[290px]': $store.sidebar.isExpanded || $store.sidebar.isHovered,
                'xl:ml-[90px]': !$store.sidebar.isExpanded && !$store.sidebar.isHovered,
                'ml-0': $store.sidebar.isMobileOpen
            }">
            <!-- app header start -->
            @include('layouts.app-header')
            <!-- app header end -->
            <div class="p-4 mx-auto max-w-(--breakpoint-2xl) md:p-6">
                @yield('content')
            </div>
        </div>

    </div>

    @stack('modals')

    <!-- jQuery + Select2 -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js"></script>
    <script>
        function initSelect2() {
            $('select').not('[disabled]').not('[data-no-select2]').each(function () {
                if ($(this).hasClass('select2-hidden-accessible')) return;
                const count = $(this).find('option').length;
                $(this).select2({
                    width: '100%',
                    minimumResultsForSearch: count > 6 ? 0 : Infinity,
                    placeholder: $(this).find('option[value=""]').text() || '',
                    allowClear: false,
                    dropdownParent: $('body'),
                });
            });
        }

        // Inisialisasi setelah Alpine selesai agar x-model sudah set nilai awal
        document.addEventListener('alpine:initialized', initSelect2);

        // Inisialisasi ulang saat Select2 perlu sinkron dengan Alpine x-model
        document.addEventListener('alpine:initialized', function () {
            $('select[x-model]').not('[disabled]').on('select2:select select2:unselect', function () {
                $(this).trigger('change');
            });
        });
    </script>

</body>

@stack('scripts')

<script>
    // Initialize feather icons
    document.addEventListener('DOMContentLoaded', () => {
        feather.replace({ class: 'text-current' });
    });
    
    // Also replace icons when Alpine updates DOM
    document.addEventListener('alpine:initialized', () => {
        feather.replace();
    });
</script>

</html>
