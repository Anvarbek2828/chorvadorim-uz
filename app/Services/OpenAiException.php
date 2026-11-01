<?php

namespace App\Services;

use RuntimeException;

class OpenAiException extends RuntimeException
{
    public function __construct(string $message, private bool $modelError = false)
    {
        parent::__construct($message);
    }

    /**
     * Model topilmasa, ruxsat bo'lmasa yoki model parametrni qabul qilmasa — shunga mos xabar,
     * aks holda chaqiruvchi bergan umumiy xabar.
     */
    public function userMessage(string $default): string
    {
        return $this->modelError
            ? __("Tanlangan AI modeli hozircha ishlamayapti. Iltimos, keyinroq qayta urinib ko'ring yoki administratorga murojaat qiling.")
            : $default;
    }
}
