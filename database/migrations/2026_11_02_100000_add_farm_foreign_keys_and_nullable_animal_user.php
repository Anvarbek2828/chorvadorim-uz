<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Xodimlar B: xavfsiz o'chirish.
 * - animals.user_id ("kim yaratdi") NULL bo'la oladi: xodim akkaunti o'chsa, hayvonlar fermada qoladi.
 * - animals.farm_id va ai_usage_logs.farm_id fermaga bog'lanadi: ferma o'chsa, ma'lumot ham o'chadi.
 * Prod bazada xuddi shu o'zgarishlar database/sql/2026_11_xodimlar_B.sql orqali bajariladi.
 */
return new class extends Migration
{
    // sqlite animals jadvalini qayta quradi; tranzaksiya ichida PRAGMA foreign_keys=OFF ishlamaydi
    // va eski jadval o'chirilganda expenses/incomes cascade bilan o'chib ketadi
    public $withinTransaction = false;

    public function up(): void
    {
        // Mavjud bo'lmagan fermaga ishora qiluvchi qiymatlar FK qo'shilishiga to'sqinlik qiladi
        // (masalan, Faza A dan keyin egasi akkauntini o'chirgan fermaning AI yozuvlari)
        foreach (['animals', 'ai_usage_logs'] as $tableName) {
            DB::table($tableName)
                ->whereNotNull('farm_id')
                ->whereNotIn('farm_id', DB::table('farms')->select('id'))
                ->update(['farm_id' => null]);
        }

        Schema::table('animals', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('animals', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('farm_id')->references('id')->on('farms')->cascadeOnDelete();
        });

        Schema::table('ai_usage_logs', function (Blueprint $table) {
            $table->foreign('farm_id')->references('id')->on('farms')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ai_usage_logs', function (Blueprint $table) {
            $table->dropForeign(['farm_id']);
        });

        Schema::table('animals', function (Blueprint $table) {
            $table->dropForeign(['farm_id']);
            $table->dropForeign(['user_id']);
        });

        // user_id si bo'sh hayvonlar NOT NULL ga qaytishga to'sqinlik qiladi; ular o'chirilmaydi — down() to'xtaydi
        if (DB::table('animals')->whereNull('user_id')->exists()) {
            throw new RuntimeException('animals jadvalida user_id NULL bo\'lgan yozuvlar bor, orqaga qaytarib bo\'lmaydi.');
        }

        Schema::table('animals', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
