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
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_wallet_id')->constrained('tenant_wallets')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('user_tenants')->onDelete('cascade');
            $table->decimal('amount', 12, 2);
            $table->decimal('fee_amount', 10, 2)->nullable();
            $table->decimal('net_amount', 10, 2)->nullable();
            // Rekening/e-wallet tujuan, diambil dari payment_methods milik tenant ybs
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->onDelete('set null');
            // Snapshot detail rekening (type, name_payment, account_number, account_name) saat payout
            // diajukan, supaya tetap ada walau payment_methods-nya nanti dihapus saat tenant direset
            $table->json('data_payment_method');
            $table->enum('status', ['Pending', 'Diproses', 'Berhasil', 'Ditolak'])
                ->default('Pending');
            $table->string('xendit_payout_id')->nullable();
            $table->string('xendit_status')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
