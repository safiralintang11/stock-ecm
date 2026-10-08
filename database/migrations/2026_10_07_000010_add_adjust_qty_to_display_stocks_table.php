<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('display_stocks', function (Blueprint $table) {
            $table->integer('adjust_qty')->default(0)->after('out_qty');
        });
    }

    public function down(): void
    {
        Schema::table('display_stocks', function (Blueprint $table) {
            $table->dropColumn('adjust_qty');
        });
    }
};
