<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vaksinatsiya va eslatmalar tizimi uchun jadval.
     * Har bir hayvon uchun vaksinatsiya tarixi va keyingi
     * muddat avtomatik hisoblab boriladi.
     */
    public function up(): void
    {
        Schema::create('vaccinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->constrained()->cascadeOnDelete();
            $table->string('vaccine_name');
            $table->date('administered_date');
            $table->date('next_due_date')->nullable();
            $table->string('administered_by')->nullable(); // veterinar ismi
            $table->text('notes')->nullable();
            $table->boolean('reminder_sent')->default(false);
            $table->timestamps();

            $table->index(['animal_id', 'next_due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vaccinations');
    }
};
