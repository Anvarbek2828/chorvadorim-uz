<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bank kuzatuvi endi fermer ruxsati bilan faollashadi.
     *
     * Mavjud yozuvlar fermer roziligisiz yaratilgan, shuning uchun ular 'pending'
     * holatiga o'tadi va bildirishnoma olmaydi. Bank vakili /kuzatish buyrug'ini
     * qayta yuborsa, fermerga ruxsat so'rovi boradi.
     */
    public function up(): void
    {
        Schema::table('bank_animal_watchers', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->after('animal_id');
            $table->string('requester_name')->nullable()->after('status');
            $table->timestamp('responded_at')->nullable()->after('requester_name');
        });
    }

    public function down(): void
    {
        Schema::table('bank_animal_watchers', function (Blueprint $table) {
            $table->dropColumn(['status', 'requester_name', 'responded_at']);
        });
    }
};
