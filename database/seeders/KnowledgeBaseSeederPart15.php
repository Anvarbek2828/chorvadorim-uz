<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KnowledgeBaseSeederPart15 extends Seeder
{
    public function run(): void
    {
        $entries = [
            ['direction' => 'sut', 'category' => 'salomatlik', 'topic' => 'Sog\'ishdan oldingi yelin gigiyenasi va uni quruq artish muhimligi', 'keywords' => 'pre-dipping, sog\'ish oldidan, yelin gigiyenasi, quruq artish, mastit, patogen', 'content' => "Sog'ishni boshlashdan oldin yelin uchlarini tozalash, maxsus eritmaga botirish va quruq bir martalik salfetka bilan butunlay quritib artish shart. Yelin quruq bo'lmasa, sog'ish stakanlari ostidan iflos suv oqib kirib, yelinga patogen bakteriyalarni olib keladi va mastit xavfini oshiradi."],
            ['direction' => 'sut', 'category' => 'boshqaruv', 'topic' => 'Poda bo\'yicha umumiy somatik hujayralar (BTSCC) monitoringi', 'keywords' => 'BTSCC, bulk tank, somatik hujayra, sut sifati, mastit, podaning salomatligi', 'content' => "Sut zavodiga topshiriladigan umumiy sut idishidagi somatik hujayralar soni (BTSCC) podaning umumiy sog'lomlik darajasini ko'rsatadi. BTSCC doimiy ravishda 200,000 dan past bo'lishi optimaldir. Agar bu ko'rsatkich oshib ketsa, podada yashirin mastit bilan kasallangan sigirlar ko'paygani aniqlanib, zudlik bilan CMT testi orqali tekshirilishi kerak."],
        ];

        foreach ($entries as $entry) {
            DB::table('knowledge_base')->insert(array_merge($entry, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
