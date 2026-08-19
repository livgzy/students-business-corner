<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\WithoutUrlPagination;


new class extends Component
{
    use WithPagination, WithoutUrlPagination;

    public $statusFilter = 'all';
    public $search = '';
    public $perPage = 10;
    public $showDetailModal = false;
    public $selectedOrder = null;

    protected $queryString = [
        'statusFilter' => ['except' => 'all'],
        'search' => ['except' => ''],
    ];

    public function getOrdersProperty()
    {
        $query = Order::with(['user', 'items'])
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc');

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->search) {
            $query->where('order_number', 'like', '%' . $this->search . '%');
        }

        return $query->paginate($this->perPage);
    }

    public function viewDetail($orderId)
    {
        $this->selectedOrder = Order::with(['user', 'items'])
            ->where('user_id', Auth::id())
            ->findOrFail($orderId);
        $this->showDetailModal = true;
    }

    public function closeModal()
    {
        // selectedOrder is intentionally kept (not nulled) so the Alpine
        // fade/scale-out transition has data to render while it closes.
        $this->showDetailModal = false;
    }

    /**
     * Central place for order-status colors/labels so Tailwind's class
     * scanner always sees full, literal class strings (no interpolation).
     */
    protected function statusMeta($status)
    {
        return [
            'Pending' => [
                'badge'  => 'bg-yellow-50 text-yellow-700 ring-1 ring-yellow-200',
                'dot'    => 'bg-yellow-500',
                'border' => 'border-l-yellow-400',
            ],
            'Diproses' => [
                'badge'  => 'bg-blue-50 text-blue-700 ring-1 ring-blue-200',
                'dot'    => 'bg-blue-500',
                'border' => 'border-l-blue-400',
            ],
            'Selesai' => [
                'badge'  => 'bg-gray-100 text-gray-700 ring-1 ring-gray-200',
                'dot'    => 'bg-gray-500',
                'border' => 'border-l-gray-300',
            ],
            'Dibatalkan' => [
                'badge'  => 'bg-red-50 text-red-700 ring-1 ring-red-200',
                'dot'    => 'bg-red-500',
                'border' => 'border-l-red-400',
            ],
        ][$status] ?? [
            'badge'  => 'bg-gray-100 text-gray-700 ring-1 ring-gray-200',
            'dot'    => 'bg-gray-400',
            'border' => 'border-l-gray-300',
        ];
    }

    public function getStatusBadgeClass($status)
    {
        return $this->statusMeta($status)['badge'];
    }

    public function getStatusDotClass($status)
    {
        return $this->statusMeta($status)['dot'];
    }

    public function getStatusBorderClass($status)
    {
        return $this->statusMeta($status)['border'];
    }

    /**
     * Payment status meta. Cash ("Tunai") orders that are still
     * "Belum Dibayar" get relabeled to "Bayar di Tempat (Tunai)" since
     * that status is expected/normal for cash, not a warning.
     */
    protected function paymentStatusMeta($order)
    {
        if ($order->payment_method === 'Tunai' && $order->payment_status === 'Belum Dibayar') {
            return [
                'label' => 'Bayar di Tempat (Tunai)',
                'badge' => 'bg-blue-50 text-blue-700 ring-1 ring-blue-200',
                'dot'   => 'bg-blue-500',
            ];
        }

        return [
            'Belum Dibayar' => [
                'label' => 'Belum Dibayar',
                'badge' => 'bg-red-50 text-red-700 ring-1 ring-red-200',
                'dot'   => 'bg-red-500',
            ],
            'Menunggu Konfirmasi' => [
                'label' => 'Menunggu Konfirmasi',
                'badge' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
                'dot'   => 'bg-amber-500',
            ],
            'Sudah Dibayar' => [
                'label' => 'Sudah Dibayar',
                'badge' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
                'dot'   => 'bg-emerald-500',
            ],
        ][$order->payment_status] ?? [
            'label' => $order->payment_status,
            'badge' => 'bg-gray-100 text-gray-700 ring-1 ring-gray-200',
            'dot'   => 'bg-gray-400',
        ];
    }

    public function getPaymentStatusLabel($order)
    {
        return $this->paymentStatusMeta($order)['label'];
    }

    public function getPaymentStatusBadgeClass($order)
    {
        return $this->paymentStatusMeta($order)['badge'];
    }

    public function getPaymentStatusDotClass($order)
    {
        return $this->paymentStatusMeta($order)['dot'];
    }

    public function getPaymentMethodLabel($order)
    {
        if ($order->payment_method === 'Tunai') {
            return 'Tunai (Bayar di Tempat)';
        }

        return $order->data_payment_method['name_payment'] ?? $order->payment_method;
    }

    public function getPaymentMethodSubLabel($order)
    {
        if ($order->payment_method === 'Tunai') {
            return 'Dibayar saat pengambilan pesanan';
        }

        $type = $order->data_payment_method['type'] ?? null;

        return [
            'bank_transfer' => 'Bank Transfer',
            'qris'          => 'QRIS',
            'e_wallet'      => 'E-Wallet',
        ][$type] ?? $type;
    }

    public function render()
    {
        return $this->view([
            'orders' => $this->orders,
        ])->layout('layouts.app')->title('UCIC Student Bussiness Corner | My Orders');
    }
};
?>

