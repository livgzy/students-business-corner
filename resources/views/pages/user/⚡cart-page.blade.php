<?php

use Livewire\Component;
use App\Models\Tenant;
use App\Models\PickupSlot;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentBatch;
use App\Services\FonnteService;
use App\Services\XenditQrisService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

new class extends Component
{
    public $cart = [];
    public $totalPrice = 0;
    public $totalItems = 0;
    public $tenantTotals = [];
    public $pickupDays = [];
    public $pickupTimeRange = [];
    public $selectedDay = [];
    public $selectedTime = [];
    public $paymentMethod = [];
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

            if (!isset($this->paymentMethod[$tenantId])) {
                $this->paymentMethod[$tenantId] = session("payment_method.{$tenantId}", '');
            }

            if (!empty($this->selectedDay[$tenantId])) {
                $this->loadAvailableTimes(
                    $tenantId,
                    $this->selectedDay[$tenantId]
                );
            }
        }
    }

    public function loadPickupDays($tenantId)
    {
        $this->pickupDays[$tenantId] = PickupSlot::where('tenant_id', $tenantId)
            ->get()
            ->unique('dayPickup')
            ->values();
    }

    public function loadAvailableTimes($tenantId, $day)
    {
        $this->pickupTimeRange[$tenantId] = [
            'min' => null,
            'max' => null
        ];

        if (empty($day)) {
            return;
        }

        $slot = PickupSlot::where('tenant_id', $tenantId)
            ->where('dayPickup', $day)
            ->first();

        if (!$slot) {
            return;
        }

        $this->pickupTimeRange[$tenantId] = [
            'min' => \Carbon\Carbon::parse($slot->start_time)->format('H:i'),
            'max' => \Carbon\Carbon::parse($slot->end_time)->format('H:i'),
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
                    'name' => $item['tenant_name'] ?? 'Tenant',
                    'tenant_code' => $item['tenant_code'] ?? '',
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
        $this->loadCart();
        $this->dispatch('cartUpdated');
    }

    public function removeItem($productId)
    {
        $cart = session()->get('cart', []);
        unset($cart[$productId]);
        session()->put('cart', $cart);
        $this->loadCart();
        $this->dispatch('cartUpdated');

        $this->dispatch(
            'notify',
            message: 'Item berhasil dihapus dari keranjang!',
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

    public function updatePickupDay($tenantId, $day)
    {
        $this->selectedDay[$tenantId] = $day;
        $this->selectedTime[$tenantId] = '';

        $this->loadAvailableTimes($tenantId, $day);

        $cart = session()->get('cart', []);

        foreach ($cart as &$item) {
            if ((int) $item['tenant_id'] === (int) $tenantId) {
                $item['selectedDay'] = $day;
                $item['selectedTime'] = '';
            }
        }

        unset($item);

        session()->put('cart', $cart);
        $this->cart = $cart;
    }

    public function updatePickupTime($tenantId, $time)
    {
        $this->selectedTime[$tenantId] = $time;

        $cart = session()->get('cart', []);

        foreach ($cart as &$item) {
            if ((int) $item['tenant_id'] === (int) $tenantId) {
                $item['selectedTime'] = $time;
            }
        }

        unset($item);

        session()->put('cart', $cart);
        $this->cart = $cart;
    }

    public function updatePaymentMethod($tenantId, $paymentMethod)
    {
        if (!in_array($paymentMethod, ['Tunai', 'Non Tunai'], true)) {
            $paymentMethod = '';
        }

        $this->paymentMethod[$tenantId] = $paymentMethod;

        session()->put(
            "payment_method.{$tenantId}",
            $paymentMethod
        );
    }

    public function checkout()
    {
        if (empty($this->cart)) {
            return;
        }

        $this->cart = session()->get('cart', []);

        if (empty($this->cart)) {
            return;
        }

        $tenantIds = collect($this->cart)
            ->pluck('tenant_id')
            ->unique()
            ->values()
            ->all();

        foreach ($tenantIds as $tenantId) {
            $tenant = Tenant::find($tenantId);

            if (!$tenant) {
                $this->dispatch(
                    'notify',
                    message: 'Tenant tidak ditemukan.',
                    type: 'error'
                );
                return;
            }

            $items = collect($this->cart)
                ->where('tenant_id', $tenantId)
                ->values();

            if ($items->isEmpty()) {
                continue;
            }

            $preorderValues = $items
                ->map(fn($item) => (bool) ($item['is_preorder'] ?? false))
                ->unique()
                ->values();

            if ($preorderValues->count() > 1) {
                $this->dispatch(
                    'notify',
                    message: "Pesanan {$tenant->store_name} tidak dapat diproses karena terdapat menu Pre-order dan Order biasa dalam satu pesanan.",
                    type: 'error'
                );
                return;
            }

            $isPreorder = (bool) $preorderValues->first();

            if ($isPreorder) {
                $day = $this->selectedDay[$tenantId] ?? null;
                $time = $this->selectedTime[$tenantId] ?? null;

                if (empty($day)) {
                    $this->dispatch(
                        'notify',
                        message: "Silakan pilih hari pickup untuk {$tenant->store_name}.",
                        type: 'error'
                    );
                    return;
                }

                if (empty($time)) {
                    $this->dispatch(
                        'notify',
                        message: "Silakan pilih jam pickup untuk {$tenant->store_name}.",
                        type: 'error'
                    );
                    return;
                }

                $slot = PickupSlot::where('tenant_id', $tenantId)
                    ->where('dayPickup', $day)
                    ->first();

                if (!$slot) {
                    $this->dispatch(
                        'notify',
                        message: "Jadwal pickup untuk {$tenant->store_name} tidak ditemukan.",
                        type: 'error'
                    );
                    return;
                }

                $selectedTime = \Carbon\Carbon::parse($time);
                $startTime = \Carbon\Carbon::parse($slot->start_time);
                $endTime = \Carbon\Carbon::parse($slot->end_time);

                if ($selectedTime->lt($startTime) || $selectedTime->gt($endTime)) {
                    $this->dispatch(
                        'notify',
                        message: "Jam pickup untuk {$tenant->store_name} berada di luar jadwal yang tersedia.",
                        type: 'error'
                    );
                    return;
                }
            }

            $paymentMethod = $this->paymentMethod[$tenantId] ?? '';

            if (!in_array($paymentMethod, ['Tunai', 'Non Tunai'], true)) {
                $this->dispatch(
                    'notify',
                    message: "Silakan pilih metode pembayaran untuk {$tenant->store_name}.",
                    type: 'error'
                );
                return;
            }
        }

        $createdOrders = collect();

        try {
            DB::transaction(function () use ($tenantIds, &$createdOrders) {
                foreach ($tenantIds as $tenantId) {
                    $createdOrders->push($this->createOrderForTenant($tenantId));
                }
            });
        } catch (\Throwable $e) {
            report($e);


            dd([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->dispatch(
                'notify',
                message: 'Order Gagal, Coba Lagi.',
                type: 'error'
            );

            return;
        }

        // Tunai: notify tenant langsung seperti sebelumnya, tidak butuh QR apa pun
        $createdOrders->where('payment_method', 'Tunai')->each(
            fn (Order $order) => FonnteService::sendOrderNotification($order->load('items'))
        );

        // Non Tunai: SEMUA order (bisa lintas tenant) digabung jadi 1 payment_batch
        // -> 1 QRIS Xendit, customer cukup scan sekali untuk seluruh checkout ini
        $nonTunaiOrders = $createdOrders->where('payment_method', 'Non Tunai');

        if ($nonTunaiOrders->isNotEmpty()) {
            try {
                $this->createPaymentBatch($nonTunaiOrders);
            } catch (\Throwable $e) {
                report($e);

                dd([
                    'message' => $e->getMessage(),
                    'class' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
                // Order tetap tersimpan (Pending, Belum Dibayar) -- customer bisa
                // retry bikin QR baru dari halaman /checkout tanpa checkout ulang dari nol
                $this->dispatch(
                    'notify',
                    message: 'Order tersimpan, tapi QRIS gagal dibuat. Coba muat ulang halaman pembayaran.',
                    type: 'error'
                );
            }
        }

        $cart = session()->get('cart', []);

        foreach ($cart as $key => $item) {
            if (in_array($item['tenant_id'], $tenantIds)) {
                unset($cart[$key]);
            }
        }

        session()->put('cart', $cart);

        foreach ($tenantIds as $tenantId) {
            session()->forget("payment_method.{$tenantId}");
        }

        $this->cart = $cart;
        $this->loadCart();
        $this->dispatch('cartUpdated');

        if ($nonTunaiOrders->isNotEmpty()) {
            return redirect('/checkout');
        }

        $this->dispatch(
            'notify',
            message: 'Menu Tenant Berhasil Di Order.',
            type: 'success'
        );

        return redirect('/my-order');
    }

    private function createPaymentBatch($orders)
    {
        $batchNumber = 'BATCH-' . now()->format('ymd') . '-' . strtoupper(Str::random(6));
        $totalAmount = $orders->sum(fn (Order $order) => (float) $order->total_amount);

        $result = app(XenditQrisService::class)->createQrisPaymentRequest(
            referenceId: $batchNumber,
            amount: $totalAmount,
            description: "Pembayaran {$orders->count()} pesanan UCIC Student Business Corner"
        );

        $batch = PaymentBatch::create([
            'batch_number'              => $batchNumber,
            'user_id'                   => Auth::id(),
            'total_amount'              => $totalAmount,
            'status'                    => 'Pending',
            'xendit_payment_request_id' => $result['payment_request_id'],
            'xendit_reference_id'       => $batchNumber,
            'xendit_qr_string'          => $result['qr_string'],
            'xendit_status'             => $result['status'],
            'expired_at'                => now()->addMinutes(PaymentBatch::PAYMENT_WINDOW_MINUTES),
        ]);

        Order::whereIn('id', $orders->pluck('id'))->update([
            'payment_batch_id' => $batch->id,
        ]);
    }

    private function createOrderForTenant($tenantId)
    {
        $tenant = Tenant::findOrFail($tenantId);

        $items = collect($this->cart)
            ->where('tenant_id', $tenantId)
            ->values();

        if ($items->isEmpty()) {
            throw new \RuntimeException('Item order tidak ditemukan.');
        }

        $isPreorder = (bool) ($items->first()['is_preorder'] ?? false);

        $day = null;
        $time = null;
        $slot = null;

        if ($isPreorder) {
            $day = $this->selectedDay[$tenantId] ?? null;
            $time = $this->selectedTime[$tenantId] ?? null;

            $slot = PickupSlot::where('tenant_id', $tenantId)
                ->where('dayPickup', $day)
                ->firstOrFail();
        }

        $paymentMethod = $this->paymentMethod[$tenantId] ?? null;

        if (!in_array($paymentMethod, ['Tunai', 'Non Tunai'], true)) {
            throw new \RuntimeException('Metode pembayaran tidak valid.');
        }

        $order = Order::create([
            'order_number' => $this->generateOrderNumber($tenant),
            'data_tenant' => [
                'tenant_code' => $tenant->tenant_code,
                'store_name' => $tenant->store_name,
                'phone' => $tenant->phone,
            ],
            'reservation_id' => $tenant->reservation_id,
            'user_id' => Auth::id(),
            'order_type' => $isPreorder ? 'pre_order' : 'reguler',
            'status' => 'Pending',
            'total_amount' => $items->sum(
                fn($item) => $item['price'] * $item['quantity']
            ),
            'payment_status' => 'Belum Dibayar',
            'payment_method' => $paymentMethod,
            'pickup_time' => $time,
            'pickup_slot_id' => $slot?->id,
            'data_pickup_slot' => $isPreorder && $slot
                ? [
                    'dayPickup' => $day,
                    'start_time' => $slot->start_time,
                    'end_time' => $slot->end_time,
                ]
                : null,
        ]);

        foreach ($items as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['id'],
                'data_product' => [
                    'name' => $item['name'],
                    'price' => $item['price'],
                    'is_preorder' => (bool) ($item['is_preorder'] ?? false),
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
            $orderNumber = 'ORD-' .
                strtoupper($tenant->tenant_code) . '-' .
                now()->format('ymd') . '-' .
                strtoupper(Str::random(4));
        } while (Order::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    public function clearCart()
    {
        session()->forget('cart');
        session()->forget('payment_method');

        foreach (array_keys($this->paymentMethod) as $tenantId) {
            session()->forget("payment_method.{$tenantId}");
        }

        $this->cart = [];
        $this->selectedDay = [];
        $this->selectedTime = [];
        $this->paymentMethod = [];
        $this->notes = [];

        $this->loadCart();
        $this->dispatch('cartUpdated');

        $this->dispatch(
            'notify',
            message: 'Keranjang berhasil dikosongkan!',
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
            'pickupTimeRange' => $this->pickupTimeRange,
            'selectedDay' => $this->selectedDay,
            'selectedTime' => $this->selectedTime,
            'paymentMethod' => $this->paymentMethod,
            'notes' => $this->notes,
        ])->layout('layouts.app')->title('UCIC Student Business Corner | Keranjang Belanja');
    }
};
?>

<div>
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-30">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-800">Keranjang Belanja</h1>
                <p class="text-gray-500 mt-1">Review pesanan Anda sebelum checkout</p>
            </div>

            @if(count($cart) > 0)
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 space-y-4">
                        @php
                            $groupedCart = collect($cart)->groupBy('tenant_id');
                        @endphp

                        @foreach($groupedCart as $tenantId => $items)
                            @php
                                $tenant = \App\Models\Tenant::find($tenantId);
                                $tenantDays = $pickupDays[$tenantId] ?? collect();
                                $isPreorder = $items->every(fn($item) => (bool)($item['is_preorder'] ?? false));
                                $hasMixedOrderType = $items->map(fn($item) => (bool)($item['is_preorder'] ?? false))->unique()->count() > 1;
                            @endphp

                            <div wire:key="tenant-{{ $tenantId }}" class="bg-white rounded-xl shadow-sm">
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

                                @if($hasMixedOrderType)
                                    <div class="m-4 p-3 rounded-lg bg-red-50 border border-red-200 text-sm text-red-600">
                                        Pesanan ini memiliki menu Pre-order dan Order biasa. Pisahkan jenis pesanan sebelum melanjutkan.
                                    </div>
                                @endif

                                <div class="divide-y divide-gray-100">
                                    @foreach($items as $item)
                                        <div wire:key="cart-item-{{ $item['id'] }}" class="p-4">
                                            <div class="flex items-start gap-4">
                                                <div class="w-20 h-20 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                                                    @if(!empty($item['image']))
                                                        <img src="{{ Storage::disk('tsbc_disk')->url($item['image']) }}" alt="{{ $item['name'] }}" class="w-full h-full object-cover">
                                                    @else
                                                        <div class="w-full h-full flex items-center justify-center">
                                                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 002 2v12a2 2 0 002 2z"></path>
                                                            </svg>
                                                        </div>
                                                    @endif
                                                </div>

                                                <div class="flex-1">
                                                    <div class="flex items-start justify-between">
                                                        <div>
                                                            <h4 class="font-semibold text-gray-800">
                                                                {{ $item['name'] }} 
                                                                @if($item['is_preorder'])  
                                                                    <span class="text-orange-600">(Pre-Order)</span>
                                                                @endif
                                                            </h4>
                                                            <p class="text-sm text-gray-500">{{ $item['tenant_name'] }}</p>
                                                            <p class="text-orange-600 font-bold mt-1">Rp {{ number_format($item['price'], 0, ',', '.') }}</p>
                                                        </div>

                                                        <button wire:click="removeItem({{ $item['id'] }})" wire:confirm="Hapus item ini dari keranjang?" class="text-gray-400 hover:text-red-500 transition">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                            </svg>
                                                        </button>
                                                    </div>

                                                    <div class="flex items-center gap-3 mt-2">
                                                        <button wire:click="decrementQuantity({{ $item['id'] }})" class="w-8 h-8 bg-gray-100 rounded-lg hover:bg-gray-200 transition flex items-center justify-center">
                                                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                                            </svg>
                                                        </button>

                                                        <span class="w-8 text-center font-semibold">{{ $item['quantity'] }}</span>

                                                        <button wire:click="incrementQuantity({{ $item['id'] }})" class="w-8 h-8 bg-gray-100 rounded-lg hover:bg-gray-200 transition flex items-center justify-center">
                                                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                                            </svg>
                                                        </button>

                                                        <span class="text-sm text-gray-500 ml-2">
                                                            Subtotal: Rp {{ number_format($item['price'] * $item['quantity'], 0, ',', '.') }}
                                                        </span>
                                                    </div>

                                                    <div class="mt-2">
                                                        <input type="text" wire:model.debounce.500ms="notes.{{ $item['id'] }}" wire:change="updateNotes({{ $item['id'] }}, $event.target.value)" placeholder="Catatan khusus (opsional)" class="w-full text-sm border border-gray-200 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                @if(!$hasMixedOrderType && $isPreorder)
                                    <div class="bg-amber-50 border-t border-amber-200 p-5 space-y-3">
                                        <div class="flex items-center space-x-2 text-amber-900 font-bold border-b border-amber-200/60 pb-2">
                                            <svg class="size-5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <span>Jadwal Pengambilan Pre-order</span>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-xs font-semibold text-amber-800 uppercase tracking-wider mb-1.5">Hari Pengambilan</label>
                                                <select wire:change="updatePickupDay({{ $tenantId }}, $event.target.value)" class="w-full bg-white border border-amber-300 rounded-xl px-3 py-3 text-gray-700 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent shadow-sm">
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
                                @endif

                                @if(!$hasMixedOrderType)
                                    <div class="bg-blue-50 border-t border-blue-200 p-5 space-y-3">
                                        <div class="flex items-center space-x-2 text-blue-900 font-bold border-b border-blue-200/60 pb-2">
                                            <flux:icon.credit-card />
                                            <span>Metode Pembayaran</span>
                                        </div>

                                        <div class="grid grid-cols-2 gap-3">
                                            <label class="flex items-center gap-2 bg-white border border-blue-200 rounded-xl px-3 py-3 cursor-pointer hover:border-blue-400 transition {{ ($paymentMethod[$tenantId] ?? '') === 'Tunai' ? 'ring-2 ring-blue-500 border-transparent' : '' }}">
                                                <input
                                                    type="radio"
                                                    name="paymentMethod_{{ $tenantId }}"
                                                    value="Tunai"
                                                    wire:click="updatePaymentMethod({{ $tenantId }}, 'Tunai')"
                                                    @checked(($paymentMethod[$tenantId] ?? '') === 'Tunai')
                                                    class="text-blue-600 focus:ring-blue-500"
                                                >
                                                <span class="text-sm font-medium text-gray-700">Tunai</span>
                                            </label>

                                            <label class="flex items-center gap-2 bg-white border border-blue-200 rounded-xl px-3 py-3 cursor-pointer hover:border-blue-400 transition {{ ($paymentMethod[$tenantId] ?? '') === 'Non Tunai' ? 'ring-2 ring-blue-500 border-transparent' : '' }}">
                                                <input
                                                    type="radio"
                                                    name="paymentMethod_{{ $tenantId }}"
                                                    value="Non Tunai"
                                                    wire:click="updatePaymentMethod({{ $tenantId }}, 'Non Tunai')"
                                                    @checked(($paymentMethod[$tenantId] ?? '') === 'Non Tunai')
                                                    class="text-blue-600 focus:ring-blue-500"
                                                >
                                                <span class="text-sm font-medium text-gray-700">Non Tunai</span>
                                            </label>
                                        </div>

                                        @if(empty($paymentMethod[$tenantId] ?? null))
                                            <p class="text-xs text-red-500">* Pilih metode pembayaran</p>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-xl shadow-sm overflow-hidden sticky top-24">
                            <div class="px-6 py-4 bg-gradient-to-r from-orange-500 to-amber-500">
                                <h3 class="text-lg font-semibold text-white">Ringkasan Pesanan</h3>
                            </div>

                            <div class="p-6 space-y-4">
                                @if(count($tenantTotals) > 0)
                                    <div class="space-y-2 pb-4 border-b border-gray-100">
                                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Rincian per Tenant</p>

                                        @foreach($tenantTotals as $tId => $data)
                                            <div class="flex justify-between text-sm text-gray-600">
                                                <span>
                                                    {{ $data['name'] }} ({{ $data['tenant_code'] }})
                                                    <span class="text-gray-400">({{ $data['items'] }} item)</span>
                                                </span>

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

                                <button
                                    wire:click="checkout"
                                    wire:confirm="Apakah Anda yakin ingin melakukan pemesanan? Pastikan pesanan makanan Anda sudah benar."
                                    wire:loading.attr="disabled"
                                    wire:target="checkout"
                                    class="w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold py-3 rounded-xl transition flex items-center justify-center gap-2 disabled:opacity-50"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                    <span wire:loading.remove wire:target="checkout">Lanjutkan Pemesanan</span>
                                    <span wire:loading wire:target="checkout">Memproses...</span>
                                </button>

                                <button wire:click="clearCart" wire:confirm="Kosongkan semua item di keranjang?" class="w-full text-sm text-gray-500 hover:text-red-500 transition">
                                    Kosongkan Keranjang
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @else
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