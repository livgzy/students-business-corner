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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            // $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('reservation_id')->nullable()->constrained('reservations')->unique()->onDelete('set null');
            $table->enum('type', ['bank_transfer', 'e_wallet']);
            // Nama payment: 'BCA', 'Mandiri', 'GoPay', 'OVO', 'DANA'
            $table->string('name_payment');
            // Nomor rekening atau Nomor HP (untuk e-wallet)
            $table->string('account_number');
            // Nama pemilik rekening/e-wallet
            $table->string('account_name');
            $table->timestamps();
            // BARU: agar histori payout tetap bisa telusur rekening yang dulu dipakai
            // walau rekening ini dihapus saat data tenant lama dibersihkan
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
