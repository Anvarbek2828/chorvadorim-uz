<?php

namespace Database\Seeders;



use Illuminate\Database\Seeder;

use Illuminate\Support\Facades\DB;



class KnowledgeBaseSeederPart6 extends Seeder

{

    public function run(): void

    {

        $entries = [

            ['direction' => 'umumiy', 'category' => 'kasallik', 'topic' => 'Selen va E vitamini sinergiyasi (tafsilotli)', 'keywords' => 'selen, E vitamini, antioksidant, oq mushak kasalligi, yo\'ldosh', 'content' => "Selen va E vitamini hujayra membranalarini erkin radikallardan himoya qilishda birgalikda ishlaydi. Selen yetishmovchiligi ko'pincha oq mushak kasalligiga va yo'ldoshning ushlanib qolishiga olib keladi."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Mis-Molibden-Oltingugurt antagonizmi (tafsilotli)', 'keywords' => 'mis, molibden, oltingugurt, tiomolibdat, jun rangi', 'content' => "Molibden va oltingugurt yuqori bo'lsa, qorinda tiomolibdatlar hosil bo'lib, mis bilan kuchli bog'lanadi. Natijada ozuqada mis yetarli bo'lsa-da, jun rangining o'zgarishi va immunitet pasayishi kuzatiladi."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Kobalt va Vitamin B12 sintezi (tafsilotli)', 'keywords' => 'kobalt, vitamin B12, kobalamin, propionat, ozish', 'content' => "Kobaltning yagona funksiyasi qorin mikroorganizmlari tomonidan B12 vitamini sintezida ishtirok etishidir. Kobalt yetishmasa, propionat glyukozaga aylana olmaydi va energiya yetishmovchiligi kuzatiladi."],

            ['direction' => 'umumiy', 'category' => 'parvarish', 'topic' => 'Rux va tuyoq salomatligi (tafsilotli)', 'keywords' => 'rux, keratin, tuyoq, xelat, yorilish', 'content' => "Rux teri, jun va tuyoq keratin sintezida asosiy rol o'ynaydi. Rux yetishmovchiligi tuyoqning yumshashi, yorilishi va infeksiyalarga chalinishini oshiradi."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Yod ehtiyoji va bo\'qoq bezlari (tafsilotli)', 'keywords' => 'yod, qalqonsimon bez, bo\'qoq, goitrogen, karam', 'content' => "Yod qalqonsimon bez gormonlari tarkibiga kirib, metabolizm tezligini boshqaradi. Yod tanqisligi buzoqlarning o'lik tug'ilishi va bo'qoq kasalligiga sabab bo'ladi."],

            ['direction' => 'umumiy', 'category' => 'parvarish', 'topic' => 'Og\'iz suti va passiv immunitet (tafsilotli)', 'keywords' => 'og\'iz suti, kolostrum, antitelalar, buzoq, gut closure', 'content' => "Buzoqlar antitelalarsiz tug'iladi. Immunoglobulinlar faqat ilk 24 soat ichida, eng yaxshisi 2-4 soat ichida ichak devoridan butun holicha qonga o'tadi, shundan keyin ichak yopiladi (gut closure)."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Sut o\'rnini bosuvchi ozuqalar sifati (tafsilotli)', 'keywords' => 'milk replacer, buzoq, zardob oqsili, ich ketishi', 'content' => "Yosh buzoqlar o'simlik oqsilini hazm qilish fermentlariga ega emas. Sut o'rnini bosuvchi ozuqa tarkibi zardob oqsili va yog'siz quruq sutdan iborat bo'lishi kerak."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Starter ozuqa va rumen rivojlanishi (tafsilotli)', 'keywords' => 'starter ozuqa, rumen, papillae, butirat, propionat', 'content' => "Buzoqlarda qorin so'rg'ichlari faqat donli starter ozuqa fermentatsiyasidan hosil bo'ladigan butirat va propionat ta'sirida rivojlanadi. Suv va starter ozuqa doimiy oldida bo'lishi kerak."],

            ['direction' => 'sut', 'category' => 'kasallik', 'topic' => 'Sut yog\'ining pasayishi (MFD) va CLA nazariyasi', 'keywords' => 'sut yog\'i, MFD, CLA, biohidrogenizatsiya, PUFA', 'content' => "Ko'p to'yinmagan yog' va konsentrat berilishi biohidrogenizatsiyani o'zgartirib, trans-10 cis-12 CLA hosil qiladi. Bu modda elinga yetib borib, sut yog'i sintezini bloklaydi. Qorin pH ni tushirib yubormaslik shart."],

            ['direction' => 'umumiy', 'category' => 'parvarish', 'topic' => 'BCS va laktatsiya sikli dinamikasi (tafsilotli)', 'keywords' => 'BCS, tana holati, laktatsiya, urug\'lantirish', 'content' => "Ideal BCS tuqish vaqtida 3.25-3.5 atrofida. Tuqqandan keyin vazn yo'qotish 0.5-1.0 balldan oshmasligi kerak, aks holda tuxumdonlar faoliyati sekinlashadi."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Oqsil fraksiyalari A, B, C (tafsilotli)', 'keywords' => 'oqsil fraksiyalari, NPN, lignin, Maillard reaksiyasi', 'content' => "Oqsil qorinda 3 fraksiyaga bo'linadi: A (NPN, tez eruvchi), B (asta parchalanadigan), C (hazm bo'lmaydigan, lignin bilan bog'langan). Ortiqcha qizdirish C fraksiyani oshirib, oqsil qiymatini yo'qotadi."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'NFC: Kraxmal va Pektin farqi', 'keywords' => 'NFC, kraxmal, pektin, qand lavlagi, atsidoz', 'content' => "Kraxmal qorinda tez parchalanib sut kislotasi hosil qiladi, atsidoz xavfini oshiradi. Pektin (qand lavlagi jomi) tez parchalanib energiya bersa-da, sut kislotasi emas, asetat-propionat hosil qiladi - bu ancha xavfsiz."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Fitin fosfori va fitaza fermenti', 'keywords' => 'fosfor, fitin, fitaza, donli ekinlar, o\'zlashtirish', 'content' => "Donli ekinlardagi fosforning katta qismi fitin kislotasi shaklida. Kavsh qaytaruvchilarning qorin mikroflorasi fitaza fermenti ishlab chiqarib, bu fosforni deyarli 100% o'zlashtirishga imkon beradi."],

            ['direction' => 'umumiy', 'category' => 'parvarish', 'topic' => 'DMI ni cheklovchi jismoniy va metabolik omillar', 'keywords' => 'DMI, qorin sig\'imi, propionat, to\'qlik', 'content' => "Past energiyali ratsionlarda DMI ni qorinning jismoniy to'lishi cheklaydi. Yuqori energiyali ratsionlarda esa jigardagi propionat signali (metabolik to'qlik) iste'molni to'xtatadi."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Azot va fosforning ortiqcha berilishi va ekologik bosim', 'keywords' => 'azot, fosfor, ekologiya, go\'ng, iqtisodiy zarar', 'content' => "Ratsionda azot va fosforni NRC talablaridan ortiqcha berish mahsuldorlikni oshirmaydi, faqat go'ng va siydik orqali tashqariga chiqib, tuproq va suv ifloslanishiga sabab bo'ladi."],

            ['direction' => 'umumiy', 'category' => 'parvarish', 'topic' => 'Yaylovda saqlanuvchi sigirlarning qo\'shimcha energiya sarfi', 'keywords' => 'yaylov, energiya, harakat, maintenance', 'content' => "Yaylovda o'tlab yuruvchi sigirlar harakatlanish uchun ko'proq energiya sarflaydi. Yaylov relyefiga qarab maintenance energiya ehtiyoji 10-20% gacha ortishi mumkin."],

            ['direction' => 'sut', 'category' => 'kasallik', 'topic' => 'Shirdon siljishi (Displaced Abomasum) mexanizmi', 'keywords' => 'shirdon, abomasum, siljish, peNDF, tug\'ruq', 'content' => "Tuqqandan so'ng qorinda bo'sh joy paydo bo'lishi va peNDF yetishmasligi shirdon siljishining asosiy sababi. Tola kamligi qorin motorikasini susaytirib, shirdon gaz bilan to'lib siljib ketadi."],

            ['direction' => 'sut', 'category' => 'kasallik', 'topic' => 'Ketoz va qondagi keton tanachalari (BHBA)', 'keywords' => 'ketoz, keton, BHBA, jigar, glyukoza', 'content' => "Erta laktatsiyada energiya yetishmasa, tana yog'lari qonga chiqadi. Jigar ularni to'liq glyukozaga aylantira olmasa, BHBA kabi keton tanachalari to'planib, ketoz kasalligi yuzaga keladi."],

            ['direction' => 'nasl', 'category' => 'parvarish', 'topic' => 'G\'unajinlar o\'sish ko\'rsatkichlari va qochirish balog\'ati', 'keywords' => 'g\'unajin, o\'sish, vazn, qochirish, elin', 'content' => "G'unajinlarni birinchi qochirish yetuk sigir vaznining 55-60% iga yetganda amalga oshirilishi kerak. Balog'atdan oldin ozuqa energiyasini oshirib yuborish elinda yog' to'qimasi rivojlanishiga olib keladi."],

        ];



        foreach ($entries as $entry) {

            DB::table('knowledge_base')->insert(array_merge($entry, [

                'created_at' => now(),

                'updated_at' => now(),

            ]));

        }

    }

}

