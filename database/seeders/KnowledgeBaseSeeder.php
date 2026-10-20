<?php

namespace Database\Seeders;



use Illuminate\Database\Seeder;

use Illuminate\Support\Facades\DB;



class KnowledgeBaseSeeder extends Seeder

{

    public function run(): void

    {

        $entries = [

            ['direction' => 'sut', 'category' => 'ozuqa_ratsion', 'topic' => 'Quruq modda iste\'moli (DMI) asoslari', 'keywords' => 'quruq modda, DMI, ishtaha, laktatsiya, oziqlantirish, vazn', 'content' => "Quruq modda iste'moli oziq moddalar ta'minotini belgilab bergani uchun sog'lom va mahsuldor chorvachilikda eng muhim ko'rsatkich hisoblanadi. DMI ni aniq hisoblash sigirni ortiqcha yoki kam oziqlantirishning oldini oladi. Ratsion tuzishda sigirning vaznini va laktatsiya haftasini inobatga olish zarur, chunki dastlabki haftalarda sigirning ishtahasi nisbatan past bo'ladi."],

            ['direction' => 'sut', 'category' => 'ozuqa_ratsion', 'topic' => 'Tana holati bahosi (BCS) va energiya', 'keywords' => 'energiya, NEL, tana holati, BCS, semizlik, laktatsiya', 'content' => "Sigirlarning energiya talablari Laktatsiya Sof Energiyasi (NEL) birligida o'lchanadi. Tana Holati Bahosi (BCS) sigirlarning semizlik darajasini 1 dan 5 gacha bo'lgan shkalada o'lchashning eng amaliy usulidir. Erta laktatsiya davrida barcha sigirlar sut berish uchun o'z tana zaxiralaridan energiya safarbar qiladi va buning oqibatida BCS ko'rsatkichi pasayadi."],

            ['direction' => 'sut', 'category' => 'ozuqa_ratsion', 'topic' => 'Uglevodlar, tola (NDF) va atsidoz xavfi', 'keywords' => 'uglevod, tola, NDF, NFC, atsidoz, laminit, qorin pH', 'content' => "Ratsionda tola, xususan Neytral Detergent Tola (NDF) miqdori qorinning sog'lom ishlashini ta'minlash uchun juda muhimdir. Sog'in sigirlar ratsionining umumiy quruq moddasida NDF taxminan 25% ni tashkil qilishi tavsiya etiladi. Ratsionda non-strukturaviy uglevodlar haddan tashqari ko'payib ketishi atsidoz hamda laminit kabi jiddiy kasalliklarga olib kelishi mumkin."],

            ['direction' => 'sut', 'category' => 'reproduksiya', 'topic' => 'Oqsil (CP) me\'yori va reproduksiyaga ta\'siri', 'keywords' => 'oqsil, CP, RDP, RUP, mochevina, BUN, MUN, urug\'lanish', 'content' => "Sut mahsuldorligini oshirish uchun faqatgina Xom Oqsil (CP) miqdorini ko'paytirish yetarli emas. Ratsionda xom oqsil miqdori 19-20% dan oshib ketsa, qonda va sutda mochevina azoti ko'payib ketadi. Bu holat sigirning urug'lanish qobiliyatini pasaytiradi va embrionning yashab qolish xavfini kamaytiradi."],

            ['direction' => 'sut', 'category' => 'ozuqa_ratsion', 'topic' => 'Yog\' qo\'shimchalari va ularning ta\'siri', 'keywords' => 'yog\', energiya, DMI, sut tarkibi, homiladorlik', 'content' => "Ratsionning quvvatini oshirish uchun yog' qo'shish foydali, lekin umumiy yog' miqdori quruq moddaning 6-7% idan oshmasligi shart. Haddan tashqari ko'p yog' qorin bakteriyalari faoliyatini bo'g'adi va sigirning yem yeyishini keskin pasaytiradi."],

            ['direction' => 'nasl', 'category' => 'parvarish', 'topic' => 'G\'unajinlarni o\'stirish va tana vazni', 'keywords' => 'g\'unajin, o\'sish, tana vazni, energiya, oqsil', 'content' => "G'unajinlar o'sishi davrida optimal tana vazniga erishish uchun energiya va oqsil me'yorlari aniq belgilanishi kerak. Atrof-muhit sharoitlari g'unajinlarning quruq modda iste'moliga va o'sish tezligiga bevosita ta'sir ko'rsatadi."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Oziqlantirish va atrof-muhit muhofazasi', 'keywords' => 'atrof-muhit, azot, fosfor, ekologiya, go\'ng', 'content' => "Chorvachilikda atrof-muhit ifloslanishini kamaytirish ratsiondagi oziq moddalarni aniq me'yorlash bilan chambarchas bog'liq. Azot va fosfor miqdorini sigirning ehtiyojidan ortiqcha bermaslik orqali go'ng orqali ajraladigan zaharli moddalarni kamaytirish mumkin."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Ozuqani qayta ishlash va kraxmal hazmdorligi', 'keywords' => 'qayta ishlash, kraxmal, hazmdorlik, maydalash', 'content' => "Ozuqalarni fizik qayta ishlash, issiqlik va bug' bilan ishlov berish kraxmalning qorinda hazm bo'lish darajasini sezilarli darajada oshiradi. Donlarni maydalash yoki bug'lab yanchish orqali sigirning energiya ta'minotini yaxshilash mumkin."],

            ['direction' => 'sut', 'category' => 'ozuqa_ratsion', 'topic' => 'Limilovchi aminokislotalar: Metionin va Lizin', 'keywords' => 'aminokislota, metionin, lizin, RUP, sut oqsili', 'content' => "Sut oqsilini maksimal darajada sintez qilish uchun sigirlarga aniq aminokislotalar balansi kerak. Metionin va lizin ko'pincha eng ko'p yetishmaydigan aminokislotalar hisoblanadi. Ularni himoyalangan shaklda qo'shish sut miqdori va oqsil foizini oshiradi."],

            ['direction' => 'sut', 'category' => 'parvarish', 'topic' => 'Quruq davr strategiyasi (Far-off va Close-up)', 'keywords' => 'quruq davr, far-off, close-up, tranzit, semizlik', 'content' => "Quruq davr ikki bosqichga bo'linadi: erta quruq davr (tug'ishga 60-21 kun) va tug'ishga yaqin davr (so'nggi 21 kun). Erta davrda semirib ketmaslik uchun past energiyali ratsion beriladi. Tug'ishga yaqin davrda maxsus tranzit ratsioniga o'tiladi."],

            ['direction' => 'sut', 'category' => 'kasallik', 'topic' => 'Ratsionning kation-anion balansi (DCAD)', 'keywords' => 'DCAD, kation, anion, kalsiy, sut isitmasi, gipokalsemiya', 'content' => "Tug'ishga yaqin davrda ratsionning kation-anion balansi manfiy bo'lishi sut isitmasining oldini olishda samarali usuldir. Bunga erishish uchun ratsionga xlorid va sulfat tuzlari qo'shiladi, bu suyaklardan kalsiyning qonga ajralib chiqishini faollashtiradi."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Ionoforlar va qorin fermentatsiyasi', 'keywords' => 'ionofor, monenzin, fermentatsiya, propionat, metan', 'content' => "Monenzin kabi ionoforlar qorindagi mikroorganizmlar populyatsiyasini o'zgartirib ozuqa samaradorligini oshiradi. Ular propionat ishlab chiqarishni ko'paytiradi - bu sigir uchun eng samarali energiya manbai. Shuningdek, metan gazi hosil bo'lishini kamaytiradi."],

            ['direction' => 'sut', 'category' => 'kasallik', 'topic' => 'Issiqlik stresi va mineral ehtiyojlar', 'keywords' => 'issiqlik stresi, terlash, kaliy, natriy, THI', 'content' => "Issiq iqlim sharoitida sigirlar terlash orqali ko'p miqdorda kaliy va natriy yo'qotadi. Issiqlik stresi davrida ratsionda kaliy va natriy miqdorini oshirish tavsiya etiladi. Natriy bikarbonat kabi bufer moddalar qorin pH darajasini saqlashga yordam beradi."],

            ['direction' => 'nasl', 'category' => 'parvarish', 'topic' => 'Buzoqlarni sutdan ajratish mezonlari', 'keywords' => 'buzoq, sutdan ajratish, starter yem, qorin rivojlanishi', 'content' => "Buzoqlarni sutdan ajratish yoshiga emas, qattiq ozuqa (starter yem) iste'mol qilish quvvatiga asoslanishi kerak. Buzoq 3 kun ketma-ket kuniga kamida 1 kg starter yem yeyishni boshlaganda, uni sutdan to'liq ajratish mumkin."],

            ['direction' => 'gosht', 'category' => 'ozuqa_ratsion', 'topic' => 'Bo\'rdoqiga boqishda kraxmal va energiya balansi', 'keywords' => 'bo\'rdoqi, go\'sht, kraxmal, konsentrat, ADG', 'content' => "Go'sht yo'nalishidagi qoramollarni bo'rdoqiga boqish bosqichida ratsiondagi konsentratlar ulushi 80-90% gacha oshirilishi mumkin. Yuqori kraxmalli ratsionlar kunlik o'rtacha semirishni tezlashtiradi. Atsidozning oldini olish uchun minimal 8-10% samarali tola saqlanishi shart."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Qorin mikroblari oqsili (Microbial Protein)', 'keywords' => 'mikrob oqsili, azot, fermentatsiya, qorin mikroflorasi', 'content' => "Sigir ichagiga yetib boradigan aminokislotalarning 50-80% i qorinda yashovchi mikroorganizmlar tomonidan sintez qilingan mikrob oqsilidan iborat. Mikroblar oqsilini maksimal sintez qilish uchun tez parchalanadigan uglevodlar va azot bir vaqtda parchalanishi shart."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Oqsil bo\'lmagan azot (NPN) - Karbamid', 'keywords' => 'NPN, karbamid, mochevina, azot, toksiklik', 'content' => "Karbamid kabi oqsil bo'lmagan azot manbalari qorin mikroblari uchun arzon azot manbai. Uni ishlatish qat'iy me'yorlanishi kerak - odatda quruq moddaning 1% idan oshmasligi kerak, aks holda ammiak toksikozi xavfi bor."],

            ['direction' => 'sut', 'category' => 'kasallik', 'topic' => 'Oziqlantirishning elin yallig\'lanishiga (Mastit) ta\'siri', 'keywords' => 'mastit, elin yallig\'lanishi, immunitet, E vitamini, selen', 'content' => "Mastit asosan bakterial infeksiya bo'lsa-da, sigirning oziqlanish holati chidamlilikka ta'sir qiladi. E vitamini, selen, mis va rux yetishmasligi immunitetni susaytiradi. Bu moddalarning optimal ta'minlanishi mastitga chalinish xavfini kamaytiradi."],

            ['direction' => 'umumiy', 'category' => 'reproduksiya', 'topic' => 'A vitamini va Beta-karotinning reproduksiyaga ta\'siri', 'keywords' => 'A vitamini, beta-karotin, reproduksiya, gormonlar', 'content' => "A vitamini va beta-karotin sog'lom reproduktiv tizimni saqlashda muhim. Beta-karotin tuxumdonlarda to'planib progesteron gormoni ishlab chiqarilishini qo'llab-quvvatlaydi. Yangi beda va ko'k o't beta-karotinga boy, uzoq saqlangan pichanda bu vitamin kamayadi."],

        ];



        foreach ($entries as $entry) {

            DB::table('knowledge_base')->insert(array_merge($entry, [

                'created_at' => now(),

                'updated_at' => now(),

            ]));

        }

    }

}

