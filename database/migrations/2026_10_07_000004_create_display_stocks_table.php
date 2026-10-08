<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('display_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->date('stock_date');
            $table->unsignedBigInteger('opening_qty')->default(0);
            $table->unsignedBigInteger('in_qty')->default(0);
            $table->unsignedBigInteger('out_qty')->default(0);
            $table->unsignedBigInteger('closing_qty')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'stock_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('display_stocks');
    }
};
