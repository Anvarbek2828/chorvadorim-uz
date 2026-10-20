<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pasport raqami takrorlanmasligi uchun unique indeks.
     *
     * DIQQAT: bu migratsiyadan oldin bazada bir xil pasport raqamli hayvonlar
     * (shu jumladan arxivdagi, ya'ni soft-delete qilinganlar) bo'lishi mumkin.
     * Unda indeks yaratilmaydi va migratsiya xato bilan to'xtaydi. Takrorlarni
     * quyidagi so'rov bilan toping va qo'lda tuzating (raqamni to'g'rilang yoki
     * keraksiz yozuvda NULL qiling), keyin migratsiyani qayta ishga tushiring:
     *
     *   SELECT passport_number, COUNT(*) AS soni
     *   FROM animals
     *   WHERE passport_number IS NOT NULL
     *   GROUP BY passport_number
     *   HAVING COUNT(*) > 1;
     *
     * Bo'sh qatorlar ('') ham takror hisoblanadi, ularni NULL qilish kerak:
     *
     *   UPDATE animals SET passport_number = NULL WHERE passport_number = '';
     */
    public function up(): void
    {
        $duplicates = DB::table('animals')
            ->whereNotNull('passport_number')
            ->select('passport_number')
            ->groupBy('passport_number')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();

        if ($duplicates > 0) {
            throw new RuntimeException(
                "animals jadvalida {$duplicates} ta takroriy pasport raqami bor. "
                . "Migratsiya ichidagi izohga qarang va avval takrorlarni tuzating."
            );
        }

        Schema::table('animals', function (Blueprint $table) {
            $table->unique('passport_number');
        });
    }

    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->dropUnique(['passport_number']);
        });
    }
};
