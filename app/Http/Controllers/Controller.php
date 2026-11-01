<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Farm;

abstract class Controller
{
    /**
     * Kirgan foydalanuvchining joriy fermasi ('farm' middleware ortidagi marshrutlar uchun).
     */
    protected function currentFarm(): Farm
    {
        return auth()->user()?->activeFarm()
            ?? abort(403, __("Siz hech qaysi fermaga a'zo emassiz yoki a'zoligingiz to'xtatilgan. Ferma egasiga yoki administratsiyaga murojaat qiling."));
    }

    /**
     * Boshqa fermaning hayvoniga murojaat — 404 (403 emas: yozuv borligi oshkor qilinmaydi).
     */
    protected function ensureAnimalInFarm(Animal $animal): void
    {
        if ((int) $animal->farm_id !== (int) $this->currentFarm()->id) {
            abort(404);
        }
    }
}
