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
            // Kode unique Order per tenant
            $table->string('order_number')->unique();
            // $table->foreignId('tenant_id')->nullable()->constrained('tenants')->onDelete('set null');
            // Berisi tenant_code, store_name, phone
            $table->json('data_tenant');
            $table->foreignId('user_id')->constrained()->nullable();
            $table->enum('status', [
                'Pending', 
                // 'Diterima', 
                'Diproses', 
                'Siap Diambil', 
                'Selesai', 
                'Dibatalkan'
            ])->default('Pending');
            $table->decimal('total_amount', 10, 2);
            $table->enum('payment_status', ['Belum Dibayar', 'Menunggu Konfirmasi','Sudah Dibayar'])->default('Belum Dibayar');
            $table->enum('payment_method', ['Tunai', 'Non Tunai']);
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->onDelete('set null');
            // Berisi type, name_payment
            $table->json('data_payment_method')->nullable();
            $table->time('pickup_time');
            $table->foreignId('pickup_slot_id')->nullable()->constrained('pickup_slots')->onDelete('set null');
            // Berisi dayPickup, start_time, end_time
            $table->json('data_pickup_slot');
            // Untuk payment_method non tunai
            $table->string('payment_proof_img')->nullable();
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
