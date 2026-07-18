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
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['bank_transfer', 'e_wallet', 'qris']);
            //Nama payment: 'BCA', 'Mandiri', 'GoPay', 'OVO', 'DANA', 'QRIS'
            $table->string('name_payment');
            // Nomor rekening atau Nomor HP (untuk e-wallet)
            $table->string('account_number')->nullable();   
            // Nama pemilik rekening/e-wallet
            $table->string('account_name'); 
            // Path gambar QR (hanya diisi jika tipenya QRIS)
            $table->string('qr_img')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
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
