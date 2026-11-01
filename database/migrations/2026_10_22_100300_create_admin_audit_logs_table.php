<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Admin amallari jurnali. Admin o'chirilsa ham yozuvlar saqlanadi.
     */
    public function up(): void
    {
        Schema::create('admin_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('amal', 50);
            $table->string('obyekt_turi', 50);
            $table->unsignedBigInteger('obyekt_id')->nullable();
            $table->json('eski_qiymat')->nullable();
            $table->json('yangi_qiymat')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['obyekt_turi', 'obyekt_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_audit_logs');
    }
};
