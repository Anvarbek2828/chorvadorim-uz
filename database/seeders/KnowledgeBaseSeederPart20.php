<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KnowledgeBaseSeederPart20 extends Seeder
{
    public function run(): void
    {
        $entries = [
            ['direction' => 'umumiy', 'category' => 'salomatlik', 'topic' => 'Quruq davrdagi sigirlarda tuyoqni profilaktik tekshirish va kesish', 'keywords' => 'tuyoq kesish, quruq davr, oqsoqlik, profilaktika, tuyoq shoxi, parvarish', 'content' => "Sigirni quruq davrga o'tkazish bosqichida uning tuyoqlarini tekshirib, kerak bo'lsa profilaktik kesish ishlarini bajarish eng maqbul vaqt hisoblanadi. Tuqishdan keyingi og'ir stress davrida tuyoq muammolarining chiqmasligi uchun bu tadbir muhim ahamiyatga ega."],
            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Silos xandaq yuzasini toza va tekis kesish (Face management)', 'keywords' => 'silos yuzasi, face management, kesish, havo kirishi, ikkilamchi qizish, sifat', 'content' => "Silos xandaqidan kunlik ozuqa olishda uning yuzasi maxsus kesuvchi texnika yordamida tekis va silliq qilib kesilishi shart. Qo'lda ketmon yoki belkurak bilan g'ovak qilib buzish silos ichiga havo kirishini osonlashtirib, uning tez qizishiga va buzilishiga olib keladi."],
            ['direction' => 'sut', 'category' => 'boshqaruv', 'topic' => 'Sog\'ish zalida sigirlar oqimini tezlashtirish va guruhlash tamoyillari', 'keywords' => 'sog\'ish zali, cow flow, guruhlash, vaqtni tejash, stress, samaradorlik', 'content' => "Sog'ish zalining ish unumdorligini oshirish uchun sigirlar laktatsiya davri, sut hajmi va yoshiga qarab aniq guruhlarga bo'linishi kerak. Guruhlarning o'lchami sog'ish zalining sig'imiga mos kelishi kutish zalida sigirlarning uzoq vaqt turib qolishi va stressga tushishining oldini oladi."],
        ];

        foreach ($entries as $entry) {
            DB::table('knowledge_base')->insert(array_merge($entry, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
