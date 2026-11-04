<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Eslatmani "bajarildi" deb belgilash va foydalanuvchi tili (bot xabarlari uchun).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->dateTime('bajarilgan_at')->nullable()->after('sent_at');
        });

        // Sessiyadagi til kodlari bilan bir xil: uz, uz_cyrl, ru. NULL — noma'lum (bot 3 tilda yozadi)
        Schema::table('users', function (Blueprint $table) {
            $table->string('locale', 10)->nullable()->after('current_farm_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('locale');
        });

        Schema::table('reminders', function (Blueprint $table) {
            $table->dropColumn('bajarilgan_at');
        });
    }
};
