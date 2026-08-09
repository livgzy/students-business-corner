<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class FonnteService
{
    public static function sendOrderNotification(Order $order): void
    {
        $phone = data_get($order->data_tenant, 'phone');

        if (empty($phone)) {
            Log::warning("Fonnte: nomor WA tenant kosong untuk order {$order->order_number}");
            return;
        }

        $storeName = data_get($order->data_tenant, 'store_name', 'Tenant');
        $day = data_get($order->data_pickup_slot, 'dayPickup');
        $time = $order->pickup_time ? \Carbon\Carbon::parse($order->pickup_time)->format('H:i') : '-';

        $itemLines = $order->items->map(function ($item) {
            $name = data_get($item->data_product, 'name', 'Item');
            $line = "- {$name} x{$item->quantity}";
        
            if (!empty($item->notes)) {
                $line .= "\n  Catatan: {$item->notes}";
            }
        
            return $line;
        })->implode("\n");
        
        $statusBayar = $order->payment_method === 'Tunai'
            ? 'Bayar di Tempat (Tunai)'
            : $order->payment_status;
        
            $customerName  = $order->user->name ?? '-';
            $customerPhone = $order->user->phone ?? '-';
            
            $paymentDetail = '';
            
            if (!empty($order->data_payment_method)) {
                $dataPayment = $order->data_payment_method;
                if (!empty($dataPayment['name_payment'])) {
                    $typeLabel = match ($dataPayment['type'] ?? null) {
                        'bank_transfer' => 'Transfer Bank',
                        'e_wallet'      => 'E-Wallet',
                        'qris'          => 'QRIS',
                        default         => $dataPayment['type'] ?? '-',
                    };
            
                    $paymentDetail = "\nMetode: {$dataPayment['name_payment']} ({$typeLabel})";
                }
            }
            
            $message = "*Order Baru Student Business Corner (SBC) - {$storeName}*\n\n"
                . "No. Order: {$order->order_number}\n"
                . "Pemesan: {$customerName}\n"
                . "No. HP: {$customerPhone}\n"
                . "Metode Bayar: {$order->payment_method}{$paymentDetail}\n"
                . "Status Bayar: {$statusBayar}\n\n"
                . "Item:\n{$itemLines}\n\n"
                . "Total: Rp " . number_format($order->total_amount, 0, ',', '.') . "\n"
                . "Pickup: {$day}, {$time}";

        $response = Http::withHeaders([
            'Authorization' => config('services.fonnte.token'),
        ])->asForm()->post('https://api.fonnte.com/send', [
            'target'  => self::formatPhone($phone),
            'message' => $message,
        ]);

        if (!$response->successful()) {
            Log::error("Fonnte gagal kirim notifikasi order {$order->order_number}: " . $response->body());
        }
    }

    private static function formatPhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);

        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        return $phone;
    }
}