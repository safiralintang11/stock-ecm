<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->enum('scope', ['main', 'display']);
            $table->integer('qty'); // signed delta: + increase, - decrease
            $table->unsignedBigInteger('stock_before');
            $table->unsignedBigInteger('stock_after');
            $table->string('reason');
            $table->date('adjust_date');
            $table->foreignId('user_id')->constrained();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};
