<?php

namespace Database\Seeders;



use Illuminate\Database\Seeder;

use Illuminate\Support\Facades\DB;



class KnowledgeBaseSeederPart10 extends Seeder

{

    public function run(): void

    {

        $entries = [

            ['direction' => 'sut', 'category' => 'kasallik', 'topic' => 'Subklinik ketoz va BHBA qon ko\'rsatkichi', 'keywords' => 'BHBA, ketoz, subklinik, qon tahlili, propilenglikol', 'content' => "Qondagi BHBA miqdorini o'lchash subklinik ketozni aniqlashning oltin standarti. Agar BHBA 1.2 mmol/L dan oshsa, sigir tashqaridan sog'lom ko'rinsa-da, darhol energetik terapiyaga (propilenglikol) muhtoj."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Makkajoxori steam-flaking va energiya ozlashtirilishi', 'keywords' => 'steam flaking, jelatinizatsiya, energiya, kraxmal', 'content' => "Makkajo'xorini bug'da pishirib yanchish (steam flaking) orqali kraxmal jelatinizatsiya qilinadi. Bu energiya ozlashtirilishini 15-20 foizga oshiradi va ratsionni iqtisodiy tejamkor qiladi."],

            ['direction' => 'umumiy', 'category' => 'boshqaruv', 'topic' => '"Bo\'sh oxur" sindromi va tez yeyish (Slug feeding)', 'keywords' => 'bo\'sh oxur, slug feeding, pH, atsidoz, ozuqa stoli', 'content' => "Oxur 2-3 soatdan ortiq bo'sh qolsa, sigirlar ochqaydi. Yangi ozuqa tashlanganda ular me'yoridan tez va ko'p yeb yuboradi (slug feeding), bu qorinda kislota birdaniga ko'payishiga olib keladi."],

            ['direction' => 'umumiy', 'category' => 'kasallik', 'topic' => 'Mikotoksinlar (Aflatoksin) va sutga o\'tishi', 'keywords' => 'mikotoksin, aflatoksin, zamburug\', sut, M1, immunitet', 'content' => "Silos yoki donli ozuqalardagi aflatoksin sigir immunitetini bloklaydi. U oshqozonda parchalanmay qonga so'rilib, Aflatoksin M1 ko'rinishida sutga o'tadi va sutni inson iste'moli uchun zaharli qiladi."],

            ['direction' => 'parvarish', 'category' => 'kasallik', 'topic' => 'Laminit — SARA ning kechikkan asorati', 'keywords' => 'laminit, tuyoq, atsidoz, gistamin, biotin', 'content' => "Laminit ko'pincha SARA dan 1-2 oy o'tib namoyon bo'ladi. Bakteriyalar qirilganda ajralgan gistamin va endotoksinlar tuyoqqa yetib, qon tomirlarini yorib yuboradi. Biotin qo'shish tuyoq shoxini qattiqlashtiradi."],

            ['direction' => 'umumiy', 'category' => 'reproduksiya', 'topic' => 'RDP ortiqchaligi va erta embrion o\'limi', 'keywords' => 'RDP, MUN, embrion o\'limi, bachadon pH', 'content' => "RDP me'yordan oshsa, MUN oshib, bachadon pH sini o'zgartiradi. Bachadon muhiti toksik holatga kelib, embrion o'z joyiga birikolmay, dastlabki 10-15 kun ichida nobud bo'ladi."],

            ['direction' => 'nasl', 'category' => 'boshqaruv', 'topic' => 'G\'unajinlarda balog\'atgacha semirish va yelin xatosi', 'keywords' => 'g\'unajin, yelin, semirish, allometrik o\'sish', 'content' => "G'unajinlarning yelin to'qimasi 3-9 oylik davrda eng tez o'sadi (allometrik). Ortiqcha energiya berilsa, yelin o'rnini yog' egallab, kelajakdagi sut berish qobiliyati 10-20% pasayadi."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'TMR namligi va sigirlarning ozuqani saralashi', 'keywords' => 'sorting, saralash, TMR namligi, peNDF', 'content' => "TMR namligi 50% dan past bo'lsa, sigirlar uzun kletchatkani chetga surib, mayda konsentratlarni terib yeydi (sorting). Bu SARA ga sabab bo'luvchi noto'g'ri ratsion iste'moliga olib keladi."],

            ['direction' => 'umumiy', 'category' => 'boshqaruv', 'topic' => 'Suvdagi Temir va Sulfatlarning mikroelement so\'rilishiga ta\'siri', 'keywords' => 'suv sifati, temir, sulfat, mis, rux, antagonizm', 'content' => "Suvda Temir (>0.3 ppm) yoki Sulfat yuqori bo'lsa, ular Mis, Rux, Marganets kabi mikroelementlarni bog'lab, so'rilishiga yo'l qo'ymaydi. Bu immunitet pasayishi va reproduktiv muammolarga olib keladi."],

            ['direction' => 'sut', 'category' => 'ozuqa_ratsion', 'topic' => 'Quruq sigirlar uchun "qisqa kun" fotoperiodi', 'keywords' => 'fotoperiod, qisqa kun, quruq sigir, yelin yangilanishi', 'content' => "Sog'in sigirlar uchun uzun kun (16 soat yorug'lik) sut oshiradi, lekin quruq sigirlar uchun teskarisi - qisqa kun (8 soat yorug'lik, 16 soat qorong'ilik) tavsiya etiladi. Bu keyingi laktatsiyada yelin to'qimasi yangilanishini ta'minlaydi."],

            ['direction' => 'sut', 'category' => 'kasallik', 'topic' => 'Egizak homiladorlik va DMI ning erta qulashi', 'keywords' => 'egizak homila, DMI, tranzit davr, qorin hajmi', 'content' => "Egizak homila ko'targan sigirlarda tug'ruqqa 2-3 hafta qolganda bachadon qorinni bosib, DMI bitta buzoqqa nisbatan ancha erta va 30% gacha keskin qulaydi. Bunday sigirlar energiyasi konsentrlangan ratsionga erta o'tkazilishi shart."],

            ['direction' => 'sut', 'category' => 'ozuqa_ratsion', 'topic' => 'Zeolit va gipokalsemiya profilaktikasi', 'keywords' => 'zeolit, kalsiy boglovchi, gipokalsemiya, PTH', 'content' => "Close-up davrida zeolit kabi kalsiy bogaovchi qoshimchalar qorindagi kalsiyni boglab, qonga otkazmaydi. Bu sun'iy kalsiy ochligi yaratib, PTH gormonini faollashtirib, tuqishda suyaklardan tez kalsiy ajralishini taminlaydi."],

            ['direction' => 'sut', 'category' => 'boshqaruv', 'topic' => 'Yotish joyi (Freestall) va sut sintezi bog\'liqligi', 'keywords' => 'freestall, yotish joyi, dam olish, qon aylanishi', 'content' => "Sog'in sigir kuniga 10-12 soat yotishi kerak. Yotgan paytda yelindagi qon aylanishi 25-30% oshib, sut sintezini jadallashtiradi. Noqulay yotish joyi stress va tuyoq shikastlanishiga olib keladi."],

            ['direction' => 'buzoq', 'category' => 'salomatlik', 'topic' => 'Buzoq elektrolitlari va ich ketishi davolash', 'keywords' => 'elektrolit, diarreya, ich ketishi, suvsizlanish', 'content' => "Ich ketishi bilan og'rigan buzoqlarda elektrolitlar (Natriy, Kaliy, Xlor) yo'qoladi. Natriy bikarbonat va glyukoza bo'lgan elektrolit eritmalarini sutdan 2 soat oldin yoki keyin berish hayotni saqlaydi."],

            ['direction' => 'sut', 'category' => 'salomatlik', 'topic' => 'Kaliforniya Mastit Testi (CMT) - subklinik diagnostika', 'keywords' => 'CMT, mastit, SCC, subklinik, diagnostika', 'content' => "CMT sut tarkibidagi somatik hujayralar sonini aniqlash uchun ishlatiladi. Reaktiv leykotsit DNKsi bilan to'qnashib, jel hosil qiladi. Bu yashirin mastitni o'z vaqtida aniqlashga yordam beradi."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Propionat va glikoneogenez orqali energiya ta\'minoti', 'keywords' => 'propionat, VFA, glikoneogenez, jigar, glyukoza', 'content' => "Uchta asosiy uchuvchi yog' kislotasidan (asetat, propionat, butirat) eng muhimi propionat, u jigarda glyukozaga aylanadi. Propionat ko'p bo'lsa, energiya balansi barqarorlashadi va sut hajmi yuqori bo'ladi."],

            ['direction' => 'sut', 'category' => 'boshqaruv', 'topic' => 'Sog\'ish zali oldidagi kutish vaqti va stress', 'keywords' => 'sog\'ish zali, kutish maydoni, stress, laminit', 'content' => "Sigirlar sog'ish zali oldida 2 soatdan ortiq tik turishi tavsiya etilmaydi. Uzoq kutish tuyoqqa bosim, ozuqa iste'moli vaqti qisqarishi va kortizol stress gormoni oshishiga olib keladi."],

        ];



        foreach ($entries as $entry) {

            DB::table('knowledge_base')->insert(array_merge($entry, [

                'created_at' => now(),

                'updated_at' => now(),

            ]));

        }

    }

}

