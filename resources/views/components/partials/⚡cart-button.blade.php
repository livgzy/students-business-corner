<?php

use Livewire\Component;

new class extends Component
{
    public $cartCount = 0;

    protected $listeners = ['cartUpdated' => 'updateCartCount'];

    public function mount()
    {
        $this->updateCartCount();
    }

    public function updateCartCount()
    {
        $this->cartCount = session()->get('cart', []);
        $this->cartCount = count($this->cartCount);
    }
    };
?>

<div>
    <a href="/cart" class="relative">
        <button class="flex items-center space-x-1 text-gray-700 hover:text-orange-600 transition">
            <flux:icon.shopping-cart />
            <span class="text-sm font-medium">Cart</span>
        </button>
        
        @if($cartCount > 0)
            <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                {{ $cartCount }}
            </span>
        @endif
    </a>
</div>