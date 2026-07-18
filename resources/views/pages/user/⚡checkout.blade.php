<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Order;
use App\Services\FonnteService;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    use WithFileUploads;

    public $orders = [];
    public $proofFiles = []; // [$orderId => TemporaryUploadedFile]

    public int $paymentWindowMinutes = 30;

    public function mount()
    {
        $this->cancelExpiredOrders();
        $this->loadOrders();
    }

    public function refreshOrders()
    {
        $this->cancelExpiredOrders();
        $this->loadOrders();
    }

    public function loadOrders()
    {
        $this->orders = Order::with(['items', 'paymentMethod'])
            ->where('user_id', Auth::id())
            ->where('payment_method', 'Non Tunai')
            ->where('status', 'Pending')
            ->whereNull('payment_proof_img')
            ->where('created_at', '>=', now()->subMinutes($this->paymentWindowMinutes))
            ->orderBy('created_at')
            ->get();
    }

    private function cancelExpiredOrders()
    {
        Order::where('user_id', Auth::id())
            ->where('status', 'Pending')
            ->where('payment_method', 'Non Tunai')
            ->whereNull('payment_proof_img')
            ->where('created_at', '<=', now()->subMinutes($this->paymentWindowMinutes))
            ->update(['status' => 'Dibatalkan']);
    }

    public function cancelOrder($orderId)
    {
        $order = Order::where('id', $orderId)
            ->where('user_id', Auth::id())
            ->where('status', 'Pending')
            ->whereNull('payment_proof_img')
            ->first();

        if (!$order || !$order->created_at->addMinutes($this->paymentWindowMinutes)->isPast()) {
            return;
        }

        $order->update(['status' => 'Dibatalkan']);


        $this->orders = $this->orders->map(function ($o) use ($order) {
            if ($o->id === $order->id) {
                $o->status = 'Dibatalkan';
            }
            return $o;
        });
    }

    public function uploadProof($orderId)
    {
        $order = Order::with('items')
            ->where('id', $orderId)
            ->where('user_id', Auth::id())
            ->first();

        if (!$order || $order->status !== 'Pending') {
            $this->dispatch('notify', message: 'Order tidak ditemukan atau sudah tidak berlaku', type: 'error');
            $this->loadOrders();
            return;
        }

        if ($order->created_at->addMinutes($this->paymentWindowMinutes)->isPast()) {
            $order->update(['status' => 'Dibatalkan']);
            $this->dispatch('notify',
                message: "Waktu pembayaran order {$order->order_number} telah habis, order otomatis dibatalkan",
                type: 'error'
            );
            $this->loadOrders();
            return;
        }

        $this->validate([
            "proofFiles.$orderId" => 'required|image|max:2048',
        ], [], [
            "proofFiles.$orderId" => 'bukti pembayaran',
        ]);

        $path = $this->proofFiles[$orderId]->store('payment-proofs', 'tsbc_disk');

        $order->update([
            'payment_proof_img' => $path,
            'payment_status' => 'Menunggu Konfirmasi',
        ]);

        FonnteService::sendOrderNotification($order->fresh('items'));

        unset($this->proofFiles[$orderId]);

        $this->dispatch('notify',
            message: "Bukti pembayaran order {$order->order_number} berhasil dikirim",
            type: 'success'
        );

        $this->loadOrders();
    }

    public function render()
    {
        return $this->view([
            'orders' => $this->orders,
        ])->layout('layouts.app')->title('UCIC Student Business Corner | Checkout Pembayaran');
    }
};
?>

<div wire:poll.15s="refreshOrders">
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 mb-30">

            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-800">Checkout Pembayaran</h1>
                <p class="text-gray-500 mt-1">Upload bukti pembayaran untuk tiap tenant dalam waktu {{ $paymentWindowMinutes }} menit, atau order otomatis dibatalkan.</p>
            </div>

            @if($orders->isEmpty())
                <div class="text-center py-16 bg-white rounded-xl shadow-sm">
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">Tidak ada pembayaran yang perlu diselesaikan</h3>
                    <p class="text-gray-500 mb-6">Order non tunai kamu sudah diproses, dibatalkan, atau sudah lewat batas waktu.</p>
                    <a href="/my-order" class="inline-block px-6 py-3 bg-orange-500 text-white rounded-xl hover:bg-orange-600 transition">
                        Lihat Order Saya
                    </a>
                </div>
            @else
                <div class="space-y-5">
                    @foreach($orders as $order)
                        @php
                            // "Masih butuh dibayar" = status masih Pending DAN belum ada bukti diupload.
                            // Ini yang nentuin apakah countdown & form upload ditampilin.
                            $needsPayment = $order->status === 'Pending' && empty($order->payment_proof_img);
                        @endphp

                        <div wire:key="checkout-order-{{ $order->id }}"
                             class="bg-white rounded-xl shadow-sm overflow-hidden"
                             x-data="{
                                orderId: {{ $order->id }},
                                needsPayment: {{ $needsPayment ? 'true' : 'false' }},
                                expiresAt: {{ $order->created_at->addMinutes($paymentWindowMinutes)->timestamp }} * 1000,
                                remaining: 0,
                                expired: false,
                                interval: null,
                                init() {
                                    if (!this.needsPayment) return;
                                    this.tick();
                                    this.interval = setInterval(() => this.tick(), 1000);
                                },
                                tick() {
                                    this.remaining = Math.max(0, Math.floor((this.expiresAt - Date.now()) / 1000));
                                    if (this.remaining <= 0 && !this.expired) {
                                        this.expired = true;
                                        clearInterval(this.interval);
                                        $wire.cancelOrder(this.orderId);
                                    }
                                },
                                get display() {
                                    const m = Math.floor(this.remaining / 60).toString().padStart(2, '0');
                                    const s = (this.remaining % 60).toString().padStart(2, '0');
                                    return m + ':' + s;
                                }
                             }"
                        >
                            <div class="bg-gradient-to-r from-orange-500 to-amber-500 px-4 py-3 flex items-center justify-between">
                                <div>
                                    <h3 class="font-semibold text-white">{{ $order->data_tenant['store_name'] ?? 'Tenant' }} ({{ $order->data_tenant['tenant_code'] }})</h3>
                                    <p class="text-orange-100 text-sm">{{ $order->order_number }}</p>
                                </div>

                                @if($needsPayment)
                                    <div class="text-right">
                                        <p class="text-white/80 text-xs uppercase tracking-wide">Sisa waktu</p>
                                        <p class="text-white font-bold text-lg tabular-nums" x-text="expired ? 'Habis' : display"></p>
                                    </div>
                                @else
                                    <span class="text-white text-sm bg-white/20 px-3 py-1 rounded-full">
                                        {{ $order->status === 'Dibatalkan' ? 'Dibatalkan' : 'Menunggu Konfirmasi' }}
                                    </span>
                                @endif
                            </div>

                            <div class="p-5 space-y-4">
                                <div class="divide-y divide-gray-100">
                                    @foreach($order->items as $item)
                                        <div class="flex justify-between py-2 text-sm">
                                            <span class="text-gray-700">{{ $item->data_product['name'] ?? '-' }} x{{ $item->quantity }}</span>
                                            <span class="text-gray-600 font-medium">
                                                Rp {{ number_format(($item->data_product['price'] ?? 0) * $item->quantity, 0, ',', '.') }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="flex justify-between font-bold text-gray-800 border-t pt-3">
                                    <span>Total</span>
                                    <span class="text-orange-600">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                                </div>

                                <div class="bg-blue-50 rounded-xl p-4 text-sm text-blue-900 space-y-2">
                                    <div class="flex items-center justify-between">
                                        <p class="font-semibold">{{ $order->paymentMethod->name_payment ?? $order->data_payment_method['name_payment'] ?? 'Non Tunai' }}</p>
                                        <span class="text-xs uppercase font-medium text-blue-600 bg-blue-100 px-2 py-0.5 rounded-full">
                                            {{ str_replace('_', ' ', $order->paymentMethod->type ?? $order->data_payment_method['type'] ?? '') }}
                                        </span>
                                    </div>
                                
                                    @if($order->paymentMethod)
                                        @if($order->paymentMethod->type === 'qris')
                                            @if($order->paymentMethod->qr_img)
                                            <div class="flex flex-col items-center justify-center pt-1 gap-2">
                                                <p class="text-blue-700 text-center">
                                                    a.n. <span class="font-semibold">{{ $order->paymentMethod->account_name }}</span>
                                                </p>
                                                <img src="{{ Storage::disk('tsbc_disk')->url($order->paymentMethod->qr_img) }}"
                                                     alt="QRIS {{ $order->paymentMethod->name_payment }}"
                                                     class="w-110 h-110 object-contain bg-white rounded-lg border border-blue-200 p-2">
                                            </div>
                                            @endif
                                        @else
                                            <div class="pt-1 space-y-1">
                                                <p class="text-blue-700">
                                                    No. {{ $order->paymentMethod->type === 'e_wallet' ? 'HP' : 'Rekening' }}:
                                                    <span class="font-semibold">{{ $order->paymentMethod->account_number ?? '-' }}</span>
                                                </p>
                                                <p class="text-blue-700">
                                                    a.n. <span class="font-semibold">{{ $order->paymentMethod->account_name }}</span>
                                                </p>
                                            </div>
                                        @endif
                                    @else
                                        <p class="text-blue-600 italic text-xs">
                                            Detail metode pembayaran sudah tidak tersedia (kemungkinan dihapus tenant). Hubungi tenant langsung untuk info pembayaran.
                                        </p>
                                    @endif
                                </div>

                                @if($order->status === 'Dibatalkan')
                                    <div class="bg-red-50 border border-red-200 rounded-xl p-3 text-sm text-red-600 text-center">
                                        Order dibatalkan karena bukti pembayaran tidak diinput dalam {{ $paymentWindowMinutes }} menit.
                                    </div>
                                @elseif(!$needsPayment)
                                    <div class="bg-green-50 border border-green-200 rounded-xl p-3 text-sm text-green-700 text-center">
                                        Bukti pembayaran terkirim, menunggu konfirmasi tenant.
                                    </div>
                                @else
                                    <template x-if="!expired">
                                        <div class="space-y-3">
                                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                                Upload Bukti Pembayaran
                                            </label>
                                            <input type="file"
                                                   accept="image/*"
                                                   wire:model="proofFiles.{{ $order->id }}"
                                                   class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                                            @error("proofFiles.{$order->id}")
                                                <p class="text-xs text-red-500">{{ $message }}</p>
                                            @enderror

                                            @if($proofFiles[$order->id] ?? false)
                                                <img src="{{ $proofFiles[$order->id]->temporaryUrl() }}" class="h-32 rounded-lg border border-gray-200 object-cover">
                                            @endif

                                            <button wire:click="uploadProof({{ $order->id }})"
                                                    wire:loading.attr="disabled"
                                                    wire:target="uploadProof({{ $order->id }}), proofFiles.{{ $order->id }}"
                                                    class="w-full bg-orange-500 hover:bg-orange-600 disabled:opacity-50 text-white font-semibold py-3 rounded-xl transition">
                                                <span wire:loading.remove wire:target="uploadProof({{ $order->id }})">Checkout</span>
                                                <span wire:loading wire:target="uploadProof({{ $order->id }})">Mengirim...</span>
                                            </button>
                                        </div>
                                    </template>

                                    <template x-if="expired">
                                        <div class="bg-red-50 border border-red-200 rounded-xl p-3 text-sm text-red-600 text-center">
                                            Waktu pembayaran habis, order otomatis dibatalkan.
                                        </div>
                                    </template>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>