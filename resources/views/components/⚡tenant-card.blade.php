<?php

use Livewire\Component;
use App\Models\Tenant;
use App\Models\Product;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

new class extends Component
{
    use WithPagination;

    public $perPage = 3;
    public function loadMore()
    {
        $this->perPage += 3;
    }
    
    #[Computed]
    public function tenants()
    {
        return Tenant::with(['products' => function($query) {
            $query->limit(3)
                  ->latest();
        }])->paginate($this->perPage);
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
        $tenant = $this->tenants->getCollection()
            ->first(function ($tenant) use ($event) {
                return $tenant->products->contains('id', $event['productId']);
            });

        if ($tenant) {
            $product = $tenant->products->firstWhere('id', $event['productId']);
            
            if ($product) {
                $product->is_available = $event['isAvailable'];
            }
        }
    }

    #[On('echo:tenants.status,.store-status-changed')]
    public function handleStoreStatusChanged($event)
    {
        $tenant = $this->tenants->firstWhere('id', $event['tenant_id']);
        if (!$tenant) {
            return;
        }
        $tenant->is_open = (bool) $event['is_open'];
    }
};
?>

@placeholder
<div class="justify-center h-screen flex py-6">
    <flux:icon.loading />
</div>
@endplaceholder

<div>
    <div class="space-y-8">
        @foreach ($this->tenants as $tenant)
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300">
                <!-- Tenant Header -->
                <div class="bg-gradient-to-r from-orange-500 to-orange-600 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <!-- Tenant Avatar -->
                            <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center shadow-lg">
                                @if($tenant->tenant_img)
                                    <img src="{{ Storage::disk('tsbc_disk')->url($tenant->tenant_img) }}" 
                                         alt="{{ $tenant->store_name }}"
                                         class="w-14 h-14 rounded-full object-cover">
                                @else
                                    <flux:icon.building-storefront class=" text-orange-500"/>
                                @endif
                            </div>
                            
                            <!-- Tenant Info -->
                            <div>
                                <h2 class="text-xl md:text-2xl font-bold text-white">
                                    {{ $tenant->store_name }}
                                </h2>
                                <h2 class="text-xl md:text-1xl font-bold text-white">
                                    Tenant {{ $tenant->tenant_code }}
                                </h2>
                            </div>
                        </div>
                        
                        <!-- Tenant Status & Action -->
                        <div class="flex items-center space-x-3">
                            @if($tenant->is_open)
                                <span class="bg-green-500 text-white px-3 py-1 rounded-full text-xs font-semibold">
                                    ● Buka
                                </span>
                            @else
                                <span class="bg-red-500 text-white px-3 py-1 rounded-full text-xs font-semibold">
                                    ● Tutup
                                </span>
                            @endif
                            
                            <a href="/tenant/{{ $tenant->tenant_code }}" 
                               class="bg-amber-500 hover:bg-white/30 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                                Lihat
                            </a>
                        </div>
                    </div>
                </div>
                <!-- Tenant Description -->
                @if($tenant->description)
                    <div class="px-6 py-3 bg-gray-50 border-b">
                        <p class="text-gray-600 text-sm">
                            {{ $tenant->description }}
                        </p>
                    </div>
                @endif
                <!-- Products Grid -->
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">
                            Latest Menu
                        </h3>
                        <a href="/tenant/{{ $tenant->tenant_code }}" 
                           class="text-orange-600 hover:text-orange-700 text-sm font-medium transition flex items-center space-x-1">
                            <span>Lihat semua menu </span>
                            <flux:icon.chevron-right class="size-4"/>
                        </a>
                    </div>
                
                    @if($tenant->products && $tenant->products->count() > 0)
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($tenant->products as $product)
                            <div class="border border-gray-200 rounded-lg overflow-hidden hover:shadow-md transition group h-36 bg-white">
                                <div class="flex h-full">
                                    <div class="w-28 bg-gray-100 relative overflow-hidden shrink-0">
                                        @if($product->product_img)
                                            <img src="{{ Storage::disk('tsbc_disk')->url($product->product_img) }}" 
                                                alt="{{ $product->name }}"
                                                class="w-full h-full object-cover group-hover:scale-110 transition duration-300">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center bg-gray-50">
                                                <flux:icon.photo class="text-gray-400 size-8"/>
                                            </div>
                                        @endif
                                    </div>
                                    
                                    <div class="flex-1 p-3 flex flex-col justify-between overflow-hidden">
                                        <div>
                                            <a href="/menu/{{ $product->slug }}" class="block">
                                                <p class="font-semibold text-gray-800 hover:text-orange-600 transition truncate">{{ $product->name }}</p>
                                                <p class="text-xs text-gray-500 mt-1 line-clamp-1">
                                                    {{ $product->description ?? 'Tidak ada deskripsi' }}
                                                </p>
                                            </a>
                                            
                                            <div class="mt-1">
                                                <span class="text-orange-600 font-bold text-sm">
                                                    {{ $product->formatted_price }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="flex items-end justify-between mt-auto pt-2 border-t border-gray-100">
                                            @if($product->is_available && $tenant->is_open)
                                                <div class="flex-1 pr-2 overflow-hidden">
                                                    @if ($product->is_preorder)
                                                    <div class="flex flex-col text-xs text-gray-500">
                                                        <div class="flex items-center space-x-1 mb-1">
                                                            <flux:icon.clock class="size-3 shrink-0" />
                                                            <span class="text-[10px] truncate">Pre-order untuk:</span>
                                                        </div>
                                                        <div class="flex flex-wrap gap-1 h-4 overflow-hidden">                                                     
                                                            @if($product->tenant->pick_slot->isNotEmpty())
                                                                @foreach($product->tenant->pick_slot->take(3) as $day)
                                                                    <span class="bg-orange-100 text-orange-700 px-1 py-0.5 rounded text-[10px] font-medium leading-none whitespace-nowrap">
                                                                        {{ $day->dayPickup }}
                                                                    </span>
                                                                @endforeach
                                                                @if($product->tenant->pick_slot->count() >= 4)
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
                                                    <div class="flex items-center space-x-1 text-xs text-gray-500 pb-1">
                                                        <flux:icon.hand-platter class="size-4 shrink-0" />
                                                        <span class="text-[11px] truncate">Ready-To Serve</span>
                                                    </div>
                                                    @endif                                              
                                                </div>
                                                    @auth 
                                                    @if ($product->tenant->pick_slot->isNotEmpty() && $product->is_preorder)                                                               
                                                    <button 
                                                        wire:click="addToCart({{ $product->id }})"
                                                        wire:loading.attr="disabled"
                                                        wire:target="addToCart({{ $product->id }})"
                                                        class="bg-amber-500 text-white px-3 py-1.5 rounded-lg text-xs 
                                                        hover:bg-amber-700 transition flex items-center space-x-1 shrink-0"
                                                    >
                                                        <flux:icon.loading class="size-5" wire:loading wire:target="addToCart({{ $product->id }})"/>
                                                        <flux:icon.plus class="size-5" wire:loading.remove wire:target="addToCart({{ $product->id }})" />
                                                        <span>Pesan</span>
                                                    </button>
                                                    @endif
                                                    @endauth
                                                
                                            @else
                                                <button disabled class="w-full bg-gray-200 text-gray-500 px-3 py-1.5 rounded-lg text-xs cursor-not-allowed text-center">
                                                    Habis / Tutup
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                        <div class="text-center py-8 bg-gray-50 rounded-lg">
                            <p class="text-gray-500">Belum ada menu yang tersedia</p>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
    <!-- Load More Button with Infinite Scroll -->
    @if ($this->tenants->hasMorePages())
        <div x-intersect="$wire.loadMore()" class="flex justify-center py-8">
                <flux:icon.loading/>
            </div>
    @else
        <div class="text-center py-8">
            <div class="items-center space-x-2 text-gray-400">
                <span>Sudah mencapai akhir daftar tenant</span>
            </div>
        </div>
    @endif
    </div>
</div>