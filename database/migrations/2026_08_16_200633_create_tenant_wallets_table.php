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
        Schema::create('tenant_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('reservations')->unique()->onDelete('cascade');
            $table->char('tenant_code', 1);
            // Akumulasi dari order dengan payment_status = 'Sudah Dibayar'
            $table->decimal('total_earned', 12, 2)->default(0);
            // Akumulasi payout dengan status 'Berhasil'
            // saldo tersedia = total_earned - total_paid_out (dihitung di aplikasi/accessor)
            $table->decimal('total_paid_out', 12, 2)->default(0); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_wallets');
    }
};
