
@php
    use App\Helpers\MenuHelper;
    $menuGroups = MenuHelper::getMenuGroups();

    // Get current path
    $currentPath = request()->path();

    // Semua path menu (item + subItem) — dipakai untuk cari match paling spesifik,
    // supaya menu dengan path lebih pendek (mis. /app/documents) tidak ikut aktif
    // saat berada di path yang lebih spesifik (mis. /app/documents/published).
    $allMenuPaths = [];
    foreach ($menuGroups as $menuGroup) {
        foreach ($menuGroup['items'] as $item) {
            if (isset($item['path'])) {
                $allMenuPaths[] = $item['path'];
            }
            foreach ($item['subItems'] ?? [] as $subItem) {
                $allMenuPaths[] = $subItem['path'];
            }
        }
    }
@endphp

<aside id="sidebar"
    class="fixed flex flex-col mt-0 top-0 px-5 left-0 bg-white dark:bg-gray-900 dark:border-gray-800 text-gray-900 h-screen transition-all duration-300 ease-in-out z-99999 border-r border-gray-200"
    x-data="{
        openSubmenus: {},
        allMenuPaths: {{ Js::from($allMenuPaths) }},
        init() {
            // Auto-open Dashboard menu on page load
            this.initializeActiveMenus();
        },
        initializeActiveMenus() {
            const currentPath = '{{ $currentPath }}';

            @foreach ($menuGroups as $groupIndex => $menuGroup)
                @foreach ($menuGroup['items'] as $itemIndex => $item)
                    @if (isset($item['subItems']))
                        // Check if any submenu item matches current path
                        @foreach ($item['subItems'] as $subItem)
                            if (currentPath === '{{ ltrim($subItem['path'], '/') }}' ||
                                window.location.pathname === '{{ $subItem['path'] }}') {
                                this.openSubmenus['{{ $groupIndex }}-{{ $itemIndex }}'] = true;
                            } @endforeach
            @endif
            @endforeach
            @endforeach
        },
        toggleSubmenu(groupIndex, itemIndex) {
            const key = groupIndex + '-' + itemIndex;
            const newState = !this.openSubmenus[key];

            // Close all other submenus when opening a new one
            if (newState) {
                this.openSubmenus = {};
            }

            this.openSubmenus[key] = newState;
        },
        isSubmenuOpen(groupIndex, itemIndex) {
            const key = groupIndex + '-' + itemIndex;
            return this.openSubmenus[key] || false;
        },
        isActive(path) {
            const current = window.location.pathname.replace(/\/$/, '') || '/';
            const bare     = path.replace(/\/$/, '') || '/';

            // Cari path menu terdaftar yang paling SPESIFIK (terpanjang) yang cocok
            // dengan current path — supaya cuma satu menu yang aktif sekaligus,
            // walau ada menu lain yang path-nya merupakan prefix dari path ini.
            let bestMatch = '';
            this.allMenuPaths.forEach((p) => {
                const pb = p.replace(/\/$/, '') || '/';
                const matches = current === pb || current.startsWith(pb + '/');
                if (matches && pb.length > bestMatch.length) {
                    bestMatch = pb;
                }
            });

            return bare === bestMatch;
        }
    }"
    :class="{
        'w-[290px]': $store.sidebar.isExpanded || $store.sidebar.isMobileOpen || $store.sidebar.isHovered,
        'w-[90px]': !$store.sidebar.isExpanded && !$store.sidebar.isHovered,
        'translate-x-0': $store.sidebar.isMobileOpen,
        '-translate-x-full xl:translate-x-0': !$store.sidebar.isMobileOpen
    }"
    @mouseenter="if (!$store.sidebar.isExpanded) $store.sidebar.setHovered(true)"
    @mouseleave="$store.sidebar.setHovered(false)">
    <!-- Logo Section -->
    <div class="pt-6 pb-5 flex items-center"
        :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ?
        'xl:justify-center' :
        'justify-start'">
        <a href="/">
            {{-- Logo penuh: saat expanded / hover / mobile open --}}
            <img x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                src="/images/logo/jagratama-logo.png"
                alt="Jagratama Logo"
                class="object-contain"
                style="max-width: 200px;" />

            {{-- Logo ikon kecil: saat sidebar collapsed (desktop only) --}}
            <img x-show="!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen"
                src="/images/logo/jagratama-logo.png"
                alt="Jagratama Logo"
                class="h-8 w-8 object-contain" />
        </a>
    </div>

    <!-- Navigation Menu -->
    <div class="flex flex-col overflow-y-auto duration-300 ease-linear no-scrollbar">
        <nav class="mb-6">
            <div class="flex flex-col gap-4">
                @foreach ($menuGroups as $groupIndex => $menuGroup)
                    <div>
                        <!-- Menu Group Title -->
                        <h2 class="mb-4 text-xs uppercase flex leading-[20px] text-gray-400"
                            :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ?
                            'lg:justify-center' : 'justify-start'">
                            <template
                                x-if="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen">
                                <span>{{ $menuGroup['title'] }}</span>
                            </template>
                            <template x-if="!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen">
                                <svg data-feather="more-horizontal" width="24" height="24"></svg>
                            </template>
                        </h2>

                        <!-- Menu Items -->
                        <ul class="flex flex-col gap-1">
                            @foreach ($menuGroup['items'] as $itemIndex => $item)
                                <li>
                                    @if (isset($item['subItems']))
                                        <!-- Menu Item with Submenu -->
                                        <button @click="toggleSubmenu({{ $groupIndex }}, {{ $itemIndex }})"
                                            class="menu-item group w-full"
                                            :class="[
                                                isSubmenuOpen({{ $groupIndex }}, {{ $itemIndex }}) ?
                                                'menu-item-active' : 'menu-item-inactive',
                                                !$store.sidebar.isExpanded && !$store.sidebar.isHovered ?
                                                'xl:justify-center' : 'xl:justify-start'
                                            ]">

                                            <!-- Icon -->
                                            <span :class="isSubmenuOpen({{ $groupIndex }}, {{ $itemIndex }}) ?
                                                    'menu-item-icon-active' : 'menu-item-icon-inactive'">
                                                <svg data-feather="{{ MenuHelper::getFeatherIcon($item['icon']) }}" width="24" height="24"></svg>
                                            </span>

                                            <!-- Text -->
                                            <span
                                                x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                                                class="menu-item-text flex items-center gap-2">
                                                {{ $item['name'] }}
                                                @if (!empty($item['new']))
                                                    <span class="absolute right-10"
                                                        :class="isActive('{{ $item['path'] ?? '' }}') ?
                                                            'menu-dropdown-badge menu-dropdown-badge-active' :
                                                            'menu-dropdown-badge menu-dropdown-badge-inactive'">
                                                        new
                                                    </span>
                                                @endif
                                            </span>

                                            <!-- Chevron Down Icon -->
                                            <svg x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                                                data-feather="chevron-down"
                                                class="ml-auto w-5 h-5 transition-transform duration-200"
                                                :class="{
                                                    'rotate-180 text-brand-500': isSubmenuOpen({{ $groupIndex }},
                                                        {{ $itemIndex }})
                                                }"></svg>
                                        </button>

                                        <!-- Submenu -->
                                        <div x-show="isSubmenuOpen({{ $groupIndex }}, {{ $itemIndex }}) && ($store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen)">
                                            <ul class="mt-2 space-y-1 ml-9">
                                                @foreach ($item['subItems'] as $subItem)
                                                    <li>
                                                        <a href="{{ $subItem['path'] }}" class="menu-dropdown-item"
                                                            :class="isActive('{{ $subItem['path'] }}') ?
                                                                'menu-dropdown-item-active' :
                                                                'menu-dropdown-item-inactive'">
                                                            {{ $subItem['name'] }}
                                                            <span class="flex items-center gap-1 ml-auto">
                                                                @if (!empty($subItem['new']))
                                                                    <span
                                                                        :class="isActive('{{ $subItem['path'] }}') ?
                                                                            'menu-dropdown-badge menu-dropdown-badge-active' :
                                                                            'menu-dropdown-badge menu-dropdown-badge-inactive'">
                                                                        new
                                                                    </span>
                                                                @endif
                                                                @if (!empty($subItem['pro']))
                                                                    <span
                                                                        :class="isActive('{{ $subItem['path'] }}') ?
                                                                            'menu-dropdown-badge-pro menu-dropdown-badge-pro-active' :
                                                                            'menu-dropdown-badge-pro menu-dropdown-badge-pro-inactive'">
                                                                        pro
                                                                    </span>
                                                                @endif
                                                            </span>
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @else
                                        <!-- Simple Menu Item -->
                                        <a href="{{ $item['path'] }}" class="menu-item group"
                                            :class="[
                                                isActive('{{ $item['path'] }}') ? 'menu-item-active' :
                                                'menu-item-inactive',
                                                (!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ?
                                                'xl:justify-center' :
                                                'justify-start'
                                            ]">

                                            <!-- Icon -->
                                            <span
                                                :class="isActive('{{ $item['path'] }}') ? 'menu-item-icon-active' :
                                                    'menu-item-icon-inactive'">
                                                <svg data-feather="{{ MenuHelper::getFeatherIcon($item['icon']) }}" width="24" height="24"></svg>
                                            </span>

                                            <!-- Text -->
                                            <span
                                                x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                                                class="menu-item-text flex items-center gap-2">
                                                {{ $item['name'] }}
                                                @if (!empty($item['new']))
                                                    <span
                                                        class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-brand-500 text-white">
                                                        new
                                                    </span>
                                                @endif
                                            </span>
                                        </a>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </nav>
    </div>

    <!-- Tombol Panduan Penggunaan — fixed di posisi paling bawah sidebar, di luar area scroll menu -->
    <div class="mt-auto border-t border-gray-200 pt-3 pb-4 dark:border-gray-800">
        <button type="button" @click="$dispatch('open-guide-modal')"
            class="menu-item group w-full menu-item-inactive"
            :class="!$store.sidebar.isExpanded && !$store.sidebar.isHovered ? 'xl:justify-center' : 'xl:justify-start'">
            <span class="menu-item-icon-inactive">
                <svg data-feather="help-circle" width="24" height="24"></svg>
            </span>
            <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                class="menu-item-text">
                Panduan Penggunaan
            </span>
        </button>
    </div>
