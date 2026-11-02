<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ferma amallari jurnali (egasi va menejer ko'radi). Ferma o'chsa yozuvlar ham o'chadi,
     * amalni bajargan foydalanuvchi o'chsa yozuv qoladi (user_id NULL).
     */
    public function up(): void
    {
        Schema::create('farm_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained('farms')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('amal', 50);
            $table->string('obyekt_turi', 50);
            $table->unsignedBigInteger('obyekt_id')->nullable();
            $table->json('eski_qiymat')->nullable();
            $table->json('yangi_qiymat')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['farm_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farm_audit_logs');
    }
};
