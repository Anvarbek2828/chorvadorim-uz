<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI xizmatlari sozlamalari
    |--------------------------------------------------------------------------
    |
    | Narxlar USD da, 1 million token uchun. OpenAI javobidagi model nomi
    | (masalan "gpt-4o-mini-2024-07-18") shu ro'yxatdagi kalit bilan boshlansa,
    | o'sha narx olinadi.
    |
    */

    'chat_model' => 'gpt-4o-mini',

    'vision_model' => 'gpt-4o-mini',

    'pricing' => [
        'gpt-4o-mini' => [
            'input' => 0.15,
            'output' => 0.60,
        ],
    ],

    // Chat javobining eng ko'p uzunligi (token)
    'chat_max_tokens' => (int) env('AI_CHAT_MAX_TOKENS', 800),

    // Rasm tahlili javobining eng ko'p uzunligi (token)
    'vision_max_tokens' => (int) env('AI_VISION_MAX_TOKENS', 600),

    // Rasm OpenAI'ga yuborilishidan oldin eng uzun tomoni shu o'lchamgacha kichraytiriladi (px)
    'vision_max_side' => 1024,

    // Kunlik limit = oylik limit / daily_limit_divisor, lekin kamida daily_limit_min
    'daily_limit_divisor' => 10,
    'daily_limit_min' => 3,

    // Bitta hayvonga kuniga eng ko'p rasm tahlili
    'vision_per_animal_daily' => 3,

    // Platforma bo'yicha oylik AI xarajati chegarasi (USD). Oshsa, AI vaqtincha to'xtaydi
    'platform_monthly_budget_usd' => (float) env('AI_MONTHLY_BUDGET_USD', 30),

    // Admin panelda so'mda ko'rsatish uchun dollar kursi
    'usd_to_som' => (float) env('USD_TO_SOM', 12800),

    // Kun va oy chegaralari shu vaqt zonasi bo'yicha hisoblanadi
    'timezone' => 'Asia/Tashkent',

];
