<!DOCTYPE html>
<html class="light" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>{{ $title ?? 'FleetManager - Enterprise Fleet & Asset Tracking' }}</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-background text-on-surface antialiased min-h-screen flex flex-col md:flex-row font-body-md text-body-md" x-data="{ mobileMenuOpen: false }">
    
    <!-- Desktop SideNavBar -->
    <nav class="hidden md:flex w-[260px] h-screen fixed left-0 top-0 flex-col py-md px-sm bg-surface-container-low border-r border-outline-variant z-40">
        <div class="mb-xl px-sm flex items-center gap-sm">
            <div class="w-10 h-10 rounded-xl bg-primary flex items-center justify-center text-on-primary shadow-sm shrink-0">
                <span class="material-symbols-outlined text-[24px]" data-weight="fill">local_shipping</span>
            </div>
            <div>
                <h1 class="text-headline-sm font-headline-sm font-black text-on-surface leading-tight">FleetManager</h1>
                <p class="text-label-md font-label-md text-on-surface-variant">Enterprise Logistics</p>
            </div>
        </div>

        <ul class="flex flex-col gap-xs flex-grow">
            <li>
                <a href="{{ route('dashboard') }}" class="flex items-center gap-md px-md py-sm rounded-lg transition-all duration-200 ease-in-out {{ request()->routeIs('dashboard') ? 'text-primary bg-secondary-container font-bold shadow-xs' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined" {{ request()->routeIs('dashboard') ? 'data-weight=fill' : '' }}>dashboard</span>
                    <span class="font-label-md text-label-md">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="{{ route('live-tracking') }}" class="flex items-center gap-md px-md py-sm rounded-lg transition-all duration-200 ease-in-out {{ request()->routeIs('live-tracking') ? 'text-primary bg-secondary-container font-bold shadow-xs' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined" {{ request()->routeIs('live-tracking') ? 'data-weight=fill' : '' }}>local_shipping</span>
                    <span class="font-label-md text-label-md">Live Assets</span>
                </a>
            </li>
            <li>
                <a href="{{ route('precision-system') }}" class="flex items-center gap-md px-md py-sm rounded-lg transition-all duration-200 ease-in-out {{ request()->routeIs('precision-system') ? 'text-primary bg-secondary-container font-bold shadow-xs' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined" {{ request()->routeIs('precision-system') ? 'data-weight=fill' : '' }}>precision_manufacturing</span>
                    <span class="font-label-md text-label-md">Precision System</span>
                </a>
            </li>
            <li>
                <a href="{{ route('assets.add') }}" class="flex items-center gap-md px-md py-sm rounded-lg transition-all duration-200 ease-in-out {{ request()->routeIs('assets.add') ? 'text-primary bg-secondary-container font-bold shadow-xs' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined" {{ request()->routeIs('assets.add') ? 'data-weight=fill' : '' }}>add_location_alt</span>
                    <span class="font-label-md text-label-md">Asset Mapping</span>
                </a>
            </li>
            <li>
                <a href="{{ route('geofences') }}" class="flex items-center gap-md px-md py-sm rounded-lg transition-all duration-200 ease-in-out {{ request()->routeIs('geofences') ? 'text-primary bg-secondary-container font-bold shadow-xs' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined" {{ request()->routeIs('geofences') ? 'data-weight=fill' : '' }}>map</span>
                    <span class="font-label-md text-label-md">Geofences</span>
                </a>
            </li>
            <li>
                <a href="{{ route('alerts') }}" class="flex items-center gap-md px-md py-sm rounded-lg transition-all duration-200 ease-in-out {{ request()->routeIs('alerts') ? 'text-primary bg-secondary-container font-bold shadow-xs' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <span class="material-symbols-outlined" {{ request()->routeIs('alerts') ? 'data-weight=fill' : '' }}>notifications_active</span>
                    <span class="font-label-md text-label-md">Alert Rules</span>
                </a>
            </li>
        </ul>

        <div class="mt-auto pt-lg border-t border-outline-variant/30 flex flex-col gap-xs">
            <a href="#" class="flex items-center gap-md px-md py-sm rounded-lg text-on-surface-variant hover:bg-surface-container-high transition-all duration-200 ease-in-out">
                <span class="material-symbols-outlined">settings</span>
                <span class="font-label-md text-label-md">Settings</span>
            </a>
            <a href="#" class="flex items-center gap-md px-md py-sm rounded-lg text-on-surface-variant hover:bg-surface-container-high transition-all duration-200 ease-in-out">
                <span class="material-symbols-outlined">contact_support</span>
                <span class="font-label-md text-label-md">Support</span>
            </a>
        </div>
    </nav>

    <!-- Mobile Navigation Drawer -->
    <div x-show="mobileMenuOpen" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-x-full"
         x-transition:enter-end="opacity-100 translate-x-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-x-0"
         x-transition:leave-end="opacity-0 -translate-x-full"
         class="fixed inset-0 z-50 bg-surface-container-low p-md flex flex-col md:hidden border-r border-outline-variant"
         style="display: none;">
        <div class="flex items-center justify-between mb-lg">
            <div class="flex items-center gap-sm">
                <div class="w-9 h-9 rounded-lg bg-primary flex items-center justify-center text-on-primary">
                    <span class="material-symbols-outlined" data-weight="fill">local_shipping</span>
                </div>
                <span class="font-headline-md text-on-surface font-black">FleetManager</span>
            </div>
            <button @click="mobileMenuOpen = false" class="p-2 text-on-surface-variant hover:bg-surface-container-high rounded-full">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <ul class="flex flex-col gap-sm flex-1">
            <li>
                <a href="{{ route('dashboard') }}" class="flex items-center gap-md px-md py-sm rounded-lg {{ request()->routeIs('dashboard') ? 'text-primary bg-secondary-container font-bold' : 'text-on-surface-variant' }}">
                    <span class="material-symbols-outlined">dashboard</span> Dashboard
                </a>
            </li>
            <li>
                <a href="{{ route('live-tracking') }}" class="flex items-center gap-md px-md py-sm rounded-lg {{ request()->routeIs('live-tracking') ? 'text-primary bg-secondary-container font-bold' : 'text-on-surface-variant' }}">
                    <span class="material-symbols-outlined">local_shipping</span> Live Assets
                </a>
            </li>
            <li>
                <a href="{{ route('precision-system') }}" class="flex items-center gap-md px-md py-sm rounded-lg {{ request()->routeIs('precision-system') ? 'text-primary bg-secondary-container font-bold' : 'text-on-surface-variant' }}">
                    <span class="material-symbols-outlined">precision_manufacturing</span> Precision System
                </a>
            </li>
            <li>
                <a href="{{ route('assets.add') }}" class="flex items-center gap-md px-md py-sm rounded-lg {{ request()->routeIs('assets.add') ? 'text-primary bg-secondary-container font-bold' : 'text-on-surface-variant' }}">
                    <span class="material-symbols-outlined">add_location_alt</span> Asset Mapping
                </a>
            </li>
            <li>
                <a href="{{ route('geofences') }}" class="flex items-center gap-md px-md py-sm rounded-lg {{ request()->routeIs('geofences') ? 'text-primary bg-secondary-container font-bold' : 'text-on-surface-variant' }}">
                    <span class="material-symbols-outlined">map</span> Geofences
                </a>
            </li>
            <li>
                <a href="{{ route('alerts') }}" class="flex items-center gap-md px-md py-sm rounded-lg {{ request()->routeIs('alerts') ? 'text-primary bg-secondary-container font-bold' : 'text-on-surface-variant' }}">
                    <span class="material-symbols-outlined">notifications_active</span> Alert Rules
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col md:ml-[260px] min-h-screen">
        <!-- Header -->
        <header class="w-full h-16 sticky top-0 z-30 bg-surface-container-lowest border-b border-outline-variant shadow-xs flex justify-between items-center px-lg">
            <div class="flex items-center gap-md">
                <button @click="mobileMenuOpen = true" class="md:hidden p-2 text-on-surface-variant hover:bg-surface-container-low rounded-lg">
                    <span class="material-symbols-outlined">menu</span>
                </button>
                <div class="hidden md:flex items-center gap-md">
                    <div class="relative w-64 group">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant group-focus-within:text-primary transition-colors">search</span>
                        <input type="text" placeholder="Search assets, drivers..." class="w-full pl-10 pr-4 py-1.5 bg-surface border border-outline-variant rounded-lg font-body-sm text-body-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"/>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-sm">
                <button class="p-2 text-on-surface-variant hover:bg-surface-container-low hover:text-primary rounded-full transition-colors active:scale-95 duration-150 relative">
                    <span class="material-symbols-outlined">notifications</span>
                    <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-error"></span>
                </button>
                <button class="p-2 text-on-surface-variant hover:bg-surface-container-low hover:text-primary rounded-full transition-colors active:scale-95 duration-150 hidden sm:block">
                    <span class="material-symbols-outlined">help_outline</span>
                </button>
                <div class="relative ml-sm" x-data="{ userMenuOpen: false }">
                    <button @click="userMenuOpen = !userMenuOpen" class="flex items-center gap-2 p-1 rounded-full hover:bg-surface-container-low transition-colors">
                        <img class="w-8 h-8 rounded-full border border-outline-variant object-cover" src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=120&q=80" alt="Avatar"/>
                        <span class="hidden xl:inline text-label-md font-label-md text-on-surface font-semibold">{{ Auth::user()->name ?? 'Alex Mercer' }}</span>
                        <span class="material-symbols-outlined text-[18px] text-on-surface-variant">expand_more</span>
                    </button>

                    <div x-show="userMenuOpen" 
                         @click.outside="userMenuOpen = false"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-56 bg-surface-container-lowest border border-outline-variant rounded-xl shadow-lg py-2 z-50 divide-y divide-outline-variant/40"
                         style="display: none;">
                        <div class="px-md py-sm">
                            <p class="font-headline-sm text-body-md font-bold text-on-surface truncate">{{ Auth::user()->name ?? 'User' }}</p>
                            <p class="text-body-sm text-on-surface-variant truncate">{{ Auth::user()->email ?? 'user@example.com' }}</p>
                        </div>
                        <div class="py-1">
                            <a href="{{ route('profile.edit') }}" class="flex items-center gap-sm px-md py-sm text-body-sm text-on-surface hover:bg-surface-container-low transition-colors">
                                <span class="material-symbols-outlined text-[18px]">person</span> Manage Profile
                            </a>
                        </div>
                        <div class="py-1">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left flex items-center gap-sm px-md py-sm text-body-sm text-error hover:bg-error-container/20 transition-colors">
                                    <span class="material-symbols-outlined text-[18px]">logout</span> Log Out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Slot -->
        <main class="flex-1 flex flex-col">
            {{ $slot }}
        </main>
    </div>

    @stack('scripts')
</body>
</html>
