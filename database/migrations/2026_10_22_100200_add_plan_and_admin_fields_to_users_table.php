<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tarif, admin, bloklash va oxirgi faollik ustunlari.
     * Mavjud foydalanuvchilarning hammasiga "free" tarifi biriktiriladi.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->after('telegram_chat_id')->constrained('plans')->nullOnDelete();
            $table->date('plan_tugash_sanasi')->nullable()->after('plan_id');
            $table->boolean('is_admin')->default(false)->after('plan_tugash_sanasi');
            $table->boolean('is_blocked')->default(false)->after('is_admin');
            $table->timestamp('last_active_at')->nullable()->index()->after('is_blocked');
        });

        $freePlanId = DB::table('plans')->where('kod', 'free')->value('id');

        if ($freePlanId) {
            DB::table('users')->whereNull('plan_id')->update(['plan_id' => $freePlanId]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('plan_id');
            $table->dropIndex(['last_active_at']);
            $table->dropColumn(['plan_tugash_sanasi', 'is_admin', 'is_blocked', 'last_active_at']);
        });
    }
};
