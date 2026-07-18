<?php

use Livewire\Component;
use App\Models\Product;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

new class extends Component
{
    public $perPage = 10;

    public function loadMore()
    {
        $this->perPage += 10;
    }

    #[Computed]
    public function products()
    {
        return Product::with([
                'tenant:id,store_name,is_open',
                'category:id,name'
            ])
            ->latest()
            ->take($this->perPage)
            ->get();
    }

    public function addToCart($productId)
    {
        $product = Product::with('tenant')->find($productId);

        if (!$product) {
            $this->dispatch('notify', 
                message: "Produk tidak ditemukan", 
                type: 'error'
            );
            return;
        }

        if (!$product->is_available || !$product->tenant->is_open) {
            $this->dispatch('notify', 
                message: "Maaf, produk ini sedang tidak tersedia", 
                type: 'error'
            );
            return;
        }

        $cart = session()->get('cart', []);
        $itemKey = $product->id;

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
                'notes' => '',
                'selectedDay' => '',
                'selectedTime' => '',
            ];
        }

        session()->put('cart', $cart);

        $this->dispatch('cartUpdated');

        $this->dispatch('notify', 
            message: "{$product->name} berhasil ditambahkan ke keranjang!", 
            type: 'success'
        );
    }

    #[On('echo:products,ProductAvailabilityChanged')]
    public function handleProductStatusUpdated($event)
    {
        $item = $this->products->firstWhere('id', $event['productId']);

        if ($item) {
            $item->is_available = $event['isAvailable'];
        }
    }

    #[On('echo:tenants.status,.store-status-changed')]
    public function handleStoreStatusChanged($event)
    {
        $tenantId = (int) $event['tenant_id'];
        $affectedProducts = $this->products->where('tenant_id', $tenantId);

        foreach ($affectedProducts as $product) {
            $product->tenant->is_open = $event['is_open'];
        }
    }
};
?>
@placeholder
<div class="justify-center h-screen flex py-6">
    <flux:icon.loading />
</div>
@endplaceholder
<div>
        <!-- GRID -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($this->products as $product)
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
                            <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center">
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
                            <span class="text-orange-600 font-bold ml-2 whitespace-nowrap">
                                {{ $product->formatted_price }}
                            </span>
                        </div>
                        
                        <p class="text-sm text-gray-600 line-clamp-2 mb-3 min-h-[40px]">
                            {{ $product->description ?? 'Tidak ada deskripsi' }}
                        </p>
                        @if($product->is_available && $product->tenant->is_open)
                            @if ($product->is_preorder)
                            <div class="flex items-center justify-between">
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
                            </div>
                            @else
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-1 text-xs text-gray-500">
                                    <flux:icon.clock class="size-4" />
                                    <span>Ready-To Serve</span>
                                </div>
                            </div>
                            @endif
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
    
        {{-- <!-- LOADING -->
        <div wire:loading.flex class="justify-center py-6">
            <flux:icon.loading />
        </div> --}}
    
        <!-- LOAD MORE BUTTON -->
        @if($this->products->count() >= $perPage)
            <div class="flex justify-center mt-8">
                <button 
                    wire:click="loadMore"
                    wire:loading.attr="disabled"
                    class="bg-orange-600 text-white px-6 py-2 rounded-lg hover:bg-orange-700 transition"
                >
                    <span wire:loading.remove wire:target='loadMore'>Load More</span>
                    <span wire:loading wire:target='loadMore'><flux:icon.loading /></span>
                </button>
            </div>
        @endif
</div>