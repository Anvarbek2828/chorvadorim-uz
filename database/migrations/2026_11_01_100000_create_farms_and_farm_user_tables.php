<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Xodimlar A: ma'lumotlarga egalik user_id dan farm_id ga o'tadi.
 * users dagi plan_id / plan_tugash_sanasi ataylab o'chirilmaydi (orqaga qaytish uchun).
 * Prod bazada xuddi shu o'zgarishlar database/sql/2026_11_xodimlar_A.sql orqali bajariladi.
 */
return new class extends Migration
{
    // Ferma ma'lumoti jadvallari: user_id qoladi ("kim yaratdi"), farm_id — kimniki
    private const FARM_TABLES = ['animals', 'ai_usage_logs', 'admin_audit_logs'];

    // sqlite users jadvalini qayta quradi; tranzaksiya ichida PRAGMA foreign_keys=OFF ishlamaydi va
    // users dan cascade bilan animals o'chib ketadi. MySQL da DDL baribir tranzaksiyasiz.
    public $withinTransaction = false;

    public function up(): void
    {
        Schema::create('farms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->date('plan_tugash_sanasi')->nullable();
            $table->timestamps();

            $table->index('owner_id');
        });

        Schema::create('farm_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained('farms')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('role', ['owner', 'manager', 'worker', 'viewer']);
            $table->enum('status', ['active', 'suspended'])->default('active');
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('must_change_password')->default(false);
            $table->timestamps();

            $table->unique(['farm_id', 'user_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('current_farm_id')->nullable()->after('plan_tugash_sanasi')->constrained('farms')->nullOnDelete();
        });

        foreach (self::FARM_TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedBigInteger('farm_id')->nullable()->after('id')->index();
            });
        }

        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedInteger('max_staff')->nullable()->after('max_hayvon');
        });

        // NULL = cheksiz (Max)
        foreach (['free' => 0, 'basic' => 2, 'pro' => 5] as $kod => $maxStaff) {
            DB::table('plans')->where('kod', $kod)->update(['max_staff' => $maxStaff]);
        }

        $this->migrateExistingUsers();
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('max_staff');
        });

        foreach (self::FARM_TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropIndex(['farm_id']);
                $table->dropColumn('farm_id');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('current_farm_id');
        });

        Schema::dropIfExists('farm_user');
        Schema::dropIfExists('farms');
    }

    /**
     * Har bir mavjud foydalanuvchi o'z fermasining egasi bo'ladi. SQL fayldagi ko'chirish bilan bir xil mantiq.
     */
    private function migrateExistingUsers(): void
    {
        $now = now();

        DB::table('users')->orderBy('id')->each(function ($user) use ($now) {
            $farmId = DB::table('farms')->insertGetId([
                'name' => $user->name,
                'owner_id' => $user->id,
                'plan_id' => $user->plan_id,
                'plan_tugash_sanasi' => $user->plan_tugash_sanasi,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('farm_user')->insert([
                'farm_id' => $farmId,
                'user_id' => $user->id,
                'role' => 'owner',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('users')->where('id', $user->id)->update(['current_farm_id' => $farmId]);
            DB::table('animals')->where('user_id', $user->id)->update(['farm_id' => $farmId]);
            DB::table('ai_usage_logs')->where('user_id', $user->id)->update(['farm_id' => $farmId]);
            DB::table('admin_audit_logs')->where('obyekt_turi', 'user')->where('obyekt_id', $user->id)->update(['farm_id' => $farmId]);
        });

        // Foydalanuvchisi o'chirilgan AI yozuvlari — hayvon orqali
        DB::table('ai_usage_logs')->whereNull('farm_id')->whereNotNull('animal_id')->orderBy('id')->each(function ($log) {
            $farmId = DB::table('animals')->where('id', $log->animal_id)->value('farm_id');

            if ($farmId) {
                DB::table('ai_usage_logs')->where('id', $log->id)->update(['farm_id' => $farmId]);
            }
        });
    }
};