</aside>

<!-- Mobile Overlay -->
<div x-show="$store.sidebar.isMobileOpen" @click="$store.sidebar.setMobileOpen(false)"
    class="fixed z-50 h-screen w-full bg-gray-900/50"></div>

<!-- Modal Panduan Penggunaan -->
<div
    x-data="{
        open: false,
        loading: false,
        title: 'Panduan Penggunaan',
        content: '',
        load() {
            this.loading = true;
            fetch('{{ route('app.guide.show') }}')
                .then(res => res.json())
                .then(data => {
                    this.title = data.title;
                    this.content = data.content;
                })
                .finally(() => { this.loading = false; });
        }
    }"
    x-show="open"
    x-cloak
    @open-guide-modal.window="open = true; load();"
    @keydown.escape.window="open = false"
    class="fixed flex items-center justify-center bg-gray-900/50 p-4"
    style="inset:0; z-index:9999999; position:fixed;"
>
    <div class="max-h-[80vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white shadow-xl dark:bg-gray-900" @click.away="open = false">
        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-800 dark:text-white/90" x-text="title"></h3>
            <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="px-6 py-5">
            <p x-show="loading" class="text-sm text-gray-500">Memuat panduan...</p>
            <div x-show="!loading" class="guide-content text-sm text-gray-700 dark:text-gray-300" x-html="content"></div>
        </div>
    </div>
</div>

<style>
    .guide-content h2, .guide-content h3 { font-weight: 600 !important; margin-top: 1rem; margin-bottom: 0.5rem; display: block; }
    .guide-content h2 { font-size: 1.15rem !important; }
    .guide-content h3 { font-size: 1.05rem !important; }
    .guide-content p { margin-bottom: 0.75rem; display: block; }
    .guide-content ul, .guide-content ol { margin-bottom: 0.75rem; padding-left: 1.5rem; display: block; }
    .guide-content ul { list-style-type: disc !important; }
    .guide-content ol { list-style-type: decimal !important; }
    .guide-content li { margin-bottom: 0.25rem; display: list-item; }
    .guide-content a { color: #3b82f6; text-decoration: underline; }
    .guide-content strong { font-weight: 700 !important; }
</style>
