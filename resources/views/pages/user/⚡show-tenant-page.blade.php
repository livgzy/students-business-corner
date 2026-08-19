<?php

use Livewire\Component;
use App\Models\Tenant;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

new class extends Component
{
    public Tenant $tenant;

    public function addToCart($productId)
    {
        $product = Product::with('tenant')->find($productId);

        if (!$product) {
            $this->dispatch('notify', message: "Produk tidak ditemukan", type: 'error');
            return;
        }

        if (!$product->is_available || !$product->tenant->is_open) {
            $this->dispatch('notify', message: "Maaf, produk ini sedang tidak tersedia", type: 'error');
            return;
        }

        $cart = session()->get('cart', []);
        $itemKey = $product->id;
        $isPreorder = (bool) $product->is_preorder;

        $tenantItems = collect($cart)->where('tenant_id', $product->tenant_id);

        if ($tenantItems->isNotEmpty()) {
            $hasDifferentOrderType = $tenantItems->contains(function ($item) use ($isPreorder) {
                return (bool) ($item['is_preorder'] ?? false) !== $isPreorder;
            });

            if ($hasDifferentOrderType) {
                $existingType = $tenantItems->first()['is_preorder'] ?? false;

                $message = $existingType
                    ? "Tenant {$product->tenant->store_name} sudah memiliki menu Pre-order. Menu biasa tidak dapat ditambahkan ke tenant yang sama."
                    : "Tenant {$product->tenant->store_name} sudah memiliki menu Order biasa. Menu Pre-order tidak dapat ditambahkan ke tenant yang sama.";

                $this->dispatch('notify', message: $message, type: 'error');
                return;
            }
        }

        if (isset($cart[$itemKey])) {
            $cart[$itemKey]['quantity'] += 1;
        } else {
            $cart[$itemKey] = [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'quantity' => 1,
                'image' => $product->product_img,
                'tenant_id' => $product->tenant_id,
                'tenant_name' => $product->tenant->store_name,
                'tenant_code' => $product->tenant->tenant_code,
                'is_preorder' => $isPreorder,
                'notes' => '',
                'selectedDay' => '',
                'selectedTime' => '',
            ];
        }

        session()->put('cart', $cart);

        $this->dispatch('cartUpdated');

        $this->dispatch('notify', message: "{$product->name} berhasil ditambahkan ke keranjang!", type: 'success');
    }

    #[On('echo:products,ProductAvailabilityChanged')]
    public function handleProductStatusUpdated($event)
    {
        $item = $this->tenant->products->firstWhere('id', $event['productId']);

        if ($item) {
            $item->is_available = $event['isAvailable'];
        }
    }

    #[On('echo:tenants.status,.store-status-changed')]
    public function handleStoreStatusChanged($event)
    {
        $this->tenant->is_open = (bool) $event['is_open'];
    }

    public function render()
    {
        $products = $this->tenant->products;

        return $this->view([
            'tenant' => $this->tenant,
            'regular_products' => $products->where('is_preorder', 0),
            'preorder_products' => $products->where('is_preorder', 1)
        ])
        ->title("UCIC Student Business Corner | {$this->tenant->store_name}");;
    }
};
?>

<div>
    <!-- Hero Section - Tenant Banner -->
    <div class="relative bg-gradient-to-r from-orange-600 to-amber-600 overflow-hidden">
        
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-12">
            <div class="flex flex-col md:flex-row items-center gap-6">
                <div class="w-32 h-32 md:w-40 md:h-40 bg-white rounded-2xl shadow-xl overflow-hidden flex-shrink-0">
                    @if($tenant->tenant_img)
                        <img src="{{ Storage::disk('tsbc_disk')->url($tenant->tenant_img) }}" 
                             alt="{{ $tenant->store_name }}"
                             class="w-full h-full object-contain">
                    @else
                        <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-orange-100 to-amber-100">
                            <flux:icon.building-storefront class="size-20 text-orange-500"/>
                        </div>
                    @endif
                </div>

                <!-- Tenant Info -->
                <div class="flex-1 text-center md:text-left">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <h2 class="text-2xl md:text-2xl font-bold text-white">
                                Tenant {{  $tenant->tenant_code }}
                            </h2>
                            <h1 class="text-2xl md:text-3xl font-bold text-white mb-2">
                                {{ $tenant->store_name }}
                            </h1>
                            <div class="flex flex-wrap items-center gap-3 justify-center md:justify-start">
                                @if($tenant->is_open)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-green-500 text-white">
                                        Buka
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-red-600 text-white">
                                        Tutup
                                    </span>
                                @endif
                                <span class="text-white/80 text-sm">
                                    {{ $tenant->products->count() }} Menu
                                </span>
                            </div>
                        </div>
                        
                        <!-- Contact Button -->
                        @if($tenant->phone)
                            <a href="https://wa.me/{{ $tenant->phone }}" 
                               target="_blank"
                               class="inline-flex items-center gap-1 px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg transition">
                               <flux:icon.phone/> Hubungi Tenant
                            </a>
                        @endif
                    </div>

                    <!-- Description -->
                    @if($tenant->description)
                        <p class="text-white/90 mt-4 max-w-2xl">
                            {{ $tenant->description }}
                        </p>
                    @endif

                </div>
            </div>
        </div>
    </div>

    <!-- Menu Section -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-12">
        
        <!-- Section Title -->
        <div class="text-center mb-4">
            <h2 class="text-2xl md:text-3xl font-bold text-gray-800">
                Menu Kami
            </h2>
        </div>

        @if($tenant->products->count() > 0)
            @if ($preorder_products->isNotEmpty())
            <h3 class="text-1xl md:text-2xl font-bold text-gray-800 mb-4">
                Menu Pre-Order
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach($preorder_products as $product)
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-all duration-300 group">
                        <a href="/menu/{{ $product->slug }}" wire:navigate>
                            <div class="relative h-48 bg-gray-200 overflow-hidden">
                                @if($product->product_img)
                                    <img src="{{ Storage::disk('tsbc_disk')->url($product->product_img) }}" 
                                        alt="{{ $product->name }}"
                                        class="w-full h-full object-cover group-hover:scale-110 transition duration-300">
                                @else
                                    <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200">
                                        <flux:icon.photo class="size-12 text-gray-400"/>
                                    </div>
                                @endif
                                
                                @if(!$product->is_available || !$product->tenant->is_open)
                                    <div class="absolute inset-0 bg-black bg-opacity-10 flex items-center justify-center">
                                        <span class="text-white font-semibold">Tidak Tersedia</span>
                                    </div>
                                @endif
                                
                                @if($product->category)
                                    <div class="absolute top-2 left-2">
                                        <span class="bg-orange-600 text-white text-xs px-2 py-1 rounded-full">
                                            {{ $product->category->name }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </a>    
                            <div class="p-4">
                                <div class="flex items-start justify-between mb-2">
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-gray-800 group-hover:text-orange-600 transition line-clamp-1">
                                            {{ $product->name }}
                                        </h3>
                                        <p class="text-sm text-gray-500 mt-1">
                                            {{ $product->tenant->store_name }}
                                        </p>
                                    </div>
                                    <span class="text-black-600 font-bold ml-2 whitespace-nowrap">
                                        {{ $product->formatted_price }}
                                    </span>
                                </div>
                                
                                <p class="text-sm text-gray-600 line-clamp-2 mb-3 min-h-[40px]">
                                    {{ $product->description ?? 'Tidak ada deskripsi' }}
                                </p>
                                
                                <div class="flex items-center justify-between">
                                    
                                    @if($product->is_available && $product->tenant->is_open)
                                        <div class="flex flex-col justify-center text-xs text-gray-500">
                                            <div class="flex items-center space-x-1 mb-1">
                                                <flux:icon.clock class="size-4" />
                                                <span>Pre-order untuk:</span>
                                            </div>
                                            
                                            <div class="flex flex-wrap gap-1">
            
                                                @if($product->tenant->pick_slot->isNotEmpty())
                                                    @foreach($product->tenant->pick_slot->take(2) as $day)
                                                        <span class="bg-orange-100 text-orange-700 px-1.5 py-1 rounded text-[12px] mt-1 font-medium">
                                                            {{ $day->dayPickup }}
                                                        </span>
                                                    @endforeach

                                                    @if($product->tenant->pick_slot->count() >= 3)
                                                    <div class="text-gray-500 text-[12px] font-bold mt-1 select-none clear-both">
                                                        ...
                                                    </div>
                                                    @endif
                                                @else
                                                    <span class="text-gray-400 text-[10px]">Belum diatur</span>
                                                @endif
                                            </div>
                                        </div>
                                        @auth         
                                        @if ($product->tenant->pick_slot->isNotEmpty())                                 
                                        <button 
                                            wire:click="addToCart({{ $product->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="addToCart({{ $product->id }})"
                                            class="cursor-pointer bg-amber-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-amber-700 transition flex items-center gap-1"
                                        >
                                            <flux:icon.loading class="size-5" wire:loading wire:target="addToCart({{ $product->id }})"/>
                                            <flux:icon.plus class="size-5" wire:loading.remove wire:target="addToCart({{ $product->id }})" />
                                            Pesan
                                        </button>
                                        @endif
                                        @endauth
                                    @else
                                        <div class="flex items-center space-x-1 text-xs text-gray-500">
                                            <flux:icon.clock class="size-4" />
                                            <span>Menu Habis</span>
                                        </div>
                                        <button disabled class="bg-gray-300 text-gray-500 px-4 py-2 rounded-lg text-sm cursor-not-allowed">
                                            Habis
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                @endforeach
            </div>
            @endif

            @if ($regular_products->isNotEmpty())
            <hr class="my-8">
            <h3 class="text-1xl md:text-2xl font-bold text-gray-800 mb-4">
                Menu Di Tenant
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach($regular_products as $product)
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-all duration-300 group">
                        <a href="/menu/{{ $product->slug }}" wire:navigate>
                            <div class="relative h-48 bg-gray-200 overflow-hidden">
                                @if($product->product_img)
                                    <img src="{{ Storage::disk('tsbc_disk')->url($product->product_img) }}" 
                                        alt="{{ $product->name }}"
                                        class="w-full h-full object-cover group-hover:scale-110 transition duration-300">
                                @else
                                    <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200">
                                        <flux:icon.photo class="size-12 text-gray-400"/>
                                    </div>
                                @endif
                                
                                @if(!$product->is_available || !$product->tenant->is_open)
                                    <div class="absolute inset-0 bg-black bg-opacity-10 flex items-center justify-center">
                                        <span class="text-white font-semibold">Tidak Tersedia</span>
                                    </div>
                                @endif
                                
                                @if($product->category)
                                    <div class="absolute top-2 left-2">
                                        <span class="bg-orange-600 text-white text-xs px-2 py-1 rounded-full">
                                            {{ $product->category->name }}
                                        </span>
                                    </div>
                                    @if(!$product->is_available)
                                    <div class="absolute top-2 right-2">
                                        <span class="bg-gray-600 text-white text-xs px-2 py-1 rounded-full">
                                            Habis
                                        </span>
                                    </div>
                                    @endif
                                    
                                @endif
                            </div>
                        </a> 
                            <div class="p-4">
                                <div class="flex items-start justify-between mb-2">
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-gray-800 group-hover:text-orange-600 transition line-clamp-1">
                                            {{ $product->name }}
                                        </h3>
                                        <p class="text-sm text-gray-500 mt-1">
                                            {{ $product->tenant->store_name }}
                                        </p>
                                    </div>
                                    <span class="text-black-600 font-bold ml-2 whitespace-nowrap">
                                        {{ $product->formatted_price }}
                                    </span>
                                </div>
                                
                                <p class="text-sm text-gray-600 line-clamp-2 mb-3 min-h-[40px]">
                                    {{ $product->description ?? 'Tidak ada deskripsi' }}
                                </p>
                                @if($product->is_available && $product->tenant->is_open)
                                <div class="flex items-center justify-between">
                                    
                                    <div class="flex items-center space-x-1 text-xs text-gray-500">
                                        <flux:icon.hand-platter class="size-4" />
                                        <span>Ready To serve</span>
                                    </div>
                                    @auth
                                    <button
                                        wire:click="addToCart({{ $product->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="addToCart({{ $product->id }})"
                                        class="cursor-pointer bg-orange-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-orange-700 transition flex items-center gap-1"
                                    >
                                        <flux:icon.loading
                                            class="size-5"
                                            wire:loading
                                            wire:target="addToCart({{ $product->id }})"
                                        />
                                        <flux:icon.plus
                                            class="size-5"
                                            wire:loading.remove
                                            wire:target="addToCart({{ $product->id }})"
                                        />
                                        Pesan
                                    </button>
                                @endauth
                                </div>
                                @else
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-1 text-xs text-gray-500">
                                        <flux:icon.x-circle class="size-4" />
                                        <span>Menu Habis</span>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                @endforeach
            </div>
            @endif
            
        @else
            <!-- Empty State -->
            <div class="text-center py-16">
                <h3 class="text-lg font-semibold text-gray-700 mb-2">Belum Ada Menu</h3>
                <p class="text-gray-500">
                    Belum ada menu yang tersedia dari tenant ini
                </p>
            </div>
        @endif
    </div>
</div>