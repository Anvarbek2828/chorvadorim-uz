<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Obuna va to'lov: to'lovlar tarixi, obuna bot xabarlari, kunlik ishlar holati.
 * Prod bazada xuddi shu o'zgarishlar database/sql/2026_11_obuna.sql orqali bajariladi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farm_payments', function (Blueprint $table) {
            $table->id();
            // Ferma o'chsa ham to'lov yozuvi qoladi (farm_nomi — o'sha paytdagi nomi)
            $table->foreignId('farm_id')->nullable()->constrained('farms')->nullOnDelete();
            $table->string('farm_nomi');
            $table->enum('tur', ['tolov', 'bonus']);
            $table->foreignId('plan_id')->constrained('plans')->restrictOnDelete();
            $table->unsignedBigInteger('avvalgi_plan_id')->nullable();
            $table->unsignedTinyInteger('oylar');
            $table->unsignedBigInteger('summa')->default(0);
            $table->enum('usul', ['bank', 'naqd', 'boshqa'])->nullable();
            $table->string('hujjat_raqami', 100)->nullable();
            $table->text('izoh')->nullable();
            $table->date('avvalgi_tugash')->nullable();
            $table->date('yangi_tugash');
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('bekor_qilingan_at')->nullable();
            $table->text('bekor_sababi')->nullable();
            $table->foreignId('bekor_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['farm_id', 'created_at']);
        });

        Schema::create('subscription_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained('farms')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('tur', ['oldin_3kun', 'tugadi', 'faqat_oqish']);
            $table->date('tugash_sanasi');
            $table->enum('holat', ['yuborildi', 'telegram_yoq', 'xato']);
            $table->unsignedTinyInteger('urinishlar')->default(0);
            $table->text('xato')->nullable();
            $table->timestamps();

            $table->unique(['farm_id', 'user_id', 'tur', 'tugash_sanasi'], 'subscription_notifications_unique');
        });

        Schema::create('system_runs', function (Blueprint $table) {
            $table->string('nom', 50)->primary();
            $table->date('last_run_date')->nullable();
            $table->dateTime('last_started_at')->nullable();
            $table->dateTime('last_finished_at')->nullable();
            $table->text('last_result')->nullable();
        });

        // Qator bo'lmasa kunlik ish hech qachon ishga tushmaydi (atomik UPDATE faqat mavjud qatorni o'zgartiradi)
        DB::table('system_runs')->insert([
            ['nom' => 'obuna', 'last_run_date' => null, 'last_started_at' => null, 'last_finished_at' => null, 'last_result' => null],
            ['nom' => 'eslatmalar', 'last_run_date' => null, 'last_started_at' => null, 'last_finished_at' => null, 'last_result' => null],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('system_runs');
        Schema::dropIfExists('subscription_notifications');
        Schema::dropIfExists('farm_payments');
    }
};
