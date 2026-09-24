<header class="h-16 flex items-center justify-between px-4 sm:px-6 lg:px-8 bg-white border-b border-slate-200">
    <div class="flex items-center gap-3">
        <button type="button" class="lg:hidden p-2 rounded-md hover:bg-slate-100" aria-label="Open menu" aria-expanded="false">
            <span class="block w-5 h-0.5 bg-slate-700 mb-1"></span>
            <span class="block w-5 h-0.5 bg-slate-700 mb-1"></span>
            <span class="block w-5 h-0.5 bg-slate-700"></span>
        </button>
        <nav class="text-sm text-slate-500">
            @yield('breadcrumbs')
        </nav>
    </div>

    <div class="flex items-center gap-4">
        <button type="button" class="relative p-2 rounded-full hover:bg-slate-100" aria-label="Notifications">
            <span class="block w-5 h-5 rounded-full border-2 border-slate-500"></span>
        </button>

        @auth
            <div class="flex items-center gap-3">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-medium leading-tight">{{ auth()->user()->full_name }}</p>
                    <p class="text-xs text-slate-500 capitalize leading-tight">{{ auth()->user()->role?->slug }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-sm font-medium text-slate-600 hover:text-indigo-600">
                        Log out
                    </button>
                </form>
            </div>
        @endauth
    </div>
</header>
