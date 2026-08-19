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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            // Berisi tenant_code, store_name, phone
            $table->json('data_tenant');
            $table->foreignId('reservation_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('order_type', ['reguler', 'pre_order'])->default('reguler');
            $table->enum('status', [
                'Pending',
                'Diproses',
                'Selesai',
                'Dibatalkan'
            ])->default('Pending');
 
            $table->decimal('total_amount', 10, 2);
            $table->enum('payment_status', ['Belum Dibayar', 'Menunggu Konfirmasi', 'Sudah Dibayar'])->default('Belum Dibayar');
            $table->enum('payment_method', ['Tunai', 'Non Tunai']);
            // FIX: nullable, tidak wajib diisi di awal untuk order reguler
            $table->time('pickup_time')->nullable();
            $table->foreignId('pickup_slot_id')->nullable()->constrained('pickup_slots')->onDelete('set null');
            // Berisi dayPickup, start_time, end_time
            $table->json('data_pickup_slot')->nullable();
            // Untuk payment_method non tunai (opsional, kalau masih ingin simpan bukti manual)
            // $table->string('payment_proof_img')->nullable();
            // $table->string('xendit_external_id')->nullable()->unique();
            // $table->string('xendit_qr_id')->nullable();
            // $table->string('xendit_status')->nullable();
            // $table->timestamp('paid_at')->nullable();
            // $table->timestamp('expired_at')->nullable();
            $table->foreignId('payment_batch_id')->nullable()->constrained('payment_batches')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
