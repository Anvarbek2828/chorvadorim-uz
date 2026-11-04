<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * To'lov usuliga yangi qiymat: 'tolov_tizimi' ("To'lov tizimlari orqali"). ENUM oxiriga qo'shiladi, mavjud qiymatlar o'zgarmaydi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farm_payments', function (Blueprint $table) {
            $table->enum('usul', ['bank', 'naqd', 'boshqa', 'tolov_tizimi'])->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('farm_payments', function (Blueprint $table) {
            $table->enum('usul', ['bank', 'naqd', 'boshqa'])->nullable()->default(null)->change();
        });
    }
};
