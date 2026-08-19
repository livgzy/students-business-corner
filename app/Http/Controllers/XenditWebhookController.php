<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PaymentBatch;

class XenditWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // WAJIB: verifikasi header ini, jangan percaya payload begitu saja —
        // endpoint webhook selalu publik dan bisa dipanggil siapa saja
        $token = $request->header('x-callback-token');
 
        if (! hash_equals((string) config('services.xendit.webhook_token'), (string) $token)) {
            return response('Unauthorized', 401);
        }
 
        $payload = $request->json()->all();
        $data = $payload['data'] ?? $payload; // sebagian event membungkus payload dalam key 'data'
 
        $paymentRequestId = $data['id'] ?? $data['payment_request_id'] ?? null;
        $referenceId      = $data['reference_id'] ?? null;
        $status           = $data['status'] ?? null;
 
        $batch = PaymentBatch::query()
            ->when($paymentRequestId, fn ($q) => $q->orWhere('xendit_payment_request_id', $paymentRequestId))
            ->when($referenceId, fn ($q) => $q->orWhere('xendit_reference_id', $referenceId))
            ->first();
 
        if (! $batch) {
            // Tetap balas 200 supaya Xendit tidak retry terus-menerus untuk
            // event yang memang bukan urusan sistem ini
            return response('OK', 200);
        }
 
        $batch->update(['xendit_status' => $status]);
 
        match ($status) {
            'SUCCEEDED'          => $batch->markAsPaid(),
            'EXPIRED', 'FAILED'  => $batch->markAsExpired(),
            default              => null,
        };
 
        return response('OK', 200);
    }
}
