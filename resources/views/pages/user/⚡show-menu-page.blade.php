<?php

use Livewire\Attributes\On;
use Livewire\Component;
use App\Models\Product;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;

new class extends Component
{
    public Product $product;

    #[Validate('required|min:1')] 
    public $quantity = 1;
    public $specialInstructions = '';

    #[Validate('required',  message: 'Mohon Isi Hari pengambilan')]
    public $selectedDay = '';
    #[Validate('required',  message: 'Mohon Isi Jam pengambilan')]
    public $selectedTime = '';
    public $slotStart = null;
    public $slotEnd = null;

    public $availableTimes = [];

    public function updatedSelectedDay($day)
    {
        $today = now()->locale('id')->translatedFormat('l');

        if (strcasecmp($day, $today) === 0) {
            $this->selectedDay = null;
            $this->addError('selectedDay', 'Hari ini tidak tersedia untuk pickup.');
            return;
        }

        $this->selectedTime = '';

        if (empty($day)) return;

        $slot = $this->product->tenant->pick_slot->where('dayPickup', $day)->first();

        if ($slot) {
            $this->slotStart = \Carbon\Carbon::parse($slot->start_time)->format('H:i');
            $this->slotEnd = \Carbon\Carbon::parse($slot->end_time)->format('H:i');
        }

    }

    public function incrementQuantity()
    {
        if ($this->quantity < 99) {
            $this->quantity++;
        }
    }

    public function decrementQuantity()
    {
        if ($this->quantity > 1) {
            $this->quantity--;
        }
    }

    #[Computed]
    public function recomendedMenu()
    {
        return Product::where('is_available', true)
            ->where('id', '!=', $this->product->id)
            ->where(function($query) {
                $query->where('category_id', $this->product->category_id)
                    ->orWhere('tenant_id', $this->product->tenant_id);
            })
            ->with(['tenant', 'category'])
            ->limit(4)
            ->get();
    }

    public function addToCart()
    {
        $this->validate();

        if ($this->slotStart && $this->slotEnd) {
            if ($this->selectedTime < $this->slotStart || $this->selectedTime > $this->slotEnd) {
                $this->addError('selectedTime', 'Jam pengambilan di luar slot yang tersedia.');
                return;
            }
        }

        $cart = session()->get('cart', []);
        
        $itemKey = $this->product->id;
        
        if (isset($cart[$itemKey])) {
            $cart[$itemKey]['quantity'] += $this->quantity;
            $cart[$itemKey]['notes'] = $this->specialInstructions;
            $cart[$itemKey]['selectedDay'] = $this->selectedDay;
            $cart[$itemKey]['selectedTime'] = $this->selectedTime;
        } else {
            $cart[$itemKey] = [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'price' => $this->product->price,
                'quantity' => $this->quantity,
                'image' => $this->product->product_img,
                'tenant_id' => $this->product->tenant_id,
                'tenant_name' => $this->product->tenant->store_name,
                'tenant_code' => $this->product->tenant->tenant_code,
                'notes' => $this->specialInstructions,
                'selectedDay' => $this->selectedDay,
                'selectedTime' => $this->selectedTime,
            ];
        }
        
        session()->put('cart', $cart);
        
        $this->dispatch('cartUpdated');
        
        $this->dispatch('notify', 
            message: "{$this->product->name} berhasil ditambahkan ke keranjang!", 
            type: 'success'
        );
        
        $this->quantity = 1;
        $this->specialInstructions = '';
        $this->selectedDay = '';
        $this->selectedTime = '';
    }

    #[On('echo:products,ProductAvailabilityChanged')]
    public function handleProductStatusUpdated($event)
    {
        if ($this->product->id === $event['productId']) {
            $this->product->is_available = $event['isAvailable'];
        }
    }

    #[On('echo:tenant.{product.tenant_id}.status,.store-status-changed')]
    public function handleStoreStatusChanged($event)
    {
        // Pastikan event ini memang untuk tenant dari produk yang sedang dibuka
        if ($event['tenant_id'] === (int) $this->product->tenant_id) {
            $this->product->tenant->is_open = $event['is_open'];
        }

        $this->dispatch('notify', 
            message: $event['is_open']
                ? "{$event['store_name']} baru saja BUKA."
                : "{$event['store_name']} baru saja TUTUP. Pemesanan dihentikan sementara.",
            type:  $event['is_open'] ? 'success' : 'warning' 
        );

        // Kosongkan form pre-order kalau tenant mendadak tutup,
        // supaya user tidak submit ke slot yang sudah tidak valid
        if (!$event['is_open']) {
            $this->selectedDay = '';
            $this->selectedTime = '';
            $this->slotStart = null;
            $this->slotEnd = null;
        }
    }

    public function render() 
    {

        return $this->view([])
        ->layout('layouts.app')
        ->title("Student Business Corner | {$this->product->name}");
    }
    
};
?>

<div>
    <!-- Breadcrumb -->
    <div class="bg-gray-100 py-3">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="flex items-center space-x-2 text-sm">
                <a href="{{ route('home') }}" class="text-gray-500 hover:text-orange-600 transition">Beranda</a>
                <flux:icon.chevron-right class="size-4 text-gray-500" />
                <a href="/tenant/{{ $product->tenant->tenant_code }}" class="text-gray-500 hover:text-orange-600 transition">
                    {{ $product->tenant->store_name }}
                </a>
                <flux:icon.chevron-right class="size-4 text-gray-500" />
                <span class="text-gray-700 font-medium">{{ $product->name }}</span>
            </nav>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Product Image Section -->
            <div class="space-y-4">
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                    <div class="relative h-96 md:h-[500px] bg-gradient-to-br from-gray-100 to-gray-200">
                        @if($product->product_img)
                            <img src="{{ Storage::disk('tsbc_disk')->url($product->product_img) }}" 
                                 alt="{{ $product->name }}"
                                 class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center">
                                <flux:icon.photo class="size-30 text-gray-400"/>
                            </div>
                        @endif
                        
                        @if(!$product->is_available || !$product->tenant->is_open)
                            <div class="absolute inset-0 bg-black bg-opacity-60 flex items-center justify-center">
                                <span class="text-white text-2xl font-bold px-6 py-3 bg-red-600 rounded-full">
                                    Tidak Tersedia
                                </span>
                            </div>
                        @endif
                        
                        <!-- Availability Badge -->
                        <div class="absolute top-4 right-4">
                            @if($product->is_available && $product->tenant->is_open)
                                <span class="bg-orange-500 text-white px-3 py-1 rounded-full text-sm font-semibold">
                                    Tersedia
                                </span>
                            @else
                                <span class="bg-red-500 text-white px-3 py-1 rounded-full text-sm font-semibold">
                                    Habis
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Info Section -->
            <div class="space-y-6">
                <!-- Tenant Info -->
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                        <flux:icon.building-storefront class="size-6 text-orange-500"/>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Tenant {{ $product->tenant->tenant_code }}</p>
                        <a href="/tenant/{{ $product->tenant->tenant_code }}" class="font-semibold text-gray-800 hover:text-orange-600 transition">
                            {{ $product->tenant->store_name }}
                        </a>
                    </div>
                </div>

                <!-- Product Title -->
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-2">{{ $product->name }}</h1>
                    <div class="flex items-center space-x-2">
                        @if($product->category)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                {{ $product->category->name }}
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Price -->
                <div class="border-t border-b border-gray-200 py-4">
                    <div class="flex items-baseline space-x-2">
                        <span class="text-3xl font-bold">{{ $product->formatted_price }}</span>
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Deskripsi</h3>
                    <p class="text-gray-600 leading-relaxed">
                        {{ $product->description ?? 'Tidak ada deskripsi untuk produk ini.' }}
                    </p>
                </div>

                @if($product->is_available && $product->tenant->is_open)
                @if ($product->is_preorder)                
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-5 space-y-4 shadow-sm">
                    <div class="flex items-center space-x-2 text-amber-900 font-bold border-b border-amber-200/60 pb-2">
                        <flux:icon.clock class="size-5 text-amber-600 shrink-0" />
                        <span>Jadwal Pengambilan Pre-order</span>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-semibold text-amber-800 uppercase tracking-wider mb-2">
                            1. Pilih Hari Pengambilan:
                        </label>
                        
                        <div class="flex flex-wrap gap-2">
                            @if($product->tenant->pick_slot->isNotEmpty())
                                @php
                                    // ambil nama hari ini dalam Bahasa Indonesia, misal: "Selasa"
                                    $today = now()->locale('id')->translatedFormat('l');
                                @endphp
                        
                                @foreach($product->tenant->pick_slot as $slot)
                                    @php
                                        $isToday = strcasecmp($slot->dayPickup, $today) === 0;
                                    @endphp
                        
                                    <label class="{{ $isToday ? 'cursor-not-allowed' : 'cursor-pointer' }}">
                                        <input
                                            type="radio"
                                            wire:model.live="selectedDay"
                                            value="{{ $slot->dayPickup }}"
                                            class="peer sr-only"
                                            @disabled($isToday)
                                        >
                                        <div class="px-4 py-2 rounded-lg text-sm font-semibold border transition-all duration-200
                                            {{ $isToday
                                                ? 'bg-gray-100 text-gray-400 border-gray-200 opacity-60'
                                                : 'bg-white text-amber-900 border-amber-200 shadow-sm hover:bg-amber-100' }}
                                            peer-checked:bg-amber-600 peer-checked:text-white peer-checked:border-amber-600 peer-checked:shadow-md">
                                            {{ $slot->dayPickup }}
                                        </div>
                                    </label>
                                @endforeach
                            @else
                                <span class="text-gray-400 text-sm italic">Belum ada jadwal hari dari tenant.</span>
                            @endif
                        </div>
                        @error('selectedDay') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                
                    @if(!empty($selectedDay))
                    <div class="pt-2 border-t border-amber-200/60 animate-fade-in">
                        <div class="flex justify-between items-center mb-1.5">
                            <label for="pickup_time" class="block text-xs font-semibold text-amber-800 uppercase tracking-wider">
                                2. Pilih Jam Pengambilan (Hari {{ $selectedDay }}):
                            </label>
                            <span wire:loading wire:target="selectedDay" class="text-xs text-amber-600 italic">
                                Memuat jam...
                            </span>
                        </div>
                    
                        <div wire:loading.remove wire:target="selectedDay">
                            <x-time-input 
                                model="selectedTime" 
                                min="{{ $slotStart }}" 
                                max="{{ $slotEnd }}" 
                            />
                        </div>
                    
                        @error('selectedTime') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    @endif
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">Jumlah Pesanan</h3>
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center border border-gray-300 rounded-lg">
                            <button 
                                wire:click="decrementQuantity"
                                wire:loading.attr="disabled"
                                class="px-4 py-2 text-gray-600 hover:bg-gray-100 transition rounded-l-lg"
                            >
                                <flux:icon.minus />
                            </button>
                            <span class="px-6 py-2 text-center font-semibold min-w-[60px]">{{ $quantity }}</span>
                            <button 
                                wire:click="incrementQuantity"
                                wire:loading.attr="disabled"
                                class="px-4 py-2 text-gray-600 hover:bg-gray-100 transition rounded-r-lg"
                            >
                                <flux:icon.plus />
                            </button>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">Catatan</h3>
                    <textarea 
                        wire:model="specialInstructions"
                        rows="3"
                        placeholder="Contoh: Tidak pedas, tambah sambal, dll..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                    ></textarea>
                </div>

                <div class="space-y-3">
                    @auth
                    @if($product->tenant->pick_slot->isNotEmpty())
                    <button 
                        wire:click="addToCart"
                        wire:loading.attr="disabled"
                        class="w-full bg-amber-500 text-white px-6 py-3 rounded-lg font-semibold hover:bg-orange-700 transition flex items-center justify-center space-x-2 cursor-pointer"
                    >
                        <flux:icon.shopping-cart />
                        <span>Tambah ke Keranjang</span>
                    </button>
                    
                        {{-- <button 
                            wire:click="buyNow"
                            wire:loading.attr="disabled"
                            class="w-full border-2 border-orange-600 text-orange-600 px-6 py-3 rounded-lg font-semibold hover:bg-orange-50 transition cursor-pointer"
                        >
                            Beli Sekarang
                        </button> --}}
                    @else
                    <button disabled
                            class="w-full border-2 border-orange-600 text-orange-600 px-6 py-3 rounded-lg font-semibold disabled:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-75 "
                        >
                            Menu Belum Bisa dipesan
                    </button>
                    @endif
                    @else
                        <button disabled
                            class="w-full border-2 border-orange-600 text-orange-600 px-6 py-3 rounded-lg font-semibold disabled:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-75 "
                        >
                            Login
                        </button>
                    @endauth
                </div>
                @endif
                @else
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex items-center space-x-2">                        
                            <p class="text-red-700 font-medium">Maaf, produk ini sedang tidak tersedia</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Recommended Products Section -->
        @if(count($this->recomendedMenu) > 0)
        <div class="mt-16">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Menu Rekomendasi</h2>
                <a wire:navigate href="/menu" class="text-orange-600 hover:text-orange-700 font-medium transition">
                    Lihat Semua Menu →
                </a>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($this->recomendedMenu as $recommended)
                    <div class="group bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-all duration-300 flex flex-col justify-between">
                        <div>
                            <a wire:navigate href="/menu/{{ $recommended->slug }}" class="block relative h-48 bg-gray-200 overflow-hidden">
                                @if($recommended->product_img)
                                    <img src="{{ Storage::disk('tsbc_disk')->url($recommended->product_img) }}" 
                                        alt="{{ $recommended->name }}"
                                        class="w-full h-full object-cover group-hover:scale-110 transition duration-300">
                                @else
                                    <div class="w-full h-full flex items-center justify-center bg-gray-100">
                                        <flux:icon.photo class="size-14 text-gray-400"/>
                                    </div>
                                @endif
                                
                                @if(!$recommended->is_available)
                                    <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center">
                                        <span class="text-white text-sm font-semibold">Tidak Tersedia</span>
                                    </div>
                                @endif
                            </a>
                            
                            <div class="p-4 pb-0">
                                <a wire:navigate href="/menu/{{ $recommended->slug }}" class="block">
                                    <h3 class="font-semibold text-gray-800 group-hover:text-orange-600 transition line-clamp-1">
                                        {{ $recommended->name }}
                                    </h3>
                                    <p class="text-sm text-gray-500 mt-1">
                                        {{ $recommended->tenant->store_name }}
                                    </p>
                                </a>
                                
                                <div class="flex items-center justify-between mt-2">
                                    <span class="font-bold text-gray-900">
                                        {{ $recommended->formatted_price }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 pt-2">
                            @if($recommended->is_available)
                                @if ($recommended->is_preorder)
                                    <div class="flex items-center justify-between gap-2 border-t border-gray-100 pt-3 mt-1">
                                        
                                        <div class="flex flex-col text-xs text-gray-500 min-w-0">
                                            <div class="flex items-center space-x-1 mb-1 text-gray-600 whitespace-nowrap">
                                                <flux:icon.clock class="size-4 shrink-0" />
                                                <span class="font-medium">Pre-order untuk:</span>
                                            </div>
                                            
                                            <div class="flex flex-wrap gap-1">
                                                @if($recommended->tenant->pick_slot->isNotEmpty())
                                                    @foreach($recommended->tenant->pick_slot->take(2) as $day)
                                                        <span class="bg-orange-100 text-orange-700 px-1.5 py-1 rounded text-[12px] mt-1 font-medium">
                                                            {{ $day->dayPickup }}
                                                        </span>
                                                    @endforeach

                                                    @if($recommended->tenant->pick_slot->count() >= 3)
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
                                        @endauth

                                    </div>
                                @else
                                    <div class="flex items-center space-x-1 text-xs text-gray-500 border-t border-gray-100 pt-3 mt-1">
                                        <flux:icon.hand-platter class="size-4 text-gray-400" />
                                        <span>Ready-To Serve</span>
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
        </div>
        @endif
    </div>
</div>