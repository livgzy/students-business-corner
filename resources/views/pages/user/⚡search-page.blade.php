<?php

use Livewire\Component;
use Livewire\Attributes\Url;
use App\Models\Tenant;
use App\Models\Product;
use Livewire\Attributes\On;


new class extends Component
{

    #[Url(history: true)] 
    public $query = '';
    
    public $results = [];
    public $tenants = [];
    public $products = [];


    public function mount()
    {
        if (!empty(trim($this->query))) {
            $this->performSearch();
        }
    }

    private function search($query)
    {
        if (trim($query) == '') {
            return [
                'tenants' => [],
                'products' => []
            ];
        }

        // Search tenants
        $tenants = Tenant::where('store_name', 'like', '%' . $query . '%')
            ->withCount('products')
            ->take(3)
            ->get();

        // Search products
        $products = Product::where('name', 'like', '%' . $query . '%')
            ->with(['tenant', 'category'])
            ->take(6)
            ->get();

        return [
            'tenants' => $tenants,
            'products' => $products
        ];
    }

    public function updatedQuery()
    {
        $this->results = $this->search($this->query);
        $this->tenants = $this->results['tenants'];
        $this->products = $this->results['products'];
    }

    public function performSearch()
    {
        $this->updatedQuery();
    }

    public function clearSearch()
    {
        $this->query = '';
        $this->tenants = [];
        $this->products = [];
    }

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
        $item = $this->products->firstWhere('id', $event['productId']);

        if ($item) {
            $item->is_available = $event['isAvailable'];
        }
    }

    public function render()
    {
        return $this->view([
            'tenants' => $this->tenants,
            'products' => $this->products,
            'query' => $this->query
        ])
        ->layout('layouts.app')
        ->title("Student Business Corner | Search");
    }
};
?>

<div>
    <div class="min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 md:py-8">
            
            
            <div class="w-full bg-white rounded-full border border-gray-300 shadow-sm hover:shadow-md transition-shadow mb-8">
                <div class="flex items-center w-full">
                    <div class="pl-4 md:pl-5">
                        <flux:icon.magnifying-glass/>
                    </div>
                    <!-- Search Input -->
                    <input 
                        wire:model.live.debounce.300ms="query"
                        type="text" 
                        placeholder="Cari menu atau tenant..."
                        class="flex-1 w-full px-3 py-3 md:py-4 text-gray-700 placeholder-gray-400 bg-transparent focus:outline-none text-sm md:text-base"
                    >
                    
                    <!-- Clear Button (shown when query not empty) -->
                    @if($query)
                        <button 
                            wire:click="clearSearch"
                            class="p-2 text-gray-400 hover:text-gray-600 transition"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    @endif
                    
                    <!-- Search Button -->
                    <button wire:click='performSearch' class="bg-amber-500 hover:bg-amber-600 text-white font-semibold py-2 md:py-3 px-5 md:px-8 rounded-full transition duration-200 text-sm md:text-base mx-1 md:mx-2 whitespace-nowrap">
                        Cari
                    </button>
                    
                </div>
            </div>

            @if($query)
                <div wire:loading.remove wire:target='query' class="space-y-8">
                    
                    @if(count($tenants) > 0)
                        <div>
                            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                                <svg class="w-6 h-6 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                Tenant Terkait
                            </h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach($tenants as $tenant)
                                    <a href="/tenant/{{ $tenant->tenant_code }}" 
                                       class="group bg-white rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden border border-gray-100">
                                        <div class="p-4">
                                            <div class="flex items-center justify-between mb-2">
                                                <h3 class="font-semibold text-gray-800 group-hover:text-orange-600 transition">
                                                    {{ $tenant->store_name }}
                                                </h3>
                                                @if($tenant->is_open)
                                                    <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full">
                                                        ● Buka
                                                    </span>
                                                @else
                                                    <span class="text-xs bg-red-100 text-red-700 px-2 py-1 rounded-full">
                                                        ● Tutup
                                                    </span>
                                                @endif
                                            </div>
                                            <p class="text-sm text-gray-500 line-clamp-2 mb-2">
                                                {{ $tenant->description ?? 'Tidak ada deskripsi' }}
                                            </p>
                                            <div class="flex items-center justify-between text-xs text-gray-400">
                                                <span>{{ $tenant->products_count }} Menu</span>
                                                <span class="group-hover:translate-x-1 transition">Lihat →</span>
                                            </div>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if(count($products) > 0)
                    <div>
                        <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                            <svg class="w-6 h-6 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            Menu
                        </h2>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($products as $product)
                                <div class="bg-white rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden border border-gray-100 group h-36">
                                    <div class="flex h-full">
                                        
                                        <div class="w-28 bg-gray-100 relative overflow-hidden shrink-0">
                                            @if($product->product_img)
                                                <img src="{{ Storage::disk('tsbc_disk')->url($product->product_img) }}" 
                                                    alt="{{ $product->name }}"
                                                    class="w-full h-full object-cover group-hover:scale-110 transition duration-300">
                                            @else
                                                <div class="w-full h-full flex items-center justify-center">
                                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                    </svg>
                                                </div>
                                            @endif
                                        </div>
                                        
                                        <div class="flex-1 p-3 flex flex-col justify-between overflow-hidden">
                                            <div>
                                                <a href="/menu/{{ $product->slug }}" class="block">
                                                    <h3 class="font-semibold text-gray-800 hover:text-orange-600 transition truncate">
                                                        {{ $product->name }}
                                                    </h3>
                                                    <p class="text-xs text-gray-500 mt-0.5 truncate">
                                                        {{ $product->tenant->store_name }}
                                                    </p>
                                                </a>
                                                
                                                <div class="flex items-center mt-1">
                                                    <span class="text-orange-600 font-bold text-sm">
                                                        {{ $product->formatted_price }}
                                                    </span>
                                                    @if($product->category)
                                                        <span class="text-xs text-gray-400 ml-2 truncate">
                                                            • {{ $product->category->name }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                            
                                            <div class="flex items-end justify-between mt-auto pt-2 border-t border-gray-50">
                                                @if($product->is_available && $product->tenant->is_open)
                                                    <div class="flex-1 pr-2 overflow-hidden">
                                                        @if ($product->is_preorder)
                                                            <div class="flex flex-col text-xs text-gray-500">
                                                                <div class="flex items-center space-x-1 mb-1">
                                                                    <flux:icon.clock class="size-3 shrink-0" />
                                                                    <span class="text-[10px] truncate">Pre-order:</span>
                                                                </div>
                                                                <div class="flex flex-wrap gap-1 h-4 overflow-hidden">
                                                                    {{-- @php
                                                                        $days = is_array($product->dayPreorder) 
                                                                                ? collect($product->dayPreorder)->take(2)
                                                                                : json_decode(collect($product->dayPreorder)->take(2), true);
                                                                    @endphp --}}
                                                                    
                                                                    @if($product->tenant->pick_slot->isNotEmpty())
                                                                        @foreach($product->tenant->pick_slot->take(3) as $day)
                                                                            <span class="bg-orange-100 text-orange-700 px-1 py-0.5 rounded text-[9px] font-medium leading-none whitespace-nowrap">
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
                                                        @else
                                                            <div class="flex items-center justify-between w-full gap-2">
                                                                {{-- Status Ready-To-Serve --}}
                                                                <div class="flex items-center space-x-1 text-xs text-gray-500 min-w-0">
                                                                    <flux:icon.hand-platter class="size-4 shrink-0" />
                                                                    <span class="truncate">Ready-To-Serve</span>
                                                                </div>
                                                        
                                                                {{-- Tombol Pesan --}}
                                                                @auth
                                                                    <button
                                                                        wire:click="addToCart({{ $product->id }})"
                                                                        wire:loading.attr="disabled"
                                                                        wire:target="addToCart({{ $product->id }})"
                                                                        class="shrink-0 cursor-pointer bg-orange-500 text-white px-3 py-1.5 rounded-lg text-xs hover:bg-orange-700 transition flex items-center gap-1"
                                                                    >
                                                                        <flux:icon.loading
                                                                            class="size-4"
                                                                            wire:loading
                                                                            wire:target="addToCart({{ $product->id }})"
                                                                        />
                                                        
                                                                        <flux:icon.plus
                                                                            class="size-4"
                                                                            wire:loading.remove
                                                                            wire:target="addToCart({{ $product->id }})"
                                                                        />
                                                        
                                                                        <span>Pesan</span>
                                                                    </button>
                                                                @endauth
                                                            </div>
                                                        @endif   
                                                    </div>
                                                    @auth
                                                    @if ($product->tenant->pick_slot->isNotEmpty() && $product->is_preorder)              
                                                    <button 
                                                        wire:click="addToCart({{ $product->id }})"
                                                        wire:loading.attr="disabled"
                                                        wire:target="addToCart({{ $product->id }})"
                                                        class="bg-orange-500 hover:bg-orange-600 text-white px-3 py-1.5 rounded-lg text-xs transition flex items-center gap-1 shrink-0"
                                                    >
                                                        <flux:icon.loading class="size-5" wire:loading wire:target="addToCart({{ $product->id }})"/>
                                                        <flux:icon.plus class="size-5" wire:loading.remove wire:target="addToCart({{ $product->id }})" />
                                                        Pesan
                                                    </button>    
                                                    @endif                                                  
                                                    @endauth
                                                @else
                                                    <button disabled class="w-full bg-gray-200 text-gray-500 px-3 py-1.5 rounded-lg text-xs cursor-not-allowed text-center">
                                                        Tidak Tersedia
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                    <!-- No Results -->
                    @if(count($tenants) == 0 && count($products) == 0)
                        <div class="text-center py-12">
                            <h3 class="text-lg font-semibold text-gray-700 mb-2">Tidak ada hasil ditemukan</h3>
                            <p class="text-gray-500">
                                Tidak ada menu atau tenant dengan kata kunci <span class="font-medium">"{{ $query }}"</span>
                            </p>
                        </div>
                    @endif
                </div>
            @endif
            <div wire:loading.flex wire:target='query' class="justify-center py-6">
                <flux:icon.loading />
            </div>
        </div>
    </div>
</div>