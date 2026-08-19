<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Pakai Xendit Payment Requests API (v3), BUKAN endpoint /qr_codes lama yang
 * sekarang sudah diarsipkan Xendit. Auth pakai HTTP Basic: secret key sebagai
 * username, password kosong.
 */
class XenditQrisService
{
    private string $baseUrl = 'https://api.xendit.co';

    private string $apiVersion = '2024-11-11';

    private string $secretKey;

    public function __construct()
    {
        $this->secretKey = (string) config('services.xendit.secret_key');
    }

    /**
     * Bikin 1 Payment Request QRIS untuk sejumlah `amount`. Dipakai untuk
     * menggabungkan beberapa order (lintas tenant sekalipun) jadi 1 kali scan.
     */
    public function createQrisPaymentRequest(
        string $referenceId,
        float $amount,
        ?string $description = null
    ): array {
        if ($amount < 1) {
            throw new RuntimeException('Nominal QRIS minimal Rp1.');
        }
    
        if ($amount > 10_000_000) {
            throw new RuntimeException('Nominal QRIS maksimal Rp10.000.000.');
        }
    
        $response = Http::withBasicAuth(
                $this->secretKey,
                ''
            )
            ->withHeaders([
                'api-version' => $this->apiVersion,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->timeout(30)
            ->post("{$this->baseUrl}/v3/payment_requests", [
                'reference_id' => $referenceId,
                'type' => 'PAY',
                'country' => 'ID',
                'currency' => 'IDR',
                'request_amount' => $amount,
                'capture_method' => 'AUTOMATIC',
                'channel_code' => 'QRIS',
                'channel_properties' => [],
                'description' => $description,
            ]);
    
        if ($response->failed()) {
            throw new RuntimeException(
                'Gagal membuat QRIS Xendit: ' .
                $response->status() .
                ' - ' .
                $response->body()
            );
        }
    
        $data = $response->json();
    
        $qrString = collect($data['actions'] ?? [])
            ->firstWhere('descriptor', 'QR_STRING')['value']
            ?? null;
    
        return [
            'payment_request_id' => $data['payment_request_id'] ?? null,
            'qr_string' => $qrString,
            'status' => $data['status'] ?? null,
            'raw' => $data,
        ];
    }

    /**
     * HANYA jalan di API key mode test/sandbox Xendit (prefix xnd_development_...).
     * Hasilnya tetap dikirim lewat webhook secara async, bukan langsung di response ini.
     */
    public function simulatePayment(string $paymentRequestId, float $amount): array
    {
        $response = Http::withBasicAuth($this->secretKey, '')
            ->withHeaders(['api-version' => $this->apiVersion])
            ->post("{$this->baseUrl}/v3/payment_requests/{$paymentRequestId}/simulate", [
                'amount' => $amount,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Gagal simulasi pembayaran Xendit: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Fallback cek status manual — berguna kalau webhook belum sampai
     * (mis. development lokal tanpa tunnel publik seperti ngrok/expose).
     */
    public function getPaymentRequest(string $paymentRequestId): array
    {
        $response = Http::withBasicAuth($this->secretKey, '')
            ->withHeaders(['api-version' => $this->apiVersion])
            ->get("{$this->baseUrl}/v3/payment_requests/{$paymentRequestId}");

        if ($response->failed()) {
            throw new RuntimeException('Gagal mengambil status Xendit: ' . $response->body());
        }

        return $response->json();
    }
}