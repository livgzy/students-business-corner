<?php

use Livewire\Component;
use App\Models\Tenant;
use App\Models\PickupSlot;
use App\Models\PaymentMethod;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\FonnteService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

new class extends Component
{
    public $cart = [];
    public $totalPrice = 0;
    public $totalItems = 0;
    public $tenantTotals = []; 

    // Payment
    public $paymentMethods = []; // [$tenantId => Collection<PaymentMethod>] daftar metode aktif tenant
    public $paymentType = []; // [$tenantId => 'tunai' | 'non_tunai']
    public $selectedPaymentMethod = []; // [$tenantId => payment_method_id]


    // Per-tenant pickup data (mirrors the day -> time flow on the product detail page)
    public $pickupDays = [];      // [$tenantId => Collection<PickupSlot>] unique days available
    public $availableTimes = [];  // [$tenantId => array<string>] available times for the selected day
    public $pickupTimeRange = []; // [$tenantId => ['min' => 'H:i', 'max' => 'H:i']]
    public $selectedDay = [];     // [$tenantId => string] e.g. "Senin"
    public $selectedTime = [];    // [$tenantId => string] e.g. "10:00"

    public $notes = [];

    public function mount()
    {
        $this->loadCart();
    }

    public function loadCart()
    {
        $this->cart = session()->get('cart', []);
        $this->calculateTotal();

        $groupedByTenant = collect($this->cart)->groupBy('tenant_id');
        $paymentSelection = session()->get('payment_selection', []);

        foreach ($groupedByTenant as $tenantId => $items) {
            foreach ($items as $item) {
                if (!isset($this->notes[$item['id']])) {
                    $this->notes[$item['id']] = $item['notes'] ?? '';
                }
            }

            $this->loadPickupDays($tenantId);

            $firstItem = $items->first();
            if (!isset($this->selectedDay[$tenantId])) {
                $this->selectedDay[$tenantId] = $firstItem['selectedDay'] ?? '';
            }
            if (!isset($this->selectedTime[$tenantId])) {
                $this->selectedTime[$tenantId] = $firstItem['selectedTime'] ?? '';
            }

            if (!empty($this->selectedDay[$tenantId])) {
                $this->loadAvailableTimes($tenantId, $this->selectedDay[$tenantId]);
            }

            // --- Payment ---
            $this->loadPaymentMethods($tenantId);

            if (!isset($this->paymentType[$tenantId])) {
                $this->paymentType[$tenantId] = $paymentSelection[$tenantId]['type'] ?? '';
            }
            if (!isset($this->selectedPaymentMethod[$tenantId])) {
                $this->selectedPaymentMethod[$tenantId] = $paymentSelection[$tenantId]['method_id'] ?? '';
            }
        }
    }

    public function loadPaymentMethods($tenantId)
    {
        $this->paymentMethods[$tenantId] = PaymentMethod::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();
    }

    public function updatePaymentType($tenantId, $type)
    {
        $this->paymentType[$tenantId] = $type;

        if ($type === 'tunai') {
            $this->selectedPaymentMethod[$tenantId] = '';
        }

        $this->savePaymentSelection($tenantId);
    }

    public function updatePaymentMethod($tenantId, $methodId)
    {
        $this->selectedPaymentMethod[$tenantId] = $methodId;
        $this->savePaymentSelection($tenantId);
    }

    private function savePaymentSelection($tenantId)
    {
        $paymentSelection = session()->get('payment_selection', []);

        $paymentSelection[$tenantId] = [
            'type'      => $this->paymentType[$tenantId] ?? '',
            'method_id' => $this->selectedPaymentMethod[$tenantId] ?? '',
        ];

        session()->put('payment_selection', $paymentSelection);
    }


    /**
     * Step 1 data source: distinct pickup days for a tenant.
     */
    public function loadPickupDays($tenantId)
    {
        $this->pickupDays[$tenantId] = PickupSlot::where('tenant_id', $tenantId)
            ->get()
            ->unique('dayPickup')
            ->values();
    }

    public function loadAvailableTimes($tenantId, $day)
    {
        $this->pickupTimeRange[$tenantId] = ['min' => null, 'max' => null];

        if (empty($day)) {
            return;
        }

        $slot = PickupSlot::where('tenant_id', $tenantId)
            ->where('dayPickup', $day)
            ->first();

        if (!$slot) {
            return;
        }

        $start = \Carbon\Carbon::parse($slot->start_time);
        $end = \Carbon\Carbon::parse($slot->end_time);

        $this->pickupTimeRange[$tenantId] = [
            'min' => $start->format('H:i'),
            'max' => $end->format('H:i'),
        ];
    }

    public function calculateTotal()
    {
        $this->totalPrice = 0;
        $this->totalItems = 0;
        $this->tenantTotals = [];

        foreach ($this->cart as $item) {
            $subtotal = $item['price'] * $item['quantity'];

            $this->totalPrice += $subtotal;
            $this->totalItems += $item['quantity'];

            $tenantId = $item['tenant_id'];

            if (!isset($this->tenantTotals[$tenantId])) {
                $this->tenantTotals[$tenantId] = [
                    'name'  => $item['tenant_name'] ?? 'Tenant',
                    'tenant_code'  => $item['tenant_code'],
                    'price' => 0,
                    'items' => 0,
                ];
            }

            $this->tenantTotals[$tenantId]['price'] += $subtotal;
            $this->tenantTotals[$tenantId]['items'] += $item['quantity'];
        }
    }

    public function incrementQuantity($productId)
    {
        $cart = session()->get('cart', []);

        if (isset($cart[$productId])) {
            $cart[$productId]['quantity']++;
        }

        session()->put('cart', $cart);
        $this->cart = $cart;
        $this->calculateTotal();
        $this->dispatch('cartUpdated');
    }

    public function decrementQuantity($productId)
    {
        $cart = session()->get('cart', []);

        if (isset($cart[$productId])) {
            $cart[$productId]['quantity']--;

            if ($cart[$productId]['quantity'] <= 0) {
                unset($cart[$productId]);
            }
        }

        session()->put('cart', $cart);
        $this->cart = $cart;
        $this->calculateTotal();
        $this->dispatch('cartUpdated');
    }

    public function removeItem($productId)
    {
        $cart = session()->get('cart', []);
        unset($cart[$productId]);
        session()->put('cart', $cart);
        $this->loadCart();
        $this->dispatch('cartUpdated');

        $this->dispatch('notify', 
            message: "Item berhasil dihapus dari keranjang!", 
            type: 'info'
        );
    }

    public function updateNotes($productId, $notes)
    {
        $cart = session()->get('cart', []);
        if (isset($cart[$productId])) {
            $cart[$productId]['notes'] = $notes;
            session()->put('cart', $cart);
        }

        if (isset($this->cart[$productId])) {
            $this->cart[$productId]['notes'] = $notes;
        }

        $this->notes[$productId] = $notes;
    }

    /**
     * Step 1: user picks a day for this tenant.
     * Resets the time (since available times depend on the day) and
     * persists the choice onto every cart item belonging to this tenant.
     */
    public function updatePickupDay($tenantId, $day)
    {
        $this->selectedDay[$tenantId] = $day;
        $this->selectedTime[$tenantId] = '';

        $this->loadAvailableTimes($tenantId, $day);

        $cart = session()->get('cart', []);
        foreach ($cart as &$item) {
            if ($item['tenant_id'] == $tenantId) {
                $item['selectedDay'] = $day;
                $item['selectedTime'] = '';
            }
        }
        session()->put('cart', $cart);
    }

    /**
     * Step 2: user picks a time for this tenant (for the already-chosen day).
     */
    public function updatePickupTime($tenantId, $time)
    {
        $this->selectedTime[$tenantId] = $time;

        $cart = session()->get('cart', []);
        foreach ($cart as &$item) {
            if ($item['tenant_id'] == $tenantId) {
                $item['selectedTime'] = $time;
            }
        }
        session()->put('cart', $cart);
    }

    public function updatedSelectedTime($value, $tenantId)
    {
        $this->updatePickupTime($tenantId, $value);
    }

    public function checkout()
    {
        $tenantIds = array_unique(array_column($this->cart, 'tenant_id'));
        $hasError = false;
 
        foreach ($tenantIds as $tenantId) {
            $day = $this->selectedDay[$tenantId] ?? null;
            $time = $this->selectedTime[$tenantId] ?? null;
            $tenant = Tenant::find($tenantId);
 
            if (empty($day) || empty($time)) {
                $hasError = true;
                $this->dispatch('notify', 
                    message: "Silakan pilih hari & jam pickup untuk {$tenant->store_name}", 
                    type: 'error'
                );
                break;
            }
 
            $paymentType = $this->paymentType[$tenantId] ?? null;
 
            if (empty($paymentType)) {
                $hasError = true;
                $this->dispatch('notify', 
                    message: "Silakan pilih metode pembayaran (Tunai/Non Tunai) untuk {$tenant->store_name}", 
                    type: 'error'
                );
                break;
            }
 
            if ($paymentType === 'non_tunai' && empty($this->selectedPaymentMethod[$tenantId] ?? null)) {
                $hasError = true;
                $this->dispatch('notify', 
                    message: "Silakan pilih metode pembayaran non tunai untuk {$tenant->store_name}", 
                    type: 'error'
                );
                break;
            }
        }
 
        if ($hasError || count($this->cart) === 0) {
            return;
        }
 
        
        $tunaiTenantIds = [];
        $nonTunaiTenantIds = [];
 
        foreach ($tenantIds as $tenantId) {
            if (($this->paymentType[$tenantId] ?? '') === 'tunai') {
                $tunaiTenantIds[] = $tenantId;
            } else {
                $nonTunaiTenantIds[] = $tenantId;
            }
        }
 
        $nonTunaiOrderIds = [];
 
        try {
            DB::transaction(function () use ($tunaiTenantIds, $nonTunaiTenantIds, &$nonTunaiOrderIds) {
                foreach ($tunaiTenantIds as $tenantId) {
                    $order = $this->createOrderForTenant($tenantId, 'Tunai');
                    FonnteService::sendOrderNotification($order->load('items'));
                }
 
                foreach ($nonTunaiTenantIds as $tenantId) {
                    $order = $this->createOrderForTenant($tenantId, 'Non Tunai');
                    $nonTunaiOrderIds[] = $order->id;
                }
            });
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('notify', 
                message: "Order Gagal, Coba Lagi", 
                type: 'error'
            );
            return;
            return;
        }
 
        // Hapus dari cart hanya item milik tenant yang berhasil diorder
        $cart = session()->get('cart', []);
        foreach ($cart as $key => $item) {
            if (in_array($item['tenant_id'], $tenantIds)) {
                unset($cart[$key]);
            }
        }
        session()->put('cart', $cart);
        session()->forget('payment_selection');
 
        $this->loadCart();
        $this->dispatch('cartUpdated');
 
        if (!empty($nonTunaiOrderIds)) {
            return redirect('/checkout');
        }

        $this->dispatch('notify', 
            message: "Menu Tenant Berhasil Di Order", 
            type: 'success'
        );
 
        return redirect('/my-order');
    }

    private function createOrderForTenant($tenantId, string $paymentMethodLabel)
    {
        $tenant = Tenant::find($tenantId);
        $items = collect($this->cart)->where('tenant_id', $tenantId);
 
        $day = $this->selectedDay[$tenantId];
        $time = $this->selectedTime[$tenantId];
 
        $slot = PickupSlot::where('tenant_id', $tenantId)
            ->where('dayPickup', $day)
            ->first();
 
        $paymentMethodId = null;
        $paymentMethodModel = null;
 
        if ($paymentMethodLabel === 'Non Tunai') {
            $paymentMethodId = $this->selectedPaymentMethod[$tenantId] ?: null;
            $paymentMethodModel = $paymentMethodId ? PaymentMethod::find($paymentMethodId) : null;
        }
 
        $order = Order::create([
            'order_number' => $this->generateOrderNumber($tenant),
            'data_tenant' => [
                'tenant_code' => $tenant->tenant_code,
                'store_name'  => $tenant->store_name,
                'phone'       => $tenant->phone,
            ],
            'user_id' => Auth::id(),
            'status' => 'Pending',
            'total_amount' => $items->sum(fn ($item) => $item['price'] * $item['quantity']),
            'payment_status' => 'Belum Dibayar',
            'payment_method' => $paymentMethodLabel,
            'payment_method_id' => $paymentMethodId,
            'data_payment_method' => $paymentMethodModel ? [
                'type' => $paymentMethodModel->type,
                'name_payment' => $paymentMethodModel->name_payment,
            ] : null,
            'pickup_time' => $time,
            'pickup_slot_id' => $slot->id ?? null,
            'data_pickup_slot' => [
                'dayPickup'  => $day,
                'start_time' => $slot->start_time ?? null,
                'end_time'   => $slot->end_time ?? null,
            ],
        ]);
 
        foreach ($items as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['id'],
                'data_product' => [
                    'name' => $item['name'],
                    'price' => $item['price'],
                    'is_preorder' => true,
                ],
                'quantity' => $item['quantity'],
                'notes' => $item['notes'] ?? null,
            ]);
        }
 
        return $order;
    }
 
    private function generateOrderNumber(Tenant $tenant): string
    {
        do {
            $orderNumber = 'ORD-' . strtoupper($tenant->tenant_code) . '-' . now()->format('ymd') . '-' . strtoupper(Str::random(4));
        } while (Order::where('order_number', $orderNumber)->exists());
 
        return $orderNumber;
    }

    public function clearCart()
    {
        session()->forget('cart');
        session()->forget('payment_selection');
        $this->loadCart();
        $this->dispatch('cartUpdated');
        $this->dispatch('notify', 
            message: "Keranjang berhasil dikosongkan!", 
            type: 'info'
        );
        
    }

    public function render()
    {
        return $this->view([
            'cart' => $this->cart,
            'totalPrice' => $this->totalPrice,
            'totalItems' => $this->totalItems,
            'tenantTotals' => $this->tenantTotals,
            'pickupDays' => $this->pickupDays,
            // 'availableTimes' => $this->availableTimes,
            'pickupTimeRange' => $this->pickupTimeRange,
            'selectedDay' => $this->selectedDay,
            'selectedTime' => $this->selectedTime,
            'notes' => $this->notes,
            'paymentMethods' => $this->paymentMethods,
            'paymentType' => $this->paymentType,
            'selectedPaymentMethod' => $this->selectedPaymentMethod,
        ])->layout('layouts.app')->title('UCIC Student Business Corner | Keranjang Belanja');
    }
};
?>

