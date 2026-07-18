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
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->char('tenant_code', 1)->unique();
            // $table->foreignId('user_id')->constrained();
            $table->foreignId('reservation_id')->constrained()->nullable()->unique();
            $table->string('store_name')->nullable();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('phone')->nullable();
            $table->boolean('is_open')->default(false)->nullable();
            $table->string('tenant_img')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
