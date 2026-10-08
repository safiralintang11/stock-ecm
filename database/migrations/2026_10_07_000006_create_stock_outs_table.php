<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_outs', function (Blueprint $table) {
            $table->id();
            $table->string('trx_no', 30)->unique();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('qty');
            $table->date('out_date');
            $table->foreignId('user_id')->constrained();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['out_date', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_outs');
    }
};
