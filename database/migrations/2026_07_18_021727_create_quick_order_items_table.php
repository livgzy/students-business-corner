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
        // Schema::create('quick_order_items', function (Blueprint $table) {
        //     $table->id();
        //     $table->foreignId('quick_order_id')->constrained()->onDelete('cascade');
        //     $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('set null');
        //     $table->integer('quantity');
        //     $table->timestamps();
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quick_order_items');
    }
};
