<?php

namespace Database\Seeders;



use Illuminate\Database\Seeder;

use Illuminate\Support\Facades\DB;



class KnowledgeBaseSeederPart8 extends Seeder

{

    public function run(): void

    {

        $entries = [

            ['direction' => 'nasl', 'category' => 'ozuqa_ratsion', 'topic' => 'G\'unajin balog\'atdan oldingi kunlik o\'sish (ADG) chegarasi', 'keywords' => 'g\'unajin, ADG, o\'sish, elin, yog\' to\'qimasi', 'content' => "Golshteyn g'unajinlar balog'atga yetishidan oldin (3-9 oylik) o'rtacha kunlik vazn qo'shishi 800-900 grammdan oshmasligi kerak. Agar 1 kg dan oshsa, elinda sut bezlari o'rnini yog' to'qimalari egallaydi va umrbod mahsuldorlik yo'qoladi."],

            ['direction' => 'umumiy', 'category' => 'kasallik', 'topic' => 'NPN va o\'tkir ammiak zaharlanishi', 'keywords' => 'NPN, karbamid, ammiak, jigar, zaharlanish', 'content' => "NPN me'yordan ortiq berilsa yoki energiya yetishmasa, qorinda ammiak shiddat bilan ajraladi. Jigar buni zararsizlantirishga ulgurmasa, markaziy asab tizimiga ta'sir qiluvchi o'lim xavfli o'tkir zaharlanish yuzaga keladi."],

            ['direction' => 'umumiy', 'category' => 'reproduksiya', 'topic' => 'Marganes tanqisligi va Yashirin kuyikish (Silent heat)', 'keywords' => 'marganes, tuxumdon, kuyikish, estrus, gormon', 'content' => "Marganes tuxumdonlarda jinsiy gormonlar (progesteron, estrogen) sintezi uchun zarur. Marganes tanqisligi yashirin kuyikish (silent heat) va sariq tana funksiyasi susayishiga olib kelib, qochirish samaradorligini tushiradi."],

            ['direction' => 'umumiy', 'category' => 'kasallik', 'topic' => 'Ichimlik suvidagi nitratlar va metgemoglobinemiya', 'keywords' => 'suv, nitrat, nitrit, gemoglobin, zaharlanish', 'content' => "Yuqori nitratli suv qorinda zaharli nitritlarga aylanadi. Nitritlar gemoglobinni metgemoglobinga aylantirib, qonning kislorod tashish qobiliyatini yo'qotadi. Bu homila tashlash va nafas qisishiga sabab bo'ladi."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'NDF hazm bo\'lish koeffitsiyenti (NDFd) va mahsuldorlik', 'keywords' => 'NDFd, kletchatka, hazm bo\'lish, DMI, xashak sifati', 'content' => "NDFd ko'rsatkichi 1% oshganda, sigirning DMI 0.17 kg ga, sut ishlab chiqarishi 0.25 kg ga ortadi. Bu xashak sifatining iqtisodiy ahamiyatini ko'rsatadi."],

            ['direction' => 'umumiy', 'category' => 'parvarish', 'topic' => 'Temir (Fe) tanqisligi va buzoqlar anemiyasi', 'keywords' => 'temir, anemiya, gemoglobin, buzoq, sut', 'content' => "Sigir sutida temir miqdori tabiiy ravishda past. Uzoq vaqt faqat sut bilan boqilgan buzoqlarda temir tanqisligi (anemiya) yuzaga keladi, bu o'sish tezligini sekinlashtirib, kasalliklarga chidamlilikni pasaytiradi."],

            ['direction' => 'umumiy', 'category' => 'parvarish', 'topic' => 'Suv oxurlari maydoni va ijtimoiy raqobat', 'keywords' => 'suv, oxur, maydon, ierarxiya, raqobat', 'content' => "Barcha sigirlar suvga bemalol yetishi uchun suv oxuri uzunligi har bir sigirga kamida 10-12 sm bo'lishi kerak. Bitta guruhda kamida ikkita alohida suv manbasi bo'lishi shart."],

            ['direction' => 'parvarish', 'category' => 'kasallik', 'topic' => 'Far-off davridagi yashirin ichki semizlik (Visceral fat)', 'keywords' => 'far-off, quritish, visceral fat, ichki semizlik, ketoz', 'content' => "Far-off davrida yuqori energiyali ozuqa (ko'p makkajo'xori silosi) tashqi tomondan sezilmasa-da, qorin bo'shlig'ida ichki yog' (visceral fat) to'planishiga olib keladi. Bu tuqishdan keyingi jigar xastaliklari va ketozning asosiy sababi."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'DCAD va tuqish falaji (aniq mEq/kg ko\'rsatkichi)', 'keywords' => 'DCAD, kation, anion, gipokalsemiya, mEq', 'content' => "Close-up davrida DCAD -100 dan -150 mEq/kg gacha bo'lishi kerak. Xlorid va sulfat qo'shish qonni kislotalashtirib, paratgormon sezgirligini kuchaytiradi va tuqish falaji xavfini yo'q qiladi."],

            ['direction' => 'sut', 'category' => 'kasallik', 'topic' => 'SARA va sut yog\'i/oqsil inversiyasi', 'keywords' => 'SARA, atsidoz, sut yog\'i, inversiya, pH', 'content' => "Qorin pH ning 5.5-5.8 oralig'ida uzoq tushib qolishi SARA deyiladi. Eng aniq belgisi - sut yog'i foizining sut oqsili foizidan pastlab ketishi (inversiya). Sababi - ko'p konsentrat va peNDF yetishmasligi."],

            ['direction' => 'umumiy', 'category' => 'parvarish', 'topic' => 'Ozuqani saralash (Sorting behavior) xavfi', 'keywords' => 'sorting, saralash, TMR, zarracha uzunligi, atsidoz', 'content' => "TMR tarkibidagi xashak juda uzun kesilgan bo'lsa, sigirlar uni chetga surib, mayda konsentratlarni terib yeydi (sorting). Bu qorinda kletchatka-kraxmal muvozanatini buzib, atsidozga olib keladi."],

            ['direction' => 'umumiy', 'category' => 'kasallik', 'topic' => 'Biotin (B7) va tuyoq shoxi salomatligi', 'keywords' => 'biotin, tuyoq, oq chiziq, oqsoqlik, keratin', 'content' => "Yuqori mahsuldor sigirlarga kunda 20 mg qo'shimcha Biotin berish tuyoq muammolarini kamaytiradi. Biotin keratin hosil bo'lishini tezlashtirib, oq chiziq kasalligi va oqsoqlanish xavfini bartaraf etadi."],

            ['direction' => 'umumiy', 'category' => 'parvarish', 'topic' => 'Ozuqa surish (Feed pushing) chastotasi', 'keywords' => 'ozuqa surish, DMI, refleks, pH barqarorligi', 'content' => "Ozuqani kuniga 6-8 marta sigirlar oldiga surib turish DMI ni oshiradi va ovqatlanish refleksini uyg'otadi. Tez-tez oziqlanish qorin fermentatsiyasini muvozanatlab, atsidozga to'sqinlik qiladi."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Xamirturush (Yeast) probiotiklari va anaerob muhit', 'keywords' => 'xamirturush, yeast, anaerob, kislorod, laktat', 'content' => "Saccharomyces cerevisiae qorinda ko'paymaydi, lekin zararli kislorodni yutib yo'q qiladi. Bu laktat iste'mol qiluvchi bakteriyalar uchun qulay sharoit yaratib, pH ni ko'taradi va SARA oldini oladi."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'RDP miqdorining aniq foiz chegarasi', 'keywords' => 'RDP, qorin oqsili, ammiak, chegarasi', 'content' => "Ratsiondagi umumiy oqsilning 60-65% idan ko'pi RDP bo'lmasligi shart. Bu chegaradan oshsa qorinda ortiqcha ammiak hosil bo'lib, uni chiqarish katta energiya talab qiladi va reproduktiv funksiyaga zarar yetkazadi."],

            ['direction' => 'sut', 'category' => 'kasallik', 'topic' => 'Niasin (B3) aniq dozasi va yog\' mobilizatsiyasi', 'keywords' => 'niasin, vitamin B3, doza, ketoz, NEFA', 'content' => "Transition davrida sigirlarga kuniga 6-12 gramm Niasin berish antilipolitik ta'sir ko'rsatadi, tana yog'larining tez ajralishini sekinlashtiradi va ketoz xavfini kamaytiradi."],

            ['direction' => 'sut', 'category' => 'ozuqa_ratsion', 'topic' => 'NEL samaradorligi va sut ishlab chiqarish mexanizmi', 'keywords' => 'NEL, energiya, samaradorlik, laktatsiya', 'content' => "Hazm bo'lgan energiyani sutga aylantirish samaradorligi (NEL) o'rtacha 64% ni tashkil qiladi. Sut ishlab chiqarish o'sish yoki semirishga nisbatan ancha tejamkor biologik mexanizm."],

        ];



        foreach ($entries as $entry) {

            DB::table('knowledge_base')->insert(array_merge($entry, [

                'created_at' => now(),

                'updated_at' => now(),

            ]));

        }

    }

}