<div>
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-8">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl bg-orange-100 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 7h6m-6 4h6"></path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">Riwayat Pesanan</h1>
                        <p class="text-gray-500 text-sm mt-0.5">Lihat dan pantau semua pesanan Anda</p>
                    </div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="bg-white rounded-2xl shadow-sm ring-1 ring-gray-100 p-4 sm:p-5 mb-6">
                <div class="flex flex-col md:flex-row gap-4">
                    <!-- Search -->
                    <div class="flex-1">
                        <label class="block text-xs font-medium text-gray-500 mb-1.5">Cari Pesanan</label>
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            <input type="text"
                                   wire:model.live.debounce.300ms="search"
                                   placeholder="Nomor pesanan..."
                                   class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent transition">
                        </div>
                    </div>

                    <!-- Status Filter -->
                    <div class="w-full md:w-52">
                        <label class="block text-xs font-medium text-gray-500 mb-1.5">Status</label>
                        <select wire:model.live="statusFilter" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500 transition">
                            <option value="all">Semua Status</option>
                            <option value="Pending">Pending</option>
                            <option value="Diproses">Diproses</option>
                            <option value="Selesai">Selesai</option>
                            <option value="Dibatalkan">Dibatalkan</option>
                        </select>
                    </div>

                    <!-- Per Page -->
                    <div class="w-full md:w-36">
                        <label class="block text-xs font-medium text-gray-500 mb-1.5">Tampilkan</label>
                        <select wire:model.live="perPage" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500 transition">
                            <option value="5">5 data</option>
                            <option value="10">10 data</option>
                            <option value="25">25 data</option>
                            <option value="50">50 data</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Orders List -->
            @if($orders->count() > 0)
                <p class="text-sm text-gray-500 mb-3">Menampilkan {{ $orders->count() }} pesanan</p>

                <div class="space-y-4">
                    @foreach($orders as $order)
                        @php
                            $tenant = $order->data_tenant ?? [];
                            $pickupSlot = $order->data_pickup_slot ?? [];
                        @endphp
                        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-gray-100 border-l-4 {{ $this->getStatusBorderClass($order->status) }} overflow-hidden hover:shadow-md transition">
                            <!-- Order Header -->
                            <div class="px-5 sm:px-6 py-4 border-b border-orange-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-3 bg-orange-50/60">
                                <div class="flex items-center gap-3 flex-wrap">
                                    <span class="font-semibold text-gray-800 tracking-tight">#{{ $order->order_number }}</span>
                                    <span class="text-sm text-gray-400">{{ $order->created_at->format('d/m/Y H:i') }}</span>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full {{ $this->getStatusBadgeClass($order->status) }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $this->getStatusDotClass($order->status) }}"></span>
                                        {{ $order->status }}
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full {{ $this->getPaymentStatusBadgeClass($order) }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $this->getPaymentStatusDotClass($order) }}"></span>
                                        {{ $this->getPaymentStatusLabel($order) }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-3 self-stretch md:self-auto justify-between md:justify-end">
                                    <span class="font-bold text-orange-600 text-lg">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                                    <button wire:click="viewDetail({{ $order->id }})"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-orange-600 hover:text-white hover:bg-orange-600 rounded-lg transition">
                                        Detail
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Order Body -->
                            <div class="px-5 sm:px-6 py-4">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-5 text-sm">
                                    <!-- Tenant Info -->
                                    <div class="flex gap-2.5">
                                        <svg class="w-4 h-4 text-gray-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4"></path>
                                        </svg>
                                        <div>
                                            <p class="text-xs text-gray-400">Tenant</p>
                                            <p class="font-medium text-gray-800">{{ $tenant['store_name'] ?? '-' }}</p>
                                            <p class="text-xs text-gray-400">Tenant {{ $tenant['tenant_code'] ?? '-' }} &middot; {{ $tenant['phone'] ?? '-' }}</p>
                                        </div>
                                    </div>

                                    <!-- Pickup Info -->
                                    <div class="flex gap-2.5">
                                        <svg class="w-4 h-4 text-gray-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <div>
                                            <p class="text-xs text-gray-400">Waktu Pickup</p>
                                            <p class="font-medium text-gray-800">{{ $pickupSlot['dayPickup'] ?? '-' }}, {{ $order->pickup_time }}</p>
                                            <p class="text-xs text-gray-400">Slot {{ $pickupSlot['start_time'] ?? '-' }} - {{ $pickupSlot['end_time'] ?? '-' }}</p>
                                        </div>
                                    </div>

                                    <!-- Payment Method -->
                                    <div class="flex gap-2.5">
                                        <svg class="w-4 h-4 text-gray-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-9 4h16a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                        </svg>
                                        <div>
                                            <p class="text-xs text-gray-400">Metode Pembayaran</p>
                                            <p class="font-medium text-gray-800">{{ $this->getPaymentMethodLabel($order) }}</p>
                                            @if($this->getPaymentMethodSubLabel($order))
                                                <p class="text-xs text-gray-400">{{ $this->getPaymentMethodSubLabel($order) }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Order Items Summary -->
                                <div class="mt-4 pt-4 border-t border-gray-100">
                                    <p class="text-xs text-gray-400 mb-2">Item Pesanan</p>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($order->items as $item)
                                            @php
                                                $product = $item->data_product ?? [];
                                            @endphp
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs bg-gray-100 text-gray-700 rounded-full">
                                                {{ $product['name'] ?? 'Produk' }}
                                                <span class="text-gray-400">&times;{{ $item->quantity }}</span>
                                                @if($product['is_preorder'] ?? false)
                                                    <span class="px-1.5 py-0.5 text-[10px] font-medium bg-amber-100 text-amber-700 rounded-full">Pre-order</span>
                                                @endif
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $orders->links(data: ['scrollTo' => false]) }}
                </div>
            @else
                <!-- Empty State -->
                <div class="bg-white rounded-2xl shadow-sm ring-1 ring-gray-100 p-12 text-center">
                    <div class="w-24 h-24 mx-auto bg-orange-50 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-12 h-12 text-orange-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">Belum Ada Pesanan</h3>
                    <p class="text-gray-500 mb-5">Anda belum memiliki riwayat pesanan</p>
                    <a href="{{ route('home') }}" class="px-5 py-2.5 bg-orange-500 text-white rounded-xl hover:bg-orange-600 transition inline-flex items-center gap-2 font-medium">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        Mulai Belanja
                    </a>
                </div>
            @endif
        </div>
    </div>

    <!-- Detail Modal -->
    @if($selectedOrder)
        @php
            $tenant = $selectedOrder->data_tenant ?? [];
            $pickupSlot = $selectedOrder->data_pickup_slot ?? [];
            $steps = ['Pending', 'Diproses', 'Selesai'];
            $currentStepIndex = array_search($selectedOrder->status, $steps);
        @endphp
        <div
            x-data="{ open: @entangle('showDetailModal') }"
            x-show="open"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 overflow-y-auto"
            style="display: none;"
        >
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" wire:click="closeModal"></div>

                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="relative bg-white rounded-2xl shadow-2xl max-w-3xl w-full mx-auto max-h-[90vh] overflow-y-auto"
                >
                    <!-- Header -->
                    <div class="sticky top-0 z-10 bg-gradient-to-r from-orange-500 to-amber-500 px-6 py-4 flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-white">Detail Pesanan</h3>
                                <p class="text-xs text-orange-50">#{{ $selectedOrder->order_number }}</p>
                            </div>
                        </div>
                        <button wire:click="closeModal" class="text-white/80 hover:text-white transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Content -->
                    <div class="p-6 space-y-6">

                        @if($selectedOrder->status === 'Dibatalkan')
                            <div class="flex items-center gap-3 bg-red-50 border border-red-100 text-red-700 rounded-xl px-4 py-3">
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <p class="text-sm font-medium">Pesanan ini telah dibatalkan</p>
                            </div>
                        @else
                            <!-- Status Stepper -->
                            <div class="flex items-center">
                                @foreach($steps as $i => $step)
                                    <div class="flex items-center {{ $i < count($steps) - 1 ? 'flex-1' : '' }}">
                                        <div class="flex flex-col items-center">
                                            <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-semibold
                                                {{ $i <= $currentStepIndex ? 'bg-orange-500 text-white' : 'bg-gray-100 text-gray-400' }}">
                                                @if($i < $currentStepIndex)
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                @else
                                                    {{ $i + 1 }}
                                                @endif
                                            </div>
                                            <span class="mt-1.5 text-[11px] text-center {{ $i <= $currentStepIndex ? 'text-gray-700 font-medium' : 'text-gray-400' }} w-16">{{ $step }}</span>
                                        </div>
                                        @if($i < count($steps) - 1)
                                            <div class="flex-1 h-0.5 mx-1 {{ $i < $currentStepIndex ? 'bg-orange-500' : 'bg-gray-100' }}"></div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <!-- Status Badges -->
                        <div class="flex flex-wrap gap-2">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium {{ $this->getStatusBadgeClass($selectedOrder->status) }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $this->getStatusDotClass($selectedOrder->status) }}"></span>
                                {{ $selectedOrder->status }}
                            </span>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium {{ $this->getPaymentStatusBadgeClass($selectedOrder) }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $this->getPaymentStatusDotClass($selectedOrder) }}"></span>
                                {{ $this->getPaymentStatusLabel($selectedOrder) }}
                            </span>
                        </div>

                        <!-- Tenant & Pickup Info -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="bg-gray-50 rounded-xl p-4">
                                <div class="flex items-center gap-2 mb-3">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4"></path>
                                    </svg>
                                    <h4 class="font-semibold text-gray-800 text-sm">Informasi Tenant</h4>
                                </div>
                                <div class="space-y-2 text-sm">
                                    <div>
                                        <span class="text-gray-400 text-xs">Nama Toko</span>
                                        <p class="font-medium text-gray-800">{{ $tenant['store_name'] ?? '-' }}</p>
                                    </div>
                                    <div>
                                        <span class="text-gray-400 text-xs">Kode Tenant</span>
                                        <p class="font-medium text-gray-800">{{ $tenant['tenant_code'] ?? '-' }}</p>
                                    </div>
                                    <div>
                                        <span class="text-gray-400 text-xs">No. Telepon</span>
                                        <p class="font-medium text-gray-800">{{ $tenant['phone'] ?? '-' }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-gray-50 rounded-xl p-4">
                                <div class="flex items-center gap-2 mb-3">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <h4 class="font-semibold text-gray-800 text-sm">Informasi Pickup</h4>
                                </div>
                                <div class="space-y-2 text-sm">
                                    <div>
                                        <span class="text-gray-400 text-xs">Hari</span>
                                        <p class="font-medium text-gray-800">{{ $pickupSlot['dayPickup'] ?? '-' }}</p>
                                    </div>
                                    <div>
                                        <span class="text-gray-400 text-xs">Slot Jam</span>
                                        <p class="font-medium text-gray-800">{{ $pickupSlot['start_time'] ?? '-' }} - {{ $pickupSlot['end_time'] ?? '-' }}</p>
                                    </div>
                                    <div>
                                        <span class="text-gray-400 text-xs">Waktu Pickup</span>
                                        <p class="font-medium text-gray-800">{{ $selectedOrder->pickup_time }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Info -->
                        <div class="bg-gray-50 rounded-xl p-4">
                            <div class="flex items-center gap-2 mb-3">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-9 4h16a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                <h4 class="font-semibold text-gray-800 text-sm">Informasi Pembayaran</h4>
                            </div>
                            <div class="text-sm">
                                <p class="font-medium text-gray-800">{{ $this->getPaymentMethodLabel($selectedOrder) }}</p>
                                @if($this->getPaymentMethodSubLabel($selectedOrder))
                                    <p class="text-xs text-gray-400 mt-0.5">{{ $this->getPaymentMethodSubLabel($selectedOrder) }}</p>
                                @endif
                            </div>
                        </div>

                        <!-- Order Items -->
                        <div>
                            <h4 class="font-semibold text-gray-800 mb-3 text-sm">Daftar Menu</h4>
                            <div class="space-y-2">
                                @foreach($selectedOrder->items as $item)
                                    @php
                                        $product = $item->data_product ?? [];
                                        $unitPrice = $product['price'] ?? 0;
                                        $subtotal = $unitPrice * $item->quantity;
                                    @endphp
                                    <div class="flex justify-between items-start gap-3 border-b border-gray-100 pb-3">
                                        <div class="flex items-start gap-3">
                                            <span class="mt-0.5 w-6 h-6 shrink-0 rounded-full bg-orange-50 text-orange-600 text-xs font-semibold flex items-center justify-center">
                                                {{ $item->quantity }}
                                            </span>
                                            <div>
                                                <p class="font-medium text-gray-800">{{ $product['name'] ?? 'Produk' }}</p>
                                                <div class="flex flex-col gap-1 mt-0.5 items-start">
                                                    @if($product['is_preorder'] ?? false)
                                                        <span class="px-1.5 py-0.5 text-[10px] font-medium bg-amber-100 text-amber-700 rounded-full">Pre-order</span>
                                                    @endif
                                                    @if($item->notes)
                                                        <span class="text-xs text-gray-400">Catatan : {{ $item->notes }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <p class="text-xs text-gray-400">Rp {{ number_format($unitPrice, 0, ',', '.') }} / item</p>
                                            <p class="font-semibold text-gray-800">Rp {{ number_format($subtotal, 0, ',', '.') }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Total -->
                        <div class="pt-2 flex justify-between items-center bg-orange-50 rounded-xl px-4 py-3.5">
                            <span class="text-base font-semibold text-gray-800">Total Pembayaran</span>
                            <span class="text-2xl font-bold text-orange-600">Rp {{ number_format($selectedOrder->total_amount, 0, ',', '.') }}</span>
                        </div>                        
                    </div>

                    <!-- Footer -->
                    <div class="sticky bottom-0 bg-gray-50 border-t border-gray-100 px-6 py-4 flex justify-end">
                        <button wire:click="closeModal"
                                class="px-4 py-2 bg-gray-200 text-orange-700 rounded-xl hover:bg-gray-300 transition font-medium">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>