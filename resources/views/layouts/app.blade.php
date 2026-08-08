<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? config('app.name') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles
    </head>
    <body class="font-sans antialiased bg-gray-50">
        <div class="min-h-screen">
            <nav class="bg-white shadow-sm sticky top-0 z-50">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center h-20">
                        <!-- Logo Section -->
                        <div class="flex items-center">
                            @if(Illuminate\Support\Facades\Storage::disk('tsbc_disk')->exists('logo/logo_ucic.png'))
                                <a href="/" class="flex items-center py-1 group">
                                    <img src="{{ Illuminate\Support\Facades\Storage::disk('tsbc_disk')->temporaryUrl('logo/logo_ucic.png', now()->addMinutes(1)) }}"
                                        alt="Logo UCIC"
                                        class="h-9 sm:h-18 w-auto object-contain drop-shadow-sm transition-transform duration-200 group-hover:scale-105">
                                </a>
                                <a href="/" class="text-lg sm:text-xl font-bold text-orange-600 tracking-tight">
                                    Student Business Corner
                                </a>
                            @endif
                        </div>
                        
                        <!-- Desktop Menu -->
                        <div class="hidden md:flex items-center space-x-4">
                            <a href="/search" class="text-gray-700 hover:text-orange-600 transition">
                                <flux:icon.magnifying-glass class="w-5 h-5" />
                            </a>
                            @livewire('partials.cart-button')
                            @auth
                                <div class="relative group">
                                    <button class="flex items-center space-x-2 text-gray-700 hover:text-orange-600">
                                        <span class="text-sm">{{ Str::limit(Auth::user()->name, 15) }}</span>
                                        <flux:icon.user class="w-5 h-5" />
                                    </button>
                                    <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                                        <a href="/my-order" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Pesanan Saya</a>
                                        <a href="/checkout" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Checkout</a>
                                        <form method="POST" action="/logout">
                                            @csrf
                                            <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                Logout
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @else
                                <a href="/login" class="bg-amber-500 text-white px-4 py-2 rounded-lg hover:bg-amber-700 transition text-sm">
                                    Login
                                </a>
                            @endauth
                        </div>
            
                        <!-- Mobile Menu Button -->
                        <div class="md:hidden flex items-center space-x-3">
                            @livewire('partials.cart-button')
                            
                            <button id="mobileMenuButton" class="text-gray-700 hover:text-orange-600 focus:outline-none">
                                <flux:icon.bars-3 id="menuIcon"/>
                                <flux:icon.x-mark id="closeIcon" class="hidden"/>

                            </button>
                        </div>
                    </div>
            
                    <!-- Mobile Menu Dropdown -->
                    <div id="mobileMenu" class="md:hidden hidden bg-white border-t border-gray-100 py-4 space-y-3">
                        <!-- Search Link -->
                        <a href="/search" class="flex items-center space-x-3 px-4 py-2 text-gray-700 hover:bg-gray-50 rounded-lg transition">
                            <flux:icon.magnifying-glass class="w-5 h-5" />
                            <span>Cari Menu/Tenant</span>
                        </a>
            
                        @auth
                            <!-- User Info -->
                            <div class="px-4 py-2 border-b border-gray-100">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center">
                                        <span class="text-orange-600 font-semibold">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-800">{{ Auth::user()->name }}</p>
                                        <p class="text-xs text-gray-500">{{ Auth::user()->email }}</p>
                                    </div>
                                </div>
                            </div>
            
                            <a href="/my-order" class="flex items-center space-x-3 px-4 py-2 text-gray-700 hover:bg-gray-50 rounded-lg transition">
                                <span>Pesanan Saya</span>
                            </a>
                            <a href="/checkout" class="flex items-center space-x-3 px-4 py-2 text-gray-700 hover:bg-gray-50 rounded-lg transition">
                                <span>Checkout</span>
                            </a>
            
                            <form method="POST" action="/logout" class="px-4">
                                @csrf
                                <button type="submit" class="w-full flex items-center space-x-3 py-2 text-red-600 hover:bg-red-50 rounded-lg transition">
                                    <span>Logout</span>
                                </button>
                            </form>
                        @else
                            <a href="/login" class="flex items-center justify-center mx-4 px-4 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-700 transition">
                                Login
                            </a>
                        @endauth
                    </div>
                </div>
            </nav>
    
            <!-- Main Content -->
            <main class="bg-gray-50">
                {{ $slot }}
            </main>

            <div
            x-data="{
                notifys: [],
                push(message, type) {
                    const id = Date.now() + Math.random();
                    this.notifys.push({ id, message, type: type || 'info' });
                    setTimeout(() => this.remove(id), 3000);
                },
                remove(id) {
                    this.notifys = this.notifys.filter(n => n.id !== id);
                }
            }"
            x-on:notify.window="push($event.detail.message, $event.detail.type)"
            class="fixed bottom-4 right-4 z-50 space-y-2 w-full max-w-sm px-4 sm:px-0"
        >
            <template x-for="notify in notifys" :key="notify.id">
                <div
                    x-show="true"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="flex items-center gap-3 text-white px-4 py-3 rounded-xl shadow-lg"
                    :class="{
                        'bg-green-500': notify.type === 'success',
                        'bg-red-500': notify.type === 'error',
                        'bg-amber-500': notify.type === 'warning',
                        'bg-blue-500': notify.type === 'info'
                    }"
                >
                    <span x-text="notify.message" class="flex-1 text-sm font-medium"></span>

                    <button
                        @click="remove(notify.id)"
                        class="shrink-0 text-white/80 hover:text-white transition"
                    >
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </template>
        </div>
                
            <!-- Footer -->
            <footer class="bg-white border-t mt-16">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <p class="text-center text-gray-500 text-sm">
                        &copy; {{ date('Y') }} UCIC Student Business Corner. All rights reserved.
                    </p>
                </div>
            </footer>
        </div>

        @livewireScripts
        <script>
            const mobileMenuButton = document.getElementById('mobileMenuButton');
            const mobileMenu = document.getElementById('mobileMenu');
            const menuIcon = document.getElementById('menuIcon');
            const closeIcon = document.getElementById('closeIcon');
        
            if (mobileMenuButton) {
                mobileMenuButton.addEventListener('click', () => {
                    mobileMenu.classList.toggle('hidden');
                    menuIcon.classList.toggle('hidden');
                    closeIcon.classList.toggle('hidden');
                });
            }
        </script>
    </body>
</html>
