<?php

namespace Database\Seeders;



use Illuminate\Database\Seeder;

use Illuminate\Support\Facades\DB;



class KnowledgeBaseSeederPart7 extends Seeder

{

    public function run(): void

    {

        $entries = [

            ['direction' => 'umumiy', 'category' => 'kasallik', 'topic' => 'Mis (Cu) zaharlanishi va jigarda to\'planish xavfi', 'keywords' => 'mis, zaharlanish, jigar, toksik, gemolitik krizis', 'content' => "Mis jigarda to'planadi va ortiqcha berilganda toksik ta'sir ko'rsatadi. Uzoq vaqt yuqori dozada mis berilishi qizil qon tanachalarining parchalanishiga (gemolitik krizis) va kutilmagan o'limga olib kelishi mumkin."],

            ['direction' => 'umumiy', 'category' => 'parvarish', 'topic' => 'Buzoqlarni sutdan ajratish (Weaning) mezonlari', 'keywords' => 'sutdan ajratish, weaning, starter ozuqa, qorin', 'content' => "Buzoqni sutdan ajratish uchun asosiy mezon yoshi emas, starter ozuqa iste'mol qilish darajasi. Ketma-ket 3-4 kun kuniga 1.0-1.5 kg starter yeya boshlaganda ajratish xavfsiz."],

            ['direction' => 'umumiy', 'category' => 'reproduksiya', 'topic' => 'Erta laktatsiyada manfiy energiya balansi va tuxumdonlar', 'keywords' => 'energiya balansi, tuxumdon, insulin, servis davri', 'content' => "Chuqur manfiy energiya balansi va ko'p vazn yo'qotish birinchi kuyikishni kechiktiradi. Past glyukoza va insulin tuxumdon follikulalar rivojlanishiga to'sqinlik qiladi, servis davri uzayadi."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Vitamin D va kalsiy gomeostazi', 'keywords' => 'vitamin D, kalsiy, gomeostaz, paratiroid', 'content' => "D vitamini kalsiy-fosfor darajasini muvozanatda ushlab turadi. Qonda kalsiy kamayganda, paratiroid gormoni ta'sirida D vitamini faollashib, ichak va suyaklardan kalsiy ajralib chiqishini kuchaytiradi."],

            ['direction' => 'sut', 'category' => 'ozuqa_ratsion', 'topic' => 'Niasin (Vitamin B3) va jigar himoyasi', 'keywords' => 'niasin, vitamin B3, jigar, yog\'li jigar, ketoz', 'content' => "Erta laktatsiyada qo'shimcha Niasin tana yog'larining tez ajralib chiqishini tormozlaydi. Bu yog'li jigar sindromining oldini oladi va ketoz xavfini kamaytirib, glyukoza hosil bo'lishini yaxshilaydi."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Silos fermentatsiyasi va butirat xavfi', 'keywords' => 'silos, klostridiya, butirat, quruq modda', 'content' => "Silos quruq moddasi 25% dan kam bo'lsa, klostridiya bakteriyalari ko'payib, zaharli butirat hosil qiladi. Bunday silos qo'lansa hidli bo'lib, ishtahani bog'laydi va ketozga moyillikni oshiradi."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Vitamin A (Beta-karotin) va shilliq qavatlar immuniteti', 'keywords' => 'vitamin A, beta-karotin, shilliq qavat, mastit, metrit', 'content' => "A vitamini nafas olish, hazm va ko'payish organlari shilliq qavatlarini saqlaydi. Tanqisligi mastit, metrit va yo'ldosh ushlanib qolish xavfini oshiradi. Uzoq saqlangan pichanda bu vitamin tez parchalanadi."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Qorinda azot (karbamid) recycling mexanizmi', 'keywords' => 'azot, karbamid, recycling, so\'lak, mikrobial oqsil', 'content' => "Kavsh qaytaruvchilar ortiqcha azotni siydik bilan chiqarishdan tashqari, so'lak orqali qayta qoringa qaytarish (recycling) qobiliyatiga ega. Bu ratsionda oqsil kamayganda mikroblarni azot bilan ta'minlaydi."],

            ['direction' => 'sut', 'category' => 'parvarish', 'topic' => 'Anion tuzlarining ta\'mi va DMI ga ta\'siri', 'keywords' => 'anion tuzlar, ta\'m, DMI, tranzit davr', 'content' => "Sut isitmasi oldini olish uchun ishlatiladigan anion tuzlari (ammoniy xlorid) juda bemaza bo'ladi. Agar to'g'ri aralashtirilmasa (masalan shinni bilan yashirilmasa), sigirlar DMI ni kamaytiradi, bu ketoz xavfini oshiradi."],

            ['direction' => 'sut', 'category' => 'kasallik', 'topic' => 'Somatik hujayralar soni (SCC) va immunitetni oziqlantirish', 'keywords' => 'SCC, somatik hujayra, mastit, antioksidant', 'content' => "SCC leykotsitlardan iborat bo'lib, mastitga qarshi kurashadi. Oqsil, energiya va mikroelementlar (A, E, Cu, Zn, Se) bilan to'liq ta'minlash leykotsitlar faolligini oshirib, SCC darajasini past ushlab turadi."],

            ['direction' => 'nasl', 'category' => 'parvarish', 'topic' => 'Primiparous sigirlarni ijtimoiy guruhlash', 'keywords' => 'primiparous, birinchi qorin, ierarxiya, DMI, stress', 'content' => "Birinchi marta tuqqan yosh sigirlarni katta sigirlar bilan bir guruhda saqlash zararli, chunki ular oxurdan surib chiqariladi. Alohida guruhlash DMI ni 10-15% oshirib, stressni kamaytiradi."],

            ['direction' => 'umumiy', 'category' => 'parvarish', 'topic' => 'Quritish davrida (Dry period) BCS ni saqlash', 'keywords' => 'quritish davri, dry cow, BCS, ketoz, yog\'li jigar', 'content' => "Quritish davriga o'tayotgan sigirning BCS tuqish vaqtida ham xuddi shunday (ideal 3.25) saqlanishi shart. 60 kunlik quritish davrida semirib ketish ketoz va yog'li jigar sindromiga yo'l ochadi."],

            ['direction' => 'umumiy', 'category' => 'parvarish', 'topic' => 'Kavsh qaytarish va so\'lakning buferlik xususiyati', 'keywords' => 'kavsh qaytarish, so\'lak, peNDF, bufer, bikarbonat', 'content' => "Sog'lom sigir kuniga 8-10 soat kavsh qaytaradi. Yetarli peNDF bilan kuniga 200-250 litrgacha so'lak ajraladi, bu tabiiy bikarbonat va fosfat buferlari orqali qorin kislotaliligini neytrallaydi."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Ratsion namligi va nam ozuqalarning DMI ga ta\'siri', 'keywords' => 'namlik, DMI, silos, fermentatsiya mahsulotlari', 'content' => "Ratsion namligi 50% dan oshganda DMI pasayadi. Bu suvning ko'pligi emas, balki nam ozuqadagi fermentatsiya mahsulotlari (kislotalar) bilan bog'liq."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Qon zardobi va MUN indikatori (ikkinchi ko\'rib chiqish)', 'keywords' => 'MUN, mochevina, azot, oqsil balansi', 'content' => "MUN oqsil-uglevod balansining ishonchli indikatori. Optimal daraja 10-14 mg/dL. Yuqori bo'lsa, RDP ortiqcha yoki NFC yetishmasligini bildiradi."],

            ['direction' => 'umumiy', 'category' => 'parvarish', 'topic' => 'Ichimlik suvi sifati va mineral almashinuvi', 'keywords' => 'suv sifati, sulfat, xlorid, TDS, so\'rilish', 'content' => "Suvdagi yuqori TDS, ayniqsa sulfat va xlorid darajasi mis, rux, selen kabi mikroelementlarning so'rilishiga to'sqinlik qiladi, ozuqada yetarli bo'lsa-da tanqislik yuzaga keladi."],

            ['direction' => 'umumiy', 'category' => 'kasallik', 'topic' => 'Yashirin qorin atsidozi (SARA) va uning belgilari', 'keywords' => 'SARA, atsidoz, pH, sut yog\'i, laminit', 'content' => "SARA qorin pH ning uzoq vaqt 5.5-5.8 oralig'ida tushib qolishi bilan xarakterlanadi. Klinik belgi ko'rinmasligi mumkin, lekin DMI beqarorlashadi, sut yog'i pasayadi va oylar o'tib laminit ko'payadi."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Qorin buferlari (Natriy bikarbonat) qo\'llash vaqti', 'keywords' => 'bufer, natriy bikarbonat, soda, pH, peNDF', 'content' => "Yuqori konsentratli ratsionlarda natriy bikarbonat qo'shiladi, ayniqsa nam silos ko'p berilganda. Agar peNDF yetarli bo'lsa va sigir tabiiy so'lak ajratsa, sun'iy buferlarga ehtiyoj kamayadi."],

        ];



        foreach ($entries as $entry) {

            DB::table('knowledge_base')->insert(array_merge($entry, [

                'created_at' => now(),

                'updated_at' => now(),

            ]));

        }

    }

}

