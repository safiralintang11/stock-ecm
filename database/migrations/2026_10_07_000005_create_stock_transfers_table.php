<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('trx_no', 30)->unique();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('qty');
            $table->string('from_location', 50)->default('Stock Utama');
            $table->string('to_location', 50)->default('Stock Display');
            $table->date('transfer_date');
            $table->foreignId('user_id')->constrained();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['transfer_date', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');
    }
};
