<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tariflar. Standart 4 ta tarif shu yerda qo'shiladi, narxni admin o'zi qo'yadi.
     */
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('kod', 20)->unique();
            $table->string('nomi', 100);
            $table->unsignedBigInteger('narx_som')->nullable();
            $table->unsignedInteger('max_hayvon')->default(0);
            $table->unsignedInteger('oylik_chat_limit')->default(0);
            $table->unsignedInteger('oylik_vision_limit')->default(0);
            $table->boolean('faol')->default(true);
            $table->timestamps();
        });

        $now = now();

        DB::table('plans')->insert([
            ['kod' => 'free', 'nomi' => 'Free', 'narx_som' => null, 'max_hayvon' => 10, 'oylik_chat_limit' => 20, 'oylik_vision_limit' => 3, 'faol' => true, 'created_at' => $now, 'updated_at' => $now],
            ['kod' => 'basic', 'nomi' => 'Basic', 'narx_som' => null, 'max_hayvon' => 50, 'oylik_chat_limit' => 200, 'oylik_vision_limit' => 30, 'faol' => true, 'created_at' => $now, 'updated_at' => $now],
            ['kod' => 'pro', 'nomi' => 'Pro', 'narx_som' => null, 'max_hayvon' => 200, 'oylik_chat_limit' => 600, 'oylik_vision_limit' => 100, 'faol' => true, 'created_at' => $now, 'updated_at' => $now],
            ['kod' => 'max', 'nomi' => 'Max', 'narx_som' => null, 'max_hayvon' => 1000, 'oylik_chat_limit' => 1500, 'oylik_vision_limit' => 300, 'faol' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
