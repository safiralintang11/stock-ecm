<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['INITIAL_STOCK', 'STOCK_IN', 'STOCK_TRANSFER', 'STOCK_DISPLAY', 'OUT', 'ADJUSTMENT']);
            $table->unsignedBigInteger('qty');
            $table->unsignedBigInteger('main_before')->default(0);
            $table->unsignedBigInteger('main_after')->default(0);
            $table->unsignedBigInteger('display_before')->default(0);
            $table->unsignedBigInteger('display_after')->default(0);
            $table->date('trx_date');
            $table->foreignId('user_id')->constrained();
            $table->string('reference')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index(['trx_date', 'product_id']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_histories');
    }
};
