<?php

use Livewire\Component;
use App\Models\PaymentBatch;
use App\Services\XenditQrisService;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    public $batches = [];

    public function mount()
    {
        $this->expireOldBatches();
        $this->loadBatches();
    }

    public function refresh()
    {
        $this->expireOldBatches();
        $this->loadBatches();
    }

    public function loadBatches()
    {
        $this->batches = PaymentBatch::with(['orders.items'])
            ->where('user_id', Auth::id())
            ->where('status', 'Pending')
            ->orderBy('created_at')
            ->get();
    }

    private function expireOldBatches()
    {
        PaymentBatch::where('user_id', Auth::id())
            ->where('status', 'Pending')
            ->where('expired_at', '<=', now())
            ->get()
            ->each(fn (PaymentBatch $batch) => $batch->markAsExpired());
    }

    /**
     * Fallback kalau webhook belum sampai (mis. development lokal tanpa tunnel
     * publik) -- tombol ini query LANGSUNG ke Xendit, tidak cuma nunggu webhook.
     */
    public function checkStatus($batchId)
    {
        $batch = PaymentBatch::where('user_id', Auth::id())->findOrFail($batchId);

        if (!$batch->xendit_payment_request_id) {
            return;
        }

        $result = app(XenditQrisService::class)->getPaymentRequest($batch->xendit_payment_request_id);
        $status = $result['status'] ?? null;

        $batch->update(['xendit_status' => $status]);

        match ($status) {
            'SUCCEEDED' => $batch->markAsPaid(),
            'EXPIRED', 'FAILED' => $batch->markAsExpired(),
            default => null,
        };

        $this->loadBatches();
    }

    /**
     * HANYA untuk API key mode test/sandbox Xendit -- pastikan tombol ini
     * disembunyikan di production (dicek dari config('services.xendit.test_mode')).
     */
    public function simulatePayment($batchId)
    {
        if (!config('services.xendit.test_mode')) {
            return;
        }

        $batch = PaymentBatch::where('user_id', Auth::id())->findOrFail($batchId);

        if (!$batch->xendit_payment_request_id) {
            return;
        }

        app(XenditQrisService::class)->simulatePayment(
            $batch->xendit_payment_request_id,
            (float) $batch->total_amount
        );

        $this->dispatch(
            'notify',
            message: 'Simulasi pembayaran dikirim. Menunggu konfirmasi webhook Xendit...',
            type: 'info'
        );
    }

    public function render()
    {
        return $this->view([
            'batches' => $this->batches,
        ])->layout('layouts.app')->title('UCIC Student Business Corner | Checkout Pembayaran');
    }
};
?>

<div wire:poll.5s="refresh">
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 mb-30">

            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-800">Checkout Pembayaran</h1>
                <p class="text-gray-500 mt-1">
                    Scan 1 QRIS untuk membayar semua pesanan Non Tunai sekaligus, dalam waktu
                    {{ \App\Models\PaymentBatch::PAYMENT_WINDOW_MINUTES }} menit. Halaman ini otomatis
                    memperbarui status begitu pembayaran diterima.
                </p>
            </div>

            @if($batches->isEmpty())
                <div class="text-center py-16 bg-white rounded-xl shadow-sm">
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">Tidak ada pembayaran yang perlu diselesaikan</h3>
                    <p class="text-gray-500 mb-6">Order non tunai kamu sudah dibayar, dibatalkan, atau sudah lewat batas waktu.</p>
                    <a href="/my-order" class="inline-block px-6 py-3 bg-orange-500 text-white rounded-xl hover:bg-orange-600 transition">
                        Lihat Order Saya
                    </a>
                </div>
            @else
                <div class="space-y-6">
                    @foreach($batches as $batch)
                        <div wire:key="batch-{{ $batch->id }}"
                             class="bg-white rounded-xl shadow-sm overflow-hidden"
                             x-data="{
                                expiresAt: {{ $batch->expired_at->timestamp }} * 1000,
                                remaining: 0,
                                expired: false,
                                interval: null,
                                init() {
                                    this.tick();
                                    this.interval = setInterval(() => this.tick(), 1000);
                                    this.$nextTick(() => this.renderQr());
                                },
                                tick() {
                                    this.remaining = Math.max(0, Math.floor((this.expiresAt - Date.now()) / 1000));
                                    if (this.remaining <= 0 && !this.expired) {
                                        this.expired = true;
                                        clearInterval(this.interval);
                                    }
                                },
                                get display() {
                                    const m = Math.floor(this.remaining / 60).toString().padStart(2, '0');
                                    const s = (this.remaining % 60).toString().padStart(2, '0');
                                    return m + ':' + s;
                                },
                                renderQr() {
                                    const el = this.$refs.qrCanvas;
                                    if (!el || el.dataset.rendered || typeof QRCode === 'undefined') return;
                                    el.dataset.rendered = '1';
                                    new QRCode(el, {
                                        text: @js($batch->xendit_qr_string),
                                        width: 240,
                                        height: 240,
                                    });
                                }
                             }"
                        >
                            <div class="bg-gradient-to-r from-orange-500 to-amber-500 px-4 py-3 flex items-center justify-between">
                                <div>
                                    <h3 class="font-semibold text-white">Pembayaran QRIS</h3>
                                    <p class="text-orange-100 text-sm">{{ $batch->batch_number }} &middot; {{ $batch->orders->count() }} pesanan</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-white/80 text-xs uppercase tracking-wide">Sisa waktu</p>
                                    <p class="text-white font-bold text-lg tabular-nums" x-text="expired ? 'Habis' : display"></p>
                                </div>
                            </div>

                            <div class="p-5 space-y-5">
                                <template x-if="!expired">
                                    <div class="flex flex-col items-center gap-3">
                                        <div x-ref="qrCanvas" class="p-3 bg-white border border-gray-200 rounded-xl"></div>
                                        <p class="text-sm text-gray-500 text-center max-w-sm">
                                            Scan pakai aplikasi e-wallet atau mobile banking apa saja yang mendukung QRIS —
                                            GoPay, OVO, DANA, ShopeePay, m-Banking BCA/Mandiri, dll.
                                        </p>

                                        @if(config('services.xendit.test_mode'))
                                            <button wire:click="simulatePayment({{ $batch->id }})"
                                                    wire:loading.attr="disabled"
                                                    class="text-xs px-3 py-1.5 rounded-full bg-amber-100 text-amber-700 hover:bg-amber-200 transition">
                                                🧪 Simulasikan Pembayaran (Test Mode)
                                            </button>
                                        @endif

                                        <button wire:click="checkStatus({{ $batch->id }})"
                                                wire:loading.attr="disabled"
                                                class="text-xs text-gray-400 hover:text-gray-600 underline">
                                            Cek status manual
                                        </button>
                                    </div>
                                </template>

                                <template x-if="expired">
                                    <div class="bg-red-50 border border-red-200 rounded-xl p-3 text-sm text-red-600 text-center">
                                        Waktu pembayaran habis, order otomatis dibatalkan.
                                    </div>
                                </template>

                                <div class="border-t pt-4 space-y-3">
                                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Rincian Pesanan</p>

                                    @foreach($batch->orders as $order)
                                        <div class="bg-gray-50 rounded-lg p-3">
                                            <div class="flex justify-between text-sm font-medium text-gray-700 mb-1">
                                                <span>{{ $order->data_tenant['store_name'] ?? 'Tenant' }} ({{ $order->data_tenant['tenant_code'] ?? '' }})</span>
                                                <span>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                                            </div>
                                            @foreach($order->items as $item)
                                                <div class="flex justify-between text-xs text-gray-500">
                                                    <span>{{ $item->data_product['name'] ?? '-' }} x{{ $item->quantity }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div>

                                <div class="flex justify-between font-bold text-gray-800 border-t pt-3">
                                    <span>Total Dibayar</span>
                                    <span class="text-orange-600">Rp {{ number_format($batch->total_amount, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>