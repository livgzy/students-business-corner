<?php

use Livewire\Component;

new class extends Component
{
    
};
?>

<div class="relative bg-gradient-to-r from-orange-500 to-orange-600 overflow-hidden">
    <div class="absolute inset-0 bg-black opacity-10"></div>
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 md:py-24">
        <div class="text-center">
            <h1 class="text-3xl md:text-5xl font-bold text-white mb-4">
                UCIC Student Business Corner
            </h1>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="/menu" class="bg-white text-black-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition duration-300">
                    Lihat Menu
                </a>
                @guest
                    <a href="/login" class="border-2 border-white text-white px-6 py-3 rounded-lg font-semibold hover:bg-white/10 transition duration-300">
                        Login Sekarang
                    </a>
                @endguest
            </div>
        </div>
    </div>
</div>