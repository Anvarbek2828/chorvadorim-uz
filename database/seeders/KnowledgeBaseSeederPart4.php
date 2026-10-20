<?php

namespace Database\Seeders;



use Illuminate\Database\Seeder;

use Illuminate\Support\Facades\DB;



class KnowledgeBaseSeederPart4 extends Seeder

{

    public function run(): void

    {

        $entries = [

            ['direction' => 'umumiy', 'category' => 'parvarish', 'topic' => 'Suv iste\'moli va uning sut hosil bo\'lishidagi ahamiyati', 'keywords' => 'suv, DMI, laktatsiya, harorat, ehtiyoj', 'content' => "Quruq modda iste'moli va sut ishlab chiqarish bevosita toza suv iste'moliga bog'liq. Sigir har 1 kg quruq modda uchun 4-5 litr va har 1 litr sut uchun qo'shimcha suv ichishi kerak. Atrof-muhit harorati oshishi bilan suvga bo'lgan ehtiyoj keskin ortadi."],

            ['direction' => 'sut', 'category' => 'ozuqa_ratsion', 'topic' => 'Tana vaznini yo\'qotish (mobilizatsiya) va energiya zaxirasi', 'keywords' => 'vazn, mobilizatsiya, energiya, NEL', 'content' => "Laktatsiyaning dastlabki davrida sigir tana yog'larini eritib energiya oladi. 1 kg tana vazni yo'qotilishi taxminan 4.92 Mcal NEL beradi. Tana zaxiralaridan energiya olish 82% samaradorlik bilan kechadi."],

            ['direction' => 'nasl', 'category' => 'ozuqa_ratsion', 'topic' => 'Yosh sigirlar (g\'unajinlar) uchun o\'sish energiyasi qo\'shimchasi', 'keywords' => 'o\'sish, g\'unajin, energiya, laktatsiya', 'content' => "Birinchi laktatsiyadagi yosh sigirlar hali to'liq tana hajmiga yetmagan. Birinchi laktatsiyada 20%, ikkinchi laktatsiyada 10% qo'shimcha o'sish energiyasi ehtiyoji hisobga olinishi shart."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Cheklovchi aminokislotalar: Lizin va Metionin', 'keywords' => 'aminokislota, lizin, metionin, oqsil, MP', 'content' => "Sut oqsili sintezi uchun eng muhim cheklovchi aminokislotalar lizin va metionin. Metabolik oqsil tarkibida lizin 7.2% va metionin 2.4% (nisbat 3:1) bo'lishi sut oqsili hosildorligini maksimal darajaga chiqaradi."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Ratsionda yog\' (Lipid) miqdorining ruxsat etilgan chegaralari', 'keywords' => 'yog\', lipid, kletchatka, rumen', 'content' => "Ratsiondagi umumiy yog' miqdori quruq moddaning 6-7% idan oshmasligi kerak. Ortiqcha yog' qorin mikroflorasi faoliyatini susaytiradi, tolaning hazm bo'lishini pasaytiradi."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Oqsil bo\'lmagan azotdan (NPN) samarali foydalanish', 'keywords' => 'NPN, mochevina, azot, energiya', 'content' => "Mochevina kabi NPN manbalari qorinda tez ammiakka aylanadi. Bundan samarali foydalanish uchun ratsionda tez parchalanuvchi uglevodlar yetarli bo'lishi shart. NPN ratsiondagi umumiy oqsilning 30% idan oshmasligi tavsiya etiladi."],

            ['direction' => 'sut', 'category' => 'parvarish', 'topic' => 'Issiqlik stresi va uning energiyaga ta\'siri', 'keywords' => 'issiqlik stresi, THI, energiya, DMI', 'content' => "Yuqori THI ko'rsatkichida sigirlar nafas olishni tezlashtiradi, bu yashash uchun energiya sarfini 20-30% gacha oshiradi. Shu bilan birga quruq modda iste'moli keskin pasayadi."],

            ['direction' => 'umumiy', 'category' => 'parvarish', 'topic' => 'Sovuq stresi holatida energiya balansini ushlash', 'keywords' => 'sovuq, harorat, maintenance, energiya', 'content' => "Harorat sigirning kritik haroratidan tushib ketganda, tana haroratini saqlash uchun qo'shimcha energiya talab etiladi. Buni ozuqaning energiya zichligini oshirish yoki DMI ni ko'paytirish orqali qoplash mumkin."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'RDP va RUP oqsil balansining qorin mikroflorasi uchun ahamiyati', 'keywords' => 'RDP, RUP, oqsil, mikroflora', 'content' => "Qorin mikroflorasi to'g'ri ishlashi uchun ratsionda RDP yetarli bo'lishi shart. Yuqori mahsuldor sigirlar uchun RUP ham muvozanatda berilishi kerak."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Tolasiz uglevodlar (NFC) va ularning me\'yorlari', 'keywords' => 'NFC, uglevod, kraxmal, qand, atsidoz', 'content' => "NFC rumen mikroblari uchun tezkor energiya manbai. Sog'in sigirlar ratsionida NFC miqdori 35-40% atrofida bo'lishi tavsiya etiladi. 40% dan oshsa atsidoz xavfi ortadi."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'O\'tish davrida (Transition period) quruq modda iste\'molining pasayishi', 'keywords' => 'o\'tish davri, DMI, tug\'ish, tranzit', 'content' => "Tuqqanga qadar so'nggi 3 hafta ichida DMI tabiiy ravishda 30% gacha pasayadi. Tranzit davri ratsioni energiya va oqsillarga boy, lekin yuqori sifatli va hazm bo'ladigan bo'lishi muhim."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Donli ekinlarda kraxmalning parchalanish tezligi', 'keywords' => 'kraxmal, don, makkajo\'xori, arpa, fermentatsiya', 'content' => "Arpa va bug'doy kraxmali makkajo'xori yoki jo'xori kraxmaliga nisbatan tezroq parchalanadi. Atsidozning oldini olish uchun tez va sekin parchalanuvchi kraxmal manbalarini mutanosib berish kerak."],

            ['direction' => 'sut', 'category' => 'parvarish', 'topic' => 'Tana holati indeksi (BCS) va reproduktiv salomatlik', 'keywords' => 'BCS, tana holati, semizlik, urug\'lanish', 'content' => "Tuqish vaqtida optimal BCS 3.25-3.5 bo'lishi tavsiya etiladi. Agar BCS tuqishdan keyin keskin tushib ketsa, qayta urug'lanish ehtimoli pasayadi va tuxumdon faoliyati sekinlashadi."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Ozuqa energiyasining taqsimlanish kaskadi (GE dan NEL gacha)', 'keywords' => 'GE, DE, ME, NEL, energiya', 'content' => "Ozuqadagi umumiy energiya (GE) axlat, siydik va issiqlik yo'qotishlari orqali kamayib boradi: GE -> DE -> ME -> NEL. Faqat NEL sut va yashash uchun sarflanadi."],

            ['direction' => 'sut', 'category' => 'ozuqa_ratsion', 'topic' => 'Sut tarkibi o\'zgarishining energiya talabiga ta\'siri', 'keywords' => 'sut yog\'i, oqsil, FCM, energiya', 'content' => "Bir litr sut ishlab chiqarish energiyasi sutning yog' va oqsil foiziga bog'liq. Barcha hisob-kitoblar 4% yog'ga to'g'rilangan sut (FCM) asosida amalga oshiriladi."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Metabolik axlat azoti (MFN) yo\'qotishlari', 'keywords' => 'MFN, azot, axlat, oqsil', 'content' => "Ichak devorlaridan ko'chgan hujayralar va mikrob qoldiqlari hisobiga oqsil yo'qotiladi (MFN). Sigir qancha ko'p ozuqa yesa, bu yo'qotish ham ortadi."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'NDF va ADF o\'rtasidagi farq hamda ularning ahamiyati', 'keywords' => 'NDF, ADF, kletchatka, hazmdorlik', 'content' => "NDF ozuqaning umumiy kletchatka hajmini ko'rsatadi va ishtaha bilan teskari proporsional. ADF esa hazm bo'lish darajasini belgilaydi - qancha yuqori bo'lsa, shuncha yomon hazm bo'ladi."],

            ['direction' => 'sut', 'category' => 'ozuqa_ratsion', 'topic' => 'Qorinda parchalanmaydigan oqsil (RUP) manbalari va ularning sifati', 'keywords' => 'RUP, bypass oqsil, qon uni, baliq uni', 'content' => "Qon uni, baliq uni va ishlov berilgan soya shroti yuqori sifatli RUP manbalari. RUP tanlashda uning cheklovchi aminokislotalar (lizin, metionin) profili ham hisobga olinishi muhim."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Xom yog\' (Ether Extract) va haqiqiy yog\' o\'rtasidagi tafovut', 'keywords' => 'xom yog\', efir ekstrakti, lipid, energiya', 'content' => "Xom yog' ko'rsatkichi triglitseridlardan tashqari mum va pigmentlarni ham oladi. Ozuqaning aniq energiya qiymatini hisoblashda haqiqiy yog' kislotalari miqdoriga asoslanish to'g'ri."],

            ['direction' => 'sut', 'category' => 'parvarish', 'topic' => 'Sut cho\'qqisi va quruq modda iste\'moli (DMI) o\'rtasidagi vaqt farqi', 'keywords' => 'sut cho\'qqisi, laktatsiya, DMI, ozish', 'content' => "Maksimal sut berish 4-8 haftalarda, DMI cho'qqisi esa 10-14 haftalarda kuzatiladi. Bu 4-6 haftalik kechikish davrida sigir salbiy energiya balansida bo'ladi."],

        ];



        foreach ($entries as $entry) {

            DB::table('knowledge_base')->insert(array_merge($entry, [

                'created_at' => now(),

                'updated_at' => now(),

            ]));

        }

    }

}

