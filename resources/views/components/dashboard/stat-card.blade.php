@props([
    'label' => '',
    'value' => 0,
    'icon' => 'circle',
    'color' => 'brand',
])

@php
    $colorMap = [
        'brand'   => 'bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400',
        'success' => 'bg-success-50 text-success-600 dark:bg-success-500/10 dark:text-success-400',
        'error'   => 'bg-error-50 text-error-600 dark:bg-error-500/10 dark:text-error-400',
        'warning' => 'bg-warning-50 text-warning-600 dark:bg-warning-500/10 dark:text-warning-400',
    ];
    $iconClass = $colorMap[$color] ?? $colorMap['brand'];

    // Inline SVG (bukan data-feather + JS replace) — supaya tidak bergantung pada
    // timing/urutan eksekusi feather.replace() di layout, yang sempat bikin error
    // "replaceChild" dan icon gagal tampil.
    $iconPaths = [
        'file-text' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline>',
        'check-circle' => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline>',
        'x-circle' => '<circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line>',
        'clock' => '<circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline>',
        'bell' => '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path>',
        'users' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>',
        'circle' => '<circle cx="12" cy="12" r="10"></circle>',
    ];
    $svgInner = $iconPaths[$icon] ?? $iconPaths['circle'];
@endphp

<div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="flex items-center gap-4">
        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full {{ $iconClass }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $svgInner !!}</svg>
        </div>
        <div>
            <p class="text-xs uppercase text-gray-500 dark:text-gray-400">{{ $label }}</p>
            <p class="mt-0.5 text-2xl font-bold text-gray-800 dark:text-white/90">{{ $value }}</p>
        </div>
    </div>
</div>
