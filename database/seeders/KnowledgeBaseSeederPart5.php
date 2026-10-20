<?php

namespace Database\Seeders;



use Illuminate\Database\Seeder;

use Illuminate\Support\Facades\DB;



class KnowledgeBaseSeederPart5 extends Seeder

{

    public function run(): void

    {

        $entries = [

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'RUP va RDP oqsil balansi (qorin mikroflorasi uchun)', 'keywords' => 'RDP, RUP, oqsil, mikroflora, ammiak, hazm', 'content' => "Qorin mikroflorasi to'g'ri ishlashi va tolali ozuqalarni parchalashi uchun ratsionda RDP yetarli bo'lishi shart. Yuqori mahsuldor sigirlar uchun RUP ham muvozanatda berilishi kerak."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'NFC va uglevod balansi (ikkinchi ko\'rib chiqish)', 'keywords' => 'NFC, uglevod, kraxmal, pektin, atsidoz', 'content' => "Tolasiz uglevodlar (NFC) asosan kraxmal, qand va pektindan iborat, rumen mikroblari uchun tezkor energiya manbai. NFC miqdori 35-40% atrofida bo'lishi tavsiya etiladi, 40% dan oshsa atsidoz xavfi ortadi."],

            ['direction' => 'umumiy', 'category' => 'parvarish', 'topic' => 'O\'tish davri (tranzit) va DMI pasayishi', 'keywords' => 'o\'tish davri, DMI, tug\'ish, tranzit, ishtaha', 'content' => "Tuqqanga qadar so'nggi 3 hafta ichida DMI 30% gacha pasayadi. Tranzit davri ratsioni energiya va oqsillarga boy, lekin qorinni to'ldirib yubormaydigan, yuqori sifatli bo'lishi muhim."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Donli ekinlar kraxmalining parchalanish tezligi (ikkinchi ko\'rib chiqish)', 'keywords' => 'kraxmal, don, makkajo\'xori, arpa, bug\'doy', 'content' => "Arpa va bug'doy kraxmali makkajo'xori yoki jo'xori kraxmaliga nisbatan tezroq parchalanadi. Atsidoz oldini olish uchun tez va sekin parchalanuvchi manbalarni mutanosib berish tavsiya etiladi."],

            ['direction' => 'sut', 'category' => 'parvarish', 'topic' => 'BCS va reproduktiv salomatlik (ikkinchi ko\'rib chiqish)', 'keywords' => 'BCS, tana holati, semizlik, urug\'lanish', 'content' => "Tuqish vaqtida optimal BCS 3.25-3.5 bo'lishi tavsiya etiladi. BCS tuqishdan keyin keskin tushib ketsa, qayta urug'lanish ehtimoli pasayadi va tuxumdon faoliyati sekinlashadi."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Energiya taqsimlanish kaskadi (GE-DE-ME-NEL)', 'keywords' => 'GE, DE, ME, NEL, energiya, siydik, axlat', 'content' => "Ozuqadagi umumiy energiya (GE) axlat, siydik va gazlar orqali yo'qotilib boradi: GE -> DE -> ME -> NEL. Faqat NEL sut ishlab chiqarish va yashash uchun sarflanadi."],

            ['direction' => 'sut', 'category' => 'ozuqa_ratsion', 'topic' => 'Sut tarkibi va FCM (4 foizli yogga tografirlangan sut)', 'keywords' => 'sut yogi, FCM, energiya, laktatsiya', 'content' => "Sut ishlab chiqarish energiyasi yog' va oqsil foiziga bog'liq. Barcha standart hisob-kitoblar 4 foizli FCM asosida amalga oshiriladi."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'NDF va ADF farqi (uchinchi ko\'rib chiqish)', 'keywords' => 'NDF, ADF, kletchatka, hazmdorlik, ishtaha', 'content' => "NDF ozuqaning umumiy kletchatka hajmini ko'rsatadi va ishtaha bilan teskari proporsional. ADF esa hazm bo'lish darajasini belgilaydi."],

            ['direction' => 'sut', 'category' => 'parvarish', 'topic' => 'Sut cho\'qqisi va DMI vaqt farqi (ikkinchi ko\'rib chiqish)', 'keywords' => 'sut cho\'qqisi, DMI, laktatsiya, energiya balansi', 'content' => "Maksimal sut berish 4-8 haftalarda, DMI cho'qqisi 10-14 haftalarda kuzatiladi. Bu kechikish davrida sigir salbiy energiya balansida bo'ladi."],

            ['direction' => 'umumiy', 'category' => 'kasallik', 'topic' => 'Lignin va ozuqa hazmdorligiga salbiy ta\'siri', 'keywords' => 'lignin, tola, hazmdorlik, o\'simlik pishishi', 'content' => "Lignin o'simlik hujayra devorini qattiqlashtiradi va qorin mikroblari tomonidan hazm qilinmaydi. O'simlik qanchalik qarib pishsa, lignin shunchalik ko'payadi va ozuqaviy qiymat pasayadi."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Ionoforlar (Monensin) va rumen fermentatsiyasi', 'keywords' => 'ionofor, monensin, propionat, metan, energiya', 'content' => "Monensin qorin mikroflora tarkibini o'zgartirib, propionat ishlab chiqarishni ko'paytiradi. Propionat jigar uchun glyukoza sintezida asosiy xomashyo, bu energiya samaradorligini oshiradi va metan yo'qotishlarini kamaytiradi."],

            ['direction' => 'nasl', 'category' => 'parvarish', 'topic' => 'Bo\'g\'ozlik davridagi DMI chegarasi', 'keywords' => 'bo\'g\'ozlik, DMI, semirish, tana holati', 'content' => "Faqat bo'g'ozlik davridagi sigirlarning DMI tana vaznining taxminan 2.0% iga teng. Ortiqcha ozuqa yeb yuborishga yo'l qo'ymaslik kerak - bu yog'li jigar sindromi va ketozga zamin yaratadi."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Kul (Ash) miqdori va organik moddalar', 'keywords' => 'kul, ash, mineral, organik modda, tuproq', 'content' => "Kul - to'liq yondirilgandan keyin qoladigan anorganik qoldiq, energiya bermaydi. Silos yoki pichanda kul 10-12% dan yuqori bo'lsa, bu tuproq aralashganini ko'rsatadi va energiya zichligini pasaytiradi."],

            ['direction' => 'umumiy', 'category' => 'kasallik', 'topic' => 'Selen va E vitamini sinergiyasi (ikkinchi ko\'rib chiqish)', 'keywords' => 'selen, E vitamini, antioksidant, oq mushak kasalligi', 'content' => "Selen va E vitamini hujayra membranalarini oksidlanishdan himoya qiladi. Selen yetishmovchiligi oq mushak kasalligi va yo'ldoshning ushlanib qolishiga olib keladi."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Mis so\'rilishida Molibden va Oltingugurt antagonizmi (ikkinchi)', 'keywords' => 'mis, molibden, oltingugurt, tiomolibdat', 'content' => "Molibden va oltingugurt yuqori bo'lsa, tiomolibdatlar hosil bo'lib, mis so'rilishini to'xtatadi. Natijada ozuqada mis yetarli bo'lsa-da, hayvonda mis tanqisligi kelib chiqadi."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Kobalt va Vitamin B12 sintezi', 'keywords' => 'kobalt, vitamin B12, propionat, energiya', 'content' => "Kobalt qorin mikroorganizmlari tomonidan B12 vitamini sintezi uchun zarur. Kobalt yetishmasa, propionat glyukozaga aylana olmaydi va energiya yetishmovchiligi (ozish) kuzatiladi."],

            ['direction' => 'umumiy', 'category' => 'parvarish', 'topic' => 'Rux va tuyoq salomatligi', 'keywords' => 'rux, keratin, tuyoq, teri, immunitet', 'content' => "Rux teri, jun va tuyoq keratin sintezida asosiy rol o'ynaydi. Rux yetishmovchiligi tuyoqning yumshashi va infeksiyalarga chalinish xavfini oshiradi."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Yod ehtiyoji va qalqonsimon bez faoliyati', 'keywords' => 'yod, qalqonsimon bez, bo\'qoq, metabolizm', 'content' => "Yod qalqonsimon bez gormonlari tarkibiga kiradi va metabolizm tezligini boshqaradi. Yod tanqisligi buzoqlarning o'lik tug'ilishiga va bo'qoq kasalligiga sabab bo'ladi."],

            ['direction' => 'umumiy', 'category' => 'parvarish', 'topic' => 'Og\'iz suti (Kolostrum) va passiv immunitet (ikkinchi ko\'rib chiqish)', 'keywords' => 'og\'iz suti, kolostrum, antitelalar, buzoq, immunitet', 'content' => "Yangi tug'ilgan buzoq immunitetni faqat onasining og'iz sutidan oladi. Tug'ilgandan keyingi 2-4 soat ichida tana vaznining 10% miqdorida sifatli og'iz suti ichishi shart, 24 soatdan keyin ichak antitelalarni o'tkaza olmay qoladi."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Buzoqlar uchun sut o\'rnini bosuvchi ozuqalar sifati', 'keywords' => 'milk replacer, buzoq, oqsil, zardob, ich ketishi', 'content' => "Yosh buzoqlar o'simlik oqsilini hazm qilish fermentlariga ega emas. Sut o'rnini bosuvchi ozuqa tarkibi asosan sut mahsulotlaridan (zardob oqsili) iborat bo'lishi kerak, aks holda ich ketishi yuzaga keladi."],

        ];



        foreach ($entries as $entry) {

            DB::table('knowledge_base')->insert(array_merge($entry, [

                'created_at' => now(),

                'updated_at' => now(),

            ]));

        }

    }

}

