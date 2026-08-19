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
        Schema::create('tenant_histories', function (Blueprint $table) {
            $table->id();
            $table->char('tenant_code', 1);
            $table->foreignId('reservation_id')->constrained('reservations')->onDelete('cascade')->unique();
            $table->string('store_name');
            $table->string('slug')->nullable();
            $table->text('description')->nullable();
            $table->text('phone')->nullable();
            $table->string('tenant_img')->nullable();
            $table->timestamps();
            $table->index(['tenant_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_histories');
    }
};
