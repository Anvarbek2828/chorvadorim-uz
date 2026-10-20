<?php

namespace Database\Seeders;



use Illuminate\Database\Seeder;

use Illuminate\Support\Facades\DB;



class KnowledgeBaseSeederPart9 extends Seeder

{

    public function run(): void

    {

        $entries = [

            ['direction' => 'parvarish', 'category' => 'ozuqa_ratsion', 'topic' => 'Close-up davrida MP (Metabolizable Protein) ehtiyoji', 'keywords' => 'MP, metabolik oqsil, close-up, homila, RUP', 'content' => "Tuqishdan oldingi oxirgi 3 hafta (close-up) homilaning eng tez o'sadigan davri. Sigirning ichakka yetib boruvchi sof oqsilga (MP) ehtiyoji kuniga 1000-1100 grammgacha oshadi. Buni bypass oqsil (RUP) ulushini ko'tarish orqali ta'minlash shart."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Samarali kletchatka (peNDF) va Penn State elaklari', 'keywords' => 'peNDF, Penn State, kavsh qaytarish, zarracha', 'content' => "Ozuqa zarralarining uzunligini Penn State elaklari (PSPS) yordamida o'lchash tavsiya etiladi. Eng yuqori elakda (19 mm) ozuqaning 2-8% qismi qolishi qorin motorikasining sog'lom ishlashi uchun ideal standart."],

            ['direction' => 'sut', 'category' => 'kasallik', 'topic' => 'Propilen glikol yordamida ketozni profilaktika qilish', 'keywords' => 'propilen glikol, ketoz, glyukoza, doza', 'content' => "Ketoz xavfi yuqori sigirlarga kuniga 300-500 gramm propilen glikol ichirish eng tezkor terapevtik usul. U jigarda tez glyukozaga aylanib, insulinni ko'tarib, tana yog'larining ortiqcha yemirilishini to'xtatadi."],

            ['direction' => 'parvarish', 'category' => 'ozuqa_ratsion', 'topic' => 'Uzoq quritish davri (Far-off) uchun hajmdor parhez', 'keywords' => 'far-off, quritish, somon, kletchatka, energiya cheklash', 'content' => "Far-off davrida (tuqishga 60-21 kun) past energiyali, hajmdor (somon kabi) ratsion beriladi. Bu sigirning qornini to'q saqlaydi, lekin energiyani cheklab, semirib ketishning oldini oladi."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Himoyalangan Xolin (RPC) orqali jigarni tozalash', 'keywords' => 'xolin, choline, VLDL, jigar, yog\'li jigar', 'content' => "Xolin jigar hujayralaridagi yog'larni VLDL shakliga o'tkazib, qonga chiqarish uchun eng muhim vosita. O'tish davrida himoyalangan xolin qo'shish jigardagi yog' tugunlarini tozalaydi va yog'li jigar sindromining oldini oladi."],

            ['direction' => 'parvarish', 'category' => 'parvarish', 'topic' => 'Loy va balchiqning DMI ga halokatli ta\'siri', 'keywords' => 'loy, balchiq, DMI, energiya sarfi', 'content' => "Sigir tuyog'i 10-20 sm loyga botganda, yurish uchun energiya ehtiyoji 30-50% oshadi. Loydan qochgan sigirlar oxurga kamroq boradi, DMI 15-30% ga tushadi. Bunda hatto mukammal ratsion ham ishlamaydi."],

            ['direction' => 'umumiy', 'category' => 'parvarish', 'topic' => 'Silos namligi o\'zgarishi va ratsion aniqligi', 'keywords' => 'namlik, quruq modda, silos, ratsion aniqligi', 'content' => "Silos namligi doimiy o'zgarib turadi. Agar namlik tahlili haftasiga 1-2 marta qilinmasa, ratsiondagi haqiqiy quruq modda dasturdagi raqamlardan farq qiladi, sigirlar kerakli oziqani ololmaydi."],

            ['direction' => 'parvarish', 'category' => 'boshqaruv', 'topic' => 'THI (Harorat-namlik indeksi) va issiqlik stresi chegarasi', 'keywords' => 'THI, issiqlik stresi, atsidoz, DMI', 'content' => "THI ko'rsatkichi 68 dan oshganda yuqori laktatsiyadagi sigirlarda issiqlik stresi boshlanadi. Tez nafas olish so'lak orqali buferlarni yo'qotib, DMI pasayadi va atsidoz xavfi ortadi."],

            ['direction' => 'sut', 'category' => 'boshqaruv', 'topic' => 'Fotoperiod (yoritish) va prolaktin stimulyatsiyasi', 'keywords' => 'fotoperiod, yoritish, melatonin, prolaktin, sut hajmi', 'content' => "Sigirlarga kuniga 16-18 soat yorug'lik va 6-8 soat qorong'ulik (LDPP) ta'minlash melatoninni pasaytirib, prolaktin sekretsiyasini oshiradi. Bu sut mahsuldorligini 8-10% (+2.0-2.5 kg) oshiradi."],

            ['direction' => 'umumiy', 'category' => 'kasallik', 'topic' => 'Kaliy-Magniy antagonizmi: O\'t tetaniyasi (Grass Tetany)', 'keywords' => 'kaliy, magniy, gipomagniyemiya, tetaniya, ko\'k o\'t', 'content' => "Erta bahorda yosh ko'k o'tlarda kaliy ko'p bo'ladi, bu magniy so'rilishini bloklaydi. Qonda magniy tushib ketib, gipomagniyemiya (o't tetaniyasi - mushak qisqarishlari, to'satdan o'lim) yuzaga kelishi mumkin."],

            ['direction' => 'parvarish', 'category' => 'kasallik', 'topic' => 'Tuqish arafasida Yelin shishi (Udder Edema)', 'keywords' => 'yelin shishi, natriy, kaliy, close-up, suv ushlanishi', 'content' => "Primiparous va yuqori mahsuldor sigirlarda yelin shishi kuzatilsa, ratsiondagi natriy va kaliy tekshirilishi kerak. Close-up davrida bu elementlar ortiqcha bo'lsa, to'qimalarda suv ushlanib qoladi."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Xelat va noorganik mikroelementlar o\'zlashtirilishi', 'keywords' => 'xelat, organik mineral, noorganik, rux, mis', 'content' => "Noorganik mineral shakllar (sulfat, oksid) qorinda boshqa moddalar bilan birikib so'rilmay chiqib ketishi mumkin. Minerallarning 30-40% qismini xelat (organik) shaklga o'tkazish o'zlashtirishni yaxshilaydi."],

            ['direction' => 'buzoq', 'category' => 'kasallik', 'topic' => 'Og\'iz suti sifati va Brix refraktometri', 'keywords' => 'kolostrum, Brix, IgG, refraktometr, buzoq', 'content' => "Og'iz sutidagi IgG miqdorini Brix refraktometri bilan o'lchash kerak. Brix 22% dan yuqori kolostrum sifatli hisoblanadi va buzoqqa ilk 2-4 soatda tana vaznining 10% miqdorida berilishi shart."],

            ['direction' => 'buzoq', 'category' => 'ozuqa_ratsion', 'topic' => 'Sut normasi va starter o\'rtasidagi biologik ziddiyat', 'keywords' => 'CMR, sun\'iy sut, starter, rumen papillalari', 'content' => "Buzoqqa haddan tashqari ko'p sut (kuniga 8-10 litrdan ortiq) berish starter yeyish istagini so'ndiradi. Natijada qorin papillalari rivojlanmay, sutdan ajratishda buzoq og'ir metabolik stressga tushadi."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Sof energiya samaradorligi va laktatsiya iqtisodiyoti', 'keywords' => 'NEL, samaradorlik, iqtisodiy, energiya', 'content' => "Hazm bo'lgan energiyani sutga aylantirish samaradorligi 64% ni tashkil qiladi - bu boshqa biologik jarayonlardan (o'sish, semirish) ancha tejamkor va samarali mexanizm."],

            ['direction' => 'sut', 'category' => 'ozuqa_ratsion', 'topic' => 'Musbat DCAD va laktatsiya davridagi bufer effekti', 'keywords' => 'DCAD, musbat balans, laktatsiya, natriy, kaliy', 'content' => "Laktatsiya davrida DCAD ko'rsatkichi musbat (+250 dan +400 mEq/kg) bo'lishi kerak. Natriy va Kaliy qo'shish qonning buferlik xususiyatini saqlab, issiqlik stresi vaqtida ham sut hajmini ushlab turadi."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Qorin ammiak (NH3) konsentratsiyasining minimal chegarasi', 'keywords' => 'ammiak, NH3, sellyulolitik bakteriya, mikrob oqsili', 'content' => "Sellyulolitik bakteriyalar faqat ammiakdan azot oladi. Qorin suyuqligida ammiak 5 mg/dL dan tushsa, mikrob oqsili sintezi va kletchatka hazm bo'lishi to'xtaydi, DMI pasayadi."],

        ];



        foreach ($entries as $entry) {

            DB::table('knowledge_base')->insert(array_merge($entry, [

                'created_at' => now(),

                'updated_at' => now(),

            ]));

        }

    }

}

