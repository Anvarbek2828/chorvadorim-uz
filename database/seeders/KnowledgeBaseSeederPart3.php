<?php

namespace Database\Seeders;



use Illuminate\Database\Seeder;

use Illuminate\Support\Facades\DB;



class KnowledgeBaseSeederPart3 extends Seeder

{

    public function run(): void

    {

        $entries = [

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Mikrobial oqsil (MCP) sintezi va azotga bo\'lgan ehtiyoj', 'keywords' => 'mikrob, oqsil, azot, RDP, ammiak, peptid, TDN', 'content' => "Qorin mikroblari o'sishi va mikrobial oqsil sintezi uchun ammiak, peptidlar va aminokislotalar kerak bo'ladi. Mikrobial oqsil hosildorligi iste'mol qilingan har 1 kg TDN uchun 130 gramm deb baholanadi. Mikroblar uchun zarur RDP miqdori hosil bo'ladigan mikrobial oqsildan 1.18 baravar ko'p bo'lishi kerak."],

            ['direction' => 'nasl', 'category' => 'reproduksiya', 'topic' => 'Ratsionda ortiqcha oqsilning (CP) ko\'payishga salbiy ta\'siri', 'keywords' => 'oqsil, CP, reproduksiya, qochish, BUN, MUN', 'content' => "Ratsionda xom oqsil miqdorining 19% dan oshishi sigirlarning homilador bo'lish ko'rsatkichlarini pasaytiradi. Qondagi karbamid azoti 20 mg/dl dan yoki sutdagi 19 mg/dl dan oshishi urug'lanishga salbiy ta'sir ko'rsatadi - ortiqcha ammiak tuxum hujayra va embrionga toksik ta'sir qiladi."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Qorinda uglevod va oqsil parchalanishini sinxronlashtirish', 'keywords' => 'sinxronizatsiya, uglevod, oqsil, fermentatsiya', 'content' => "Qorinda uglevodlar va oqsilning parchalanish tezligini sinxronlashtirish mikrobial oqsil sintezi samaradorligini oshirishi mumkin. Muhimi mikroblar uchun fermentlanadigan energiya va azotning yetarli darajada ta'minlanishidir."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Endogen oqsilning ajralishi va hazm bo\'lishi', 'keywords' => 'endogen oqsil, azot, DMI, ingichka ichak', 'content' => "Ingichka ichakka yetib boradigan oqsil ozuqa va mikroblardan tashqari so'lak va hazm traktidan ko'chgan hujayralardan ham keladi. O'tayotgan endogen azot miqdori har bir kg quruq modda uchun taxminan 1.9 gramm."],

            ['direction' => 'umumiy', 'category' => 'parvarish', 'topic' => 'Yashab qolish uchun metabolik oqsil (MP) ehtiyoji', 'keywords' => 'metabolik oqsil, MP, yashash ehtiyoji, siydik azoti', 'content' => "Sigirlarning yashab qolishi uchun metabolik oqsil ehtiyoji asosan siydikdagi endogen azot, teri va jun to'kilishi hamda najasdagi azot yo'qotishlarini qoplashdan iborat."],

            ['direction' => 'umumiy', 'category' => 'parvarish', 'topic' => 'Haroratning quruq modda iste\'moliga (DMI) va energiyaga ta\'siri', 'keywords' => 'issiq, sovuq, harorat, quruq modda, DMI', 'content' => "Atrof-muhit harorati sigirlarning DMI va energiya sarfiga bevosita ta'sir qiladi. Issiqlik stressi quruq modda iste'molini keskin kamaytiradi (ba'zan 55% gacha) va yashash uchun zarur energiyani 7-25% gacha oshiradi."],

            ['direction' => 'sut', 'category' => 'ozuqa_ratsion', 'topic' => 'Tana holati indeksi (BCS) va energiya zaxiralari', 'keywords' => 'BCS, semizlik, energiya, tana vazni, laktatsiya', 'content' => "Erta laktatsiya davrida sigirlar tana yog'larini eritib energiya zaxiralaridan foydalanadi. BCS 1 birlikka pasayishi taxminan 417 Mcal NEL beradi, bu 564 kg sut ishlab chiqarishga yetarli."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Ratsionda yog\' miqdori (Fat) cheklovlari', 'keywords' => 'yog\', lipid, quruq modda, energiya', 'content' => "Ratsionning energiya zichligini oshirish uchun yog' qo'shiladi, ammo jami yog' miqdori quruq moddaning 6-7% idan oshmasligi kerak. Yog' ko'payishi qorin fermentatsiyasini buzib, quruq modda iste'molini cheklaydi."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Uglevodlar: NDF va NFC balansini saqlash', 'keywords' => 'NDF, NFC, tola, uglevod, atsidoz', 'content' => "Ratsionda NDF ning minimal miqdori 25% bo'lishi va kamida 19% i dag'al xashakdan kelishi tavsiya etiladi. NFC ning maksimal miqdori 44% dan oshmasligi kerak, aks holda kislotalilik ortadi."],

            ['direction' => 'sut', 'category' => 'ozuqa_ratsion', 'topic' => 'Himoyalangan oqsillar (Rumen Protected Proteins)', 'keywords' => 'RUP, oqsil, issiqlik ishlovi, aminokislota', 'content' => "Ozuqa oqsillarining qorinda parchalanishini kamaytirish uchun issiqlik yoki kimyoviy ishlov beriladi. Ammo haddan tashqari qizdirish lizin kabi muhim aminokislotalarni yo'q qilishi mumkin. RUP yuqori mahsuldor sigirlar uchun muhim."],

            ['direction' => 'umumiy', 'category' => 'parvarish', 'topic' => 'Oziqlantirish boshqaruvi va oxur maydoni', 'keywords' => 'oxur, joy, TMR, xulq-atvor, chastota', 'content' => "Sigirlar erkin iste'mol qilishi uchun har biriga kamida 46 sm oxur maydoni ajratilishi tavsiya etiladi. TMR rumen fermentatsiyasini barqarorlashtiradi va ozuqa tanlab yeyilishining oldini oladi."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Iste\'mol darajasi oshganda hazmdorlikning pasayishi', 'keywords' => 'hazmdorlik, energiya, iste\'mol darajasi, TDN', 'content' => "Quruq modda iste'moli yashash ehtiyojidan oshgan sari, ozuqaning hazm bo'lish darajasi pasayib boradi. Yuqori mahsuldor sigirlar ratsionini hisoblashda bu hazmdorlik pasayishi inobatga olinishi kerak."],

            ['direction' => 'sut', 'category' => 'parvarish', 'topic' => 'Yaylovda boqiladigan sigirlarning qo\'shimcha energiya ehtiyojlari', 'keywords' => 'yaylov, faollik, energiya, yurish', 'content' => "Yaylovda boqiladigan sigirlar yurish faolligi uchun ko'proq energiya sarflaydi. Tekis yaylovlarda energiya ehtiyoji 10-12% ga, tepalikli yaylovlarda 50% gacha oshishi mumkin."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Dag\'al xashakning zarracha o\'lchami va chaynalish', 'keywords' => 'zarracha o\'lchami, tola, beda, pH, chaynalish', 'content' => "Tolali ozuqalar yetarli bo'lsa-da, zarracha o'lchami juda mayda bo'lsa, rumen pH darajasi va sut yog'liligi pasayadi. Beda pichani zarrachalarining o'rtacha uzunligi kamida 3 mm bo'lishi talab etiladi."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Oqsil fraksiyalari (A, B, C) va ularning hazm bo\'lishi', 'keywords' => 'oqsil fraksiyalari, NPN, degradatsiya, RUP, RDP', 'content' => "Ozuqa oqsillari qorinda parchalanishiga ko'ra 3 fraksiyaga bo'linadi: A (darhol parchalanuvchi NPN), B (potensial parchalanuvchi oqsil) va C (butunlay parchalanmaydigan oqsil). B fraksiyasining parchalanmagan qismi RUP ni tashkil qiladi."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Ratsion namligining quruq modda iste\'moliga ta\'siri', 'keywords' => 'namlik, quruq modda, DMI, silos, fermentatsiya', 'content' => "Ratsion tarkibidagi namlik 50% dan oshganda quruq modda iste'moli pasayadi. Bu holat suvning ko'pligi bilan emas, balki nam ozuqadagi fermentatsiya yakuniy mahsulotlari (kislotalar) bilan bog'liq."],

            ['direction' => 'sut', 'category' => 'ozuqa_ratsion', 'topic' => 'Xashak va Konsentrat nisbatining mahsuldorlikka ta\'siri', 'keywords' => 'xashak, konsentrat, nisbat, DMI, hazmdorlik', 'content' => "Ratsionda konsentratlar ulushini 60% gacha oshirish odatda quruq modda iste'molini oshiradi. Ammo konsentratning haddan tashqari ko'payishi qorin muhitini buzib, sut yog'liligini kamaytirishi mumkin."],

            ['direction' => 'nasl', 'category' => 'parvarish', 'topic' => 'Bo\'g\'ozlikning oxirgi bosqichida energiya ehtiyoji', 'keywords' => 'homiladorlik, bo\'g\'ozlik, energiya, buzoq vazni', 'content' => "Bo'g'oz sigirlarda homila o'sishi uchun qo'shimcha energiya ehtiyoji homiladorlikning 190-kunidan boshlab keskin ortadi. Bu ehtiyoj tug'iladigan buzoqning vazniga bog'liq bo'lib, laktatsiya va yashash ehtiyojlari ustiga qo'shiladi."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Jismoniy samarali tola (peNDF) va kavsh qaytarish', 'keywords' => 'peNDF, samarali tola, kavsh qaytarish, pH', 'content' => "Jismoniy samarali tola sigirda kavsh qaytarishni rag'batlantirish va qorin muhitini saqlash xususiyatini o'lchaydi. Samarali tola yetarli bo'lsa, so'lak ajralishi ko'payadi va sut yog'i foizi me'yorda saqlanadi."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Yog\'larning to\'yinganlik darajasi va hazm bo\'lishi', 'keywords' => 'yod soni, to\'yinmagan yog\', hazmdorlik', 'content' => "Qo'shimcha yog'larning hazm bo'lishi to'yinmaganlik darajasiga bog'liq. Yod soni 40 dan yuqori yog'larning hazmdorligi 89% gacha, past bo'lganlariniki 74% atrofida bo'ladi. To'yinmagan yog'lar qorin mikroflorasiga salbiy ta'sir qilishi mumkin."],

        ];



        foreach ($entries as $entry) {

            DB::table('knowledge_base')->insert(array_merge($entry, [

                'created_at' => now(),

                'updated_at' => now(),

            ]));

        }

    }

}

