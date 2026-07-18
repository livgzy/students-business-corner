<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
 
        <title>{{ $title ?? config('app.name') }}</title>
 
        @vite(['resources/css/app.css', 'resources/js/app.js'])
 
        @livewireStyles
    </head>
    <body x-data="{ sidebarOpen: false }">
    
        <div class="flex h-screen overflow-hidden">
            
            <!-- Overlay untuk mobile -->
            <div x-show="sidebarOpen" 
                 x-cloak
                 x-transition:enter="transition-opacity ease-linear duration-300" 
                 x-transition:enter-start="opacity-0" 
                 x-transition:enter-end="opacity-100" 
                 x-transition:leave="transition-opacity ease-linear duration-300" 
                 x-transition:leave-start="opacity-100" 
                 x-transition:leave-end="opacity-0" 
                 class="fixed inset-0 z-20 bg-black/30 lg:hidden" 
                 @click="sidebarOpen = false"></div>
    
            <!-- Sidebar -->
            <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" 
            class="fixed inset-y-0 left-0 z-30 w-72 bg-white shadow-2xl lg:static lg:translate-x-0 transition-transform duration-300 ease-in-out">
                
                <div class="flex flex-col h-full">
                    <!-- Logo Area dengan Gradient -->
                    <div class="relative px-6 py-6 bg-gradient-to-r from-orange-500 to-amber-500">
                        <div class="flex items-center justify-between">
                            <a href="/">
                                <img src="{{ asset('storage/logo/ucic_logo.png') }}" 
                                        alt="Logo" 
                                        class="h-14 sm:h-12 w-auto object-contain">
                            </a>
                            <button @click="sidebarOpen = false" class="lg:hidden text-white/80 hover:text-white">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>                    
    
                    <!-- Navigation Menu -->
                    <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto">
                        <!-- Dashboard -->
                        <a href="#"
                           class="flex items-center px-4 py-3 text-gray-700 rounded-xl transition-all duration-200 group {{ request()->routeIs('tenant.dashboard') ? 'bg-gradient-to-r from-orange-500 to-amber-500 text-white shadow-lg' : 'hover:bg-orange-50 hover:text-orange-600' }}">
                            <svg class="w-5 h-5 mr-3 transition-colors {{ request()->routeIs('tenant.dashboard') ? 'text-white' : 'text-gray-400 group-hover:text-orange-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                            </svg>
                            <span class="font-medium">Dashboard</span>
                        </a>
    
                        <!-- Pesanan Masuk -->
                        <a href="#" 
                           class="flex items-center px-4 py-3 text-gray-700 rounded-xl transition-all duration-200 group {{ request()->routeIs('tenant.orders') ? 'bg-gradient-to-r from-orange-500 to-amber-500 text-white shadow-lg' : 'hover:bg-orange-50 hover:text-orange-600' }}">
                            <svg class="w-5 h-5 mr-3 {{ request()->routeIs('tenant.orders') ? 'text-white' : 'text-gray-400 group-hover:text-orange-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                            <span class="font-medium">Pesanan Masuk</span>
                            <span class="inline-flex items-center justify-center w-5 h-5 ml-auto text-xs font-bold text-white bg-red-500 rounded-full shadow-sm">3</span>
                        </a>
    
                        <!-- Kelola Menu -->
                        <a href="{{ route('tenant.menu') }}"
                           class="flex items-center px-4 py-3 text-gray-700 rounded-xl transition-all duration-200 group {{ request()->routeIs('tenant.menu*') ? 'bg-gradient-to-r from-orange-500 to-amber-500 text-white shadow-lg' : 'hover:bg-orange-50 hover:text-orange-600' }}">
                            <svg class="w-5 h-5 mr-3 {{ request()->routeIs('tenant.menu*') ? 'text-white' : 'text-gray-400 group-hover:text-orange-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                            <span class="font-medium">Kelola Menu</span>
                        </a>
    
                        <!-- Laporan -->
                        <a href="#"
                           class="flex items-center px-4 py-3 text-gray-700 rounded-xl transition-all duration-200 group {{ request()->routeIs('tenant.reports') ? 'bg-gradient-to-r from-orange-500 to-amber-500 text-white shadow-lg' : 'hover:bg-orange-50 hover:text-orange-600' }}">
                            <svg class="w-5 h-5 mr-3 {{ request()->routeIs('tenant.reports') ? 'text-white' : 'text-gray-400 group-hover:text-orange-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            <span class="font-medium">Laporan</span>
                        </a>
    
                        <!-- Pengaturan Toko -->
                        <a href="#"
                           class="flex items-center px-4 py-3 text-gray-700 rounded-xl transition-all duration-200 group {{ request()->routeIs('tenant.settings') ? 'bg-gradient-to-r from-orange-500 to-amber-500 text-white shadow-lg' : 'hover:bg-orange-50 hover:text-orange-600' }}">
                            <svg class="w-5 h-5 mr-3 {{ request()->routeIs('tenant.settings') ? 'text-white' : 'text-gray-400 group-hover:text-orange-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <span class="font-medium">Pengaturan Toko</span>
                        </a>
                    </nav>
    
                    <!-- Footer Menu -->
                    <div class="p-4 border-t bg-gray-50">
                        <a href="{{ route('home') }}" 
                           class="flex items-center px-4 py-3 text-gray-600 rounded-xl hover:bg-gray-100 transition-all duration-200">
                            <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            <span class="font-medium">Kembali ke Website</span>
                        </a>
                </div>
            </aside>
    
            <!-- Main Content Area -->
            <div class="flex flex-col flex-1 overflow-hidden">
                
                <!-- Top Navigation Bar -->
                <header class="sticky top-0 z-10 bg-white/80 backdrop-blur-md border-b shadow-sm">
                    <div class="flex items-center justify-between px-6 py-3">
                        <div class="flex items-center">
                            <button @click="sidebarOpen = true" class="p-2 text-gray-500 rounded-lg lg:hidden hover:bg-gray-100">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                </svg>
                            </button>
                        
                        </div>
    
                        <div class="flex items-center space-x-3">
    
                            <!-- Notification Bell -->
                            <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open" class="relative p-2 text-gray-500 rounded-lg hover:bg-gray-100">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                                    </svg>
                                    <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                                </button>
                                <div x-show="open" 
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 transform -translate-y-2"
                                     x-transition:enter-end="opacity-100 transform translate-y-0"
                                     x-transition:leave="transition ease-in duration-150"
                                     x-transition:leave-start="opacity-100 transform translate-y-0"
                                     x-transition:leave-end="opacity-0 transform -translate-y-2"
                                     @click.away="open = false"
                                     class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-lg z-50 border"
                                     style="display: none;">
                                    <div class="p-4 border-b">
                                        <h3 class="font-semibold text-gray-800">Notifikasi</h3>
                                    </div>
                                    <div class="max-h-96 overflow-y-auto">
                                        <div class="p-4 hover:bg-gray-50 cursor-pointer border-b">
                                            <p class="text-sm text-gray-800">Pesanan baru masuk!</p>
                                            <p class="text-xs text-gray-400 mt-1">2 menit yang lalu</p>
                                        </div>
                                        <div class="p-4 hover:bg-gray-50 cursor-pointer">
                                            <p class="text-sm text-gray-800">Menu Anda telah dilihat 50 kali</p>
                                            <p class="text-xs text-gray-400 mt-1">1 jam yang lalu</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
    
                            <!-- User Dropdown -->
                            <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open" class="flex items-center space-x-2 p-1 rounded-full hover:bg-gray-100 transition">
                                    <img class="w-9 h-9 rounded-full border-2 border-orange-500 object-cover" 
                                         src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name ?? 'Tenant') }}&background=F97316&color=fff&bold=true" 
                                         alt="Avatar">
                                    <div class="hidden md:block text-left">
                                        <p class="text-sm font-medium text-gray-800">{{ Auth::user()->name ?? 'Tenant User' }}</p>
                                        <p class="text-xs text-gray-500">Tenant</p>
                                    </div>
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <div x-show="open" 
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 transform -translate-y-2"
                                     x-transition:enter-end="opacity-100 transform translate-y-0"
                                     x-transition:leave="transition ease-in duration-150"
                                     x-transition:leave-start="opacity-100 transform translate-y-0"
                                     x-transition:leave-end="opacity-0 transform -translate-y-2"
                                     @click.away="open = false"
                                     class="absolute right-0 mt-2 w-56 bg-white shadow-lg z-50"
                                     style="display: none;">
                                    <div class="px-4 py-3 border-b bg-gradient-to-r from-orange-50 to-amber-50 rounded-t-xl">
                                        <p class="text-sm font-semibold text-gray-800">{{ Auth::user()->name ?? 'Tenant User' }}</p>
                                        <p class="text-xs text-gray-500">{{ Auth::user()->email ?? 'tenant@foodcourt.com' }}</p>
                                    </div>
                                    <div class="py-2">
                                        <a href="#" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Profile</a>
                                    </div>
                                    <form method="POST" action="/logout">
                                        @csrf
                                        <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 rounded-b-xl">Logout</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </header>
    
                <!-- Main Content -->
                <main class="flex-1 overflow-x-hidden overflow-y-auto p-6">
                    {{ $slot }}
                </main>
            </div>
        </div>
    
        @livewireScripts
    </body>
</html>