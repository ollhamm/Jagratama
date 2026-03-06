<button
    x-data="{ theme: localStorage.getItem('theme') || 'light' }"
    x-init="
        if (theme === 'dark') {
            document.documentElement.classList.add('dark');
        }
    "
    @click="
        theme = theme === 'light' ? 'dark' : 'light';
        localStorage.setItem('theme', theme);
        if (theme === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    "
    class="relative flex items-center justify-center text-gray-500 transition-colors bg-white border border-gray-200 rounded-full hover:text-dark-900 h-11 w-11 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
>
    <!-- Dark Icon -->
    <svg x-show="theme === 'dark'" data-feather="sun" width="20" height="20"></svg>

    <!-- Light Icon -->
    <svg x-show="theme === 'light'" data-feather="moon" width="20" height="20"></svg>
</button>
