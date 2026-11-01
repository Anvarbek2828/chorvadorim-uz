<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Har bir AI so'rovi (chat / vision) va uning narxi.
     * Foydalanuvchi yoki hayvon o'chirilsa ham xarajat tarixi saqlanib qoladi.
     */
    public function up(): void
    {
        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('animal_id')->nullable()->constrained()->nullOnDelete();
            $table->string('turi', 10);
            $table->string('model', 100);
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->decimal('narx_usd', 12, 6)->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'turi', 'created_at']);
            $table->index(['animal_id', 'turi', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_logs');
    }
};
