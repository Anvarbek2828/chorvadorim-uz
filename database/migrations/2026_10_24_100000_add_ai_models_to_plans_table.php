<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Har bir tarif uchun AI chat va rasm tahlili modeli. Bo'sh bo'lsa config('ai.*_model') ishlatiladi.
     */
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->string('chat_model', 100)->nullable()->default('gpt-4o-mini')->after('oylik_vision_limit');
            $table->string('vision_model', 100)->nullable()->default('gpt-5.4-mini')->after('chat_model');
        });

        DB::table('plans')->update([
            'chat_model' => 'gpt-4o-mini',
            'vision_model' => 'gpt-5.4-mini',
        ]);
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['chat_model', 'vision_model']);
        });
    }
};