<div>
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-30">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-800">Keranjang Belanja</h1>
                <p class="text-gray-500 mt-1">Review pesanan Anda sebelum checkout</p>
            </div>

            @if(count($cart) > 0)
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Cart Items -->
                    <div class="lg:col-span-2 space-y-4">
                        @php
                            $groupedCart = collect($cart)->groupBy('tenant_id');
                        @endphp

                        @foreach($groupedCart as $tenantId => $items)
                            @php
                                $tenant = \App\Models\Tenant::find($tenantId);
                                $tenantDays = $pickupDays[$tenantId] ?? collect();
                            @endphp

                            <div wire:key="tenant-{{ $tenantId }}"class="bg-white rounded-xl shadow-sm">
                                <!-- Tenant Header -->
                                <div class="bg-gradient-to-r from-orange-500 to-amber-500 px-4 py-3">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h3 class="font-semibold text-white">{{ $tenant->store_name ?? 'Tenant' }}</h3>
                                            <p class="text-orange-100 text-sm">Tenant {{ $tenant->tenant_code ?? '' }}</p>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-white text-sm bg-white/20 px-2 py-1 rounded-full">
                                                {{ $items->count() }} menu
                                            </span>
                                            <p class="text-white font-bold text-sm mt-1">
                                                Rp {{ number_format($tenantTotals[$tenantId]['price'] ?? 0, 0, ',', '.') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Items -->
                                <div class="divide-y divide-gray-100">
                                    @foreach($items as $item)
                                        <div wire:key="cart-item-{{ $item['id'] }}" class="p-4">
                                            <div class="flex items-start gap-4">
                                                <!-- Product Image -->
                                                <div class="w-20 h-20 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                                                    @if($item['image'])
                                                        <img src="{{ Storage::disk('tsbc_disk')->url($item['image']) }}"
                                                             alt="{{ $item['name'] }}"
                                                             class="w-full h-full object-cover">
                                                    @else
                                                        <div class="w-full h-full flex items-center justify-center">
                                                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                            </svg>
                                                        </div>
                                                    @endif
                                                </div>

                                                <!-- Product Info -->
                                                <div class="flex-1">
                                                    <div class="flex items-start justify-between">
                                                        <div>
                                                            <h4 class="font-semibold text-gray-800">{{ $item['name'] }}</h4>
                                                            <p class="text-sm text-gray-500">{{ $item['tenant_name'] }}</p>
                                                            <p class="text-orange-600 font-bold mt-1">Rp {{ number_format($item['price'], 0, ',', '.') }}</p>
                                                        </div>
                                                        <button wire:click="removeItem({{ $item['id'] }})"
                                                                wire:confirm="Hapus item ini dari keranjang?"
                                                                class="text-gray-400 hover:text-red-500 transition">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                            </svg>
                                                        </button>
                                                    </div>

                                                    <!-- Quantity -->
                                                    <div class="flex items-center gap-3 mt-2">
                                                        <button wire:click="decrementQuantity({{ $item['id'] }})"
                                                                
                                                                wire:target="decrementQuantity({{ $item['id'] }})"
                                                                class="w-8 h-8 bg-gray-100 rounded-lg hover:bg-gray-200 disabled:opacity-40 disabled:cursor-not-allowed transition flex items-center justify-center">
                                                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                                            </svg>
                                                        </button>
                                                        <span class="w-8 text-center font-semibold">{{ $item['quantity'] }}</span>
                                                        <button wire:click="incrementQuantity({{ $item['id'] }})"
                                                                
                                                                wire:target="incrementQuantity({{ $item['id'] }})"
                                                                class="w-8 h-8 bg-gray-100 rounded-lg hover:bg-gray-200 disabled:opacity-40 disabled:cursor-not-allowed transition flex items-center justify-center">
                                                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                                            </svg>
                                                        </button>
                                                        <span class="text-sm text-gray-500 ml-2">
                                                            Subtotal: Rp {{ number_format($item['price'] * $item['quantity'], 0, ',', '.') }}
                                                        </span>
                                                    </div>

                                                    <!-- Notes -->
                                                    <div class="mt-2">
                                                        <input type="text"
                                                               wire:model.debounce.500ms="notes.{{ $item['id'] }}"
                                                               wire:change="updateNotes({{ $item['id'] }}, $event.target.value)"
                                                               placeholder="Catatan khusus (opsional)"
                                                               class="w-full text-sm border border-gray-200 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <!-- Pickup Day + Time Selection per Tenant (simple dropdowns) -->
                                <div class="bg-amber-50 border-t border-amber-200 p-5 space-y-3">
                                    <div class="flex items-center space-x-2 text-amber-900 font-bold border-b border-amber-200/60 pb-2">
                                        <svg class="size-5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <span>Jadwal Pengambilan Pre-order</span>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <!-- Hari -->
                                        <div>
                                            <label class="block text-xs font-semibold text-amber-800 uppercase tracking-wider mb-1.5">
                                                Hari Pengambilan
                                            </label>
                                            <select
                                                wire:change="updatePickupDay({{ $tenantId }}, $event.target.value)"
                                                class="w-full bg-white border border-amber-300 rounded-xl px-3 py-3 text-gray-700 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent shadow-sm"
                                            >
                                                <option value="">-- Pilih Hari --</option>
                                                @foreach($tenantDays as $slot)
                                                    <option value="{{ $slot->dayPickup }}" @selected(($selectedDay[$tenantId] ?? '') === $slot->dayPickup)>
                                                        {{ $slot->dayPickup }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @if($tenantDays->isEmpty())
                                                <p class="text-xs text-gray-400 italic mt-1">Belum ada jadwal hari dari tenant.</p>
                                            @elseif(empty($selectedDay[$tenantId] ?? null))
                                                <p class="text-xs text-red-500 mt-1">* Pilih hari pickup</p>
                                            @endif
                                        </div>

                                        <!-- Jam -->
                                        <div>
                                            <x-time-input
                                                model="selectedTime.{{ $tenantId }}"
                                                label="Jam Pengambilan"
                                                :min="$pickupTimeRange[$tenantId]['min'] ?? null"
                                                :max="$pickupTimeRange[$tenantId]['max'] ?? null"
                                            />
                                            @if(!empty($selectedDay[$tenantId] ?? null) && empty($selectedTime[$tenantId] ?? null))
                                                <p class="text-xs text-red-500 mt-1">* Pilih jam pickup</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <!-- Metode Pembayaran per Tenant -->
                                <div class="bg-blue-50 border-t border-blue-200 p-5 space-y-3">
                                    <div class="flex items-center space-x-2 text-blue-900 font-bold border-b border-blue-200/60 pb-2">
                                        <flux:icon.credit-card/>
                                        <span>Metode Pembayaran</span>
                                    </div>

                                    <!-- Pilihan Tunai / Non Tunai -->
                                    <div class="grid grid-cols-2 gap-3">
                                        <label class="flex items-center gap-2 bg-white border border-blue-200 rounded-xl px-3 py-3 cursor-pointer hover:border-blue-400 transition
                                                    {{ ($paymentType[$tenantId] ?? '') === 'tunai' ? 'ring-2 ring-blue-500 border-transparent' : '' }}">
                                            <input type="radio"
                                                name="paymentType_{{ $tenantId }}"
                                                value="tunai"
                                                wire:click="updatePaymentType({{ $tenantId }}, 'tunai')"
                                                @checked(($paymentType[$tenantId] ?? '') === 'tunai')
                                                class="text-blue-600 focus:ring-blue-500">
                                            <span class="text-sm font-medium text-gray-700">Tunai</span>
                                        </label>

                                        <label class="flex items-center gap-2 bg-white border border-blue-200 rounded-xl px-3 py-3 cursor-pointer hover:border-blue-400 transition
                                                    {{ ($paymentType[$tenantId] ?? '') === 'non_tunai' ? 'ring-2 ring-blue-500 border-transparent' : '' }}">
                                            <input type="radio"
                                                name="paymentType_{{ $tenantId }}"
                                                value="non_tunai"
                                                wire:click="updatePaymentType({{ $tenantId }}, 'non_tunai')"
                                                @checked(($paymentType[$tenantId] ?? '') === 'non_tunai')
                                                class="text-blue-600 focus:ring-blue-500">
                                            <span class="text-sm font-medium text-gray-700">Non Tunai</span>
                                        </label>
                                    </div>

                                    @if(empty($paymentType[$tenantId] ?? null))
                                        <p class="text-xs text-red-500">* Pilih metode pembayaran</p>
                                    @endif

                                    <!-- Daftar Metode Non Tunai (muncul jika Non Tunai dipilih) -->
                                    @if(($paymentType[$tenantId] ?? '') === 'non_tunai')
                                    @php
                                        $methods = $paymentMethods[$tenantId] ?? collect();
                                    @endphp

                                    <div class="pt-2 border-t border-blue-200/60">
                                        @if($methods->isNotEmpty())
                                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                                @foreach($methods as $method)
                                                    <label class="flex items-center gap-3 bg-white border border-blue-200 rounded-xl px-3 py-3 cursor-pointer hover:border-blue-400 transition
                                                                {{ (string)($selectedPaymentMethod[$tenantId] ?? '') === (string)$method->id ? 'ring-2 ring-blue-500 border-transparent' : '' }}">
                                                        <input type="radio"
                                                            name="paymentMethod_{{ $tenantId }}"
                                                            value="{{ $method->id }}"
                                                            wire:click="updatePaymentMethod({{ $tenantId }}, {{ $method->id }})"
                                                            @checked((string)($selectedPaymentMethod[$tenantId] ?? '') === (string)$method->id)
                                                            class="text-blue-600 focus:ring-blue-500 shrink-0">

                                                        <div class="min-w-0">
                                                            <p class="text-sm font-semibold text-gray-800 truncate">{{ $method->name_payment }}</p>
                                                            <p class="text-xs text-gray-500">
                                                                <span class="uppercase font-medium">{{ str_replace('_', ' ', $method->type) }}</span>
                                                            </p>
                                                        </div>
                                                    </label>
                                                @endforeach
                                            </div>
                                        @else
                                            <p class="text-xs text-gray-400 italic">
                                                Tenant ini belum memiliki metode pembayaran non tunai aktif.
                                            </p>
                                        @endif

                                        @if($methods->isNotEmpty() && empty($selectedPaymentMethod[$tenantId] ?? null))
                                            <p class="text-xs text-red-500 mt-2">* Pilih salah satu metode pembayaran</p>
                                        @endif
                                    </div>
                                @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Summary -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-xl shadow-sm overflow-hidden sticky top-24">
                            <div class="px-6 py-4 bg-gradient-to-r from-orange-500 to-amber-500">
                                <h3 class="text-lg font-semibold text-white">Ringkasan Pesanan</h3>
                            </div>

                            <div class="p-6 space-y-4">
                                @if(count($tenantTotals) > 0)
                                    <div class="space-y-2 pb-4 border-b border-gray-100">
                                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
                                            Rincian per Tenant
                                        </p>
                                        @foreach($tenantTotals as $tId => $data)
                                            <div class="flex justify-between text-sm text-gray-600">
                                                <span>{{ $data['name'] }} ({{ $data['tenant_code'] }}) <span class="text-gray-400"> ({{ $data['items'] }} item)</span></span>
                                                <span class="font-medium text-gray-700">
                                                    Rp {{ number_format($data['price'], 0, ',', '.') }}
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                                <div class="flex justify-between text-gray-600">
                                    <span>Total Item</span>
                                    <span class="font-semibold">{{ $totalItems }}</span>
                                </div>
                                <div class="flex justify-between text-gray-600">
                                    <span>Total Harga</span>
                                    <span class="font-semibold">Rp {{ number_format($totalPrice, 0, ',', '.') }}</span>
                                </div>

                                <div class="border-t pt-4">
                                    <div class="flex justify-between text-lg font-bold text-gray-800">
                                        <span>Total</span>
                                        <span class="text-orange-600">Rp {{ number_format($totalPrice, 0, ',', '.') }}</span>
                                    </div>
                                </div>

                                <!-- Checkout Button -->
                                <button wire:click="checkout"
                                        wire:confirm="Apakah Anda yakin ingin melakukan pemesanan? Pastikan pesanan makanan Anda sudah benar."
                                        class="w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold py-3 rounded-xl transition flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                    Lanjut ke Pembayaran
                                </button>

                                <button wire:click="clearCart"
                                        wire:confirm="Kosongkan semua item di keranjang?"
                                        class="w-full text-sm text-gray-500 hover:text-red-500 transition">
                                    Kosongkan Keranjang
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <!-- Empty Cart -->
                <div class="text-center py-16 bg-white rounded-xl shadow-sm">
                    <div class="w-32 h-32 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-6">
                        <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">Keranjang Kosong</h3>
                    <p class="text-gray-500 mb-6">Belum ada item di keranjang belanja Anda</p>
                    <a href="{{ route('home') }}" class="inline-block px-6 py-3 bg-orange-500 text-white rounded-xl hover:bg-orange-600 transition">
                        Mulai Belanja
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>