<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_batches', function (Blueprint $table) {
            $table->id();
            // Referensi yang dilihat customer, dan dikirim ke Xendit sebagai reference_id
            $table->string('batch_number')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            // Total gabungan SEMUA order Non Tunai dalam 1x checkout — inilah kuncinya
            // customer cuma scan 1 QR walau belanja dari beberapa tenant sekaligus
            $table->decimal('total_amount', 12, 2);
            $table->enum('status', ['Pending', 'Berhasil', 'Kadaluarsa', 'Dibatalkan'])->default('Pending');
            // Referensi ke Xendit Payment Request (QRIS)
            $table->string('xendit_payment_request_id')->nullable()->unique(); // format: pr-xxxxxxxx
            $table->string('xendit_reference_id')->nullable()->unique();       // reference_id yang kita kirim
            $table->text('xendit_qr_string')->nullable();                     // payload buat dirender jadi gambar QR
            $table->string('xendit_status')->nullable();                      // status mentah dari Xendit
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expired_at')->nullable();
 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_batches');
    }
};
