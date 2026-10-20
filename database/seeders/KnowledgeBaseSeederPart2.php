<?php

namespace Database\Seeders;



use Illuminate\Database\Seeder;

use Illuminate\Support\Facades\DB;



class KnowledgeBaseSeederPart2 extends Seeder

{

    public function run(): void

    {

        $entries = [

            ['direction' => 'sut', 'category' => 'parvarish', 'topic' => 'Suv ehtiyoji va iste\'moli', 'keywords' => 'suv, laktatsiya, harorat, sut miqdori', 'content' => "Sog'in sigirlar uchun suv eng muhim va ko'pincha e'tibordan chetda qoladigan oziq modda hisoblanadi. 1 litr sut ishlab chiqarish uchun o'rtacha 3-4 litr suv talab etiladi. Quruq modda iste'moli, atrof-muhit harorati va sut sog'imi oshishi bilan suvga bo'lgan ehtiyoj keskin ortadi."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Kalsiy va Fosfor nisbati', 'keywords' => 'kalsiy, fosfor, suyak, mineral, Ca:P nisbati', 'content' => "Ratsionda kalsiy (Ca) va fosfor (P) nisbati to'g'ri bo'lishi suyaklar rivojlanishi va sut isitmasi oldini olish uchun muhimdir. Laktatsiyadagi sigirlar uchun bu nisbat 1.5:1 dan 2.0:1 gacha bo'lishi tavsiya etiladi. Haddan tashqari ko'p fosfor berish atrof-muhit ifloslanishiga ham olib keladi."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'NDF va ADF (Tola fraksiyalari)', 'keywords' => 'NDF, ADF, tola, hazmdorlik, atsidoz', 'content' => "NDF sigirning quruq modda iste'mol qilish hajmini cheklovchi asosiy omildir, chunki u qorinda katta joy egallaydi. ADF esa ozuqaning hazm bo'lish darajasini ko'rsatadi. Ratsionda samarali tola yetishmasa kavsh qaytarish pasayadi va qorin kislotaliligi kelib chiqadi."],

            ['direction' => 'sut', 'category' => 'ozuqa_ratsion', 'topic' => 'Quruq modda iste\'molini (DMI) hisoblash asoslari', 'keywords' => 'DMI, quruq modda, vazn, sut, energiya', 'content' => "Sigirning kunlik quruq modda iste'moli uning tana vazni va sut miqdoriga bog'liq. O'rtacha, laktatsiya o'rtasidagi sog'lom sigir o'z tana vaznining 3.0-4.0% miqdorida quruq modda iste'mol qila oladi."],

            ['direction' => 'sut', 'category' => 'parvarish', 'topic' => 'Tana holati indeksi (BCS) dinamikasi', 'keywords' => 'BCS, semizlik, laktatsiya, quruq davr, energiya balansi', 'content' => "Sigirlarning tana holati indeksi laktatsiya sikli davomida o'zgarib turadi. Tuqqandan so'ng sigir tana zaxirasini sarflaydi. Maqsad, quruq davrga o'tishda BCS ko'rsatkichini 3.0-3.25 atrofida ushlab turishdir. Juda semiz sigirlarda metabolik kasalliklar xavfi yuqori bo'ladi."],

            ['direction' => 'nasl', 'category' => 'parvarish', 'topic' => 'Yangi tug\'ilgan buzoqlar va Og\'iz suti (Kolostrum)', 'keywords' => 'buzoq, og\'iz suti, kolostrum, immunitet, IgG', 'content' => "Yangi tug'ilgan buzoqlar xususiy immunitetsiz tug'iladi. Passiv immunitet shakllanishi uchun ular tug'ilgandan keyingi ilk 2-4 soat ichida, tana vaznining taxminan 10% miqdorida yuqori sifatli og'iz sutini ichishi qat'iy shart."],

            ['direction' => 'umumiy', 'category' => 'kasallik', 'topic' => 'Ketoz va salbiy energiya balansi (NEB)', 'keywords' => 'ketoz, energiya, jigar, glyukoza, NEB', 'content' => "Tuqqanidan so'ng sigirning energiya talabi ozuqa iste'mol qilish qobiliyatidan tezroq o'sadi, bu salbiy energiya balansini keltirib chiqaradi. Agar tana yog'larining parchalanishi juda tez bo'lsa, jigar ularni qayta ishlashga ulgurolmaydi va ketoz kasalligiga sabab bo'ladi."],

            ['direction' => 'gosht', 'category' => 'ozuqa_ratsion', 'topic' => 'Bo\'rdoqi qoramollarda oqsil ehtiyojining o\'zgarishi', 'keywords' => 'bo\'rdoqi, oqsil, mushak, o\'sish, semirtirish', 'content' => "Go'sht yo'nalishidagi qoramollarning yoshi oshib borishi bilan umumiy oqsilga bo'lgan ehtiyoji pasayib boradi. Yosh buqalar yuqori oqsil (13-14%) talab qilsa, bo'rdoqi bosqichida oqsil miqdorini 10-11% gacha tushirish va energiyaga urg'u berish samaralidir."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Vitamin D va kalsiy metabolizmi', 'keywords' => 'vitamin D, kalsiy, qon, suyak, so\'rilish', 'content' => "D vitamini sigir organizmida kalsiy va fosfor almashinuvini boshqaruvchi muhim elementdir. U ingichka ichakdan kalsiyning so'rilishini rag'batlantiradi. Yopiq binolarda saqlanadigan sigirlarga ratsion orqali D vitamini qo'shib berilishi shart."],

            ['direction' => 'sut', 'category' => 'ozuqa_ratsion', 'topic' => 'Lipidlar (Yog\') va Himoyalangan yog\'lar (Bypass Fats)', 'keywords' => 'yog\', lipid, energiya, bypass fat, rumen', 'content' => "Yog'lar energiya zichligi bo'yicha uglevodlardan 2.25 barobar ustun turadi. Ratsionda oddiy yog'lar 5-6% dan oshsa, qorindagi mikroblar faoliyatini bo'g'ib qo'yadi. Yuqori mahsuldor sigirlarga 'himoyalangan yog'lar' ishlatilishi maqsadga muvofiq."],

            ['direction' => 'umumiy', 'category' => 'parvarish', 'topic' => 'Suv sifati: Sulfat va Nitratlar ta\'siri', 'keywords' => 'suv sifati, toksin, sulfat, nitrat, sho\'rlanish', 'content' => "Suvning kimyoviy tarkibi ham juda muhim. Suvdagi sulfatlar va xloridlarning yuqori darajasi suv ichish hajmini kamaytiradi. Suvdagi nitratlar miqdori ortiqcha bo'lsa, toksik nitritga aylanib, abortlarga olib kelishi mumkin."],

            ['direction' => 'nasl', 'category' => 'parvarish', 'topic' => 'G\'unajinlarni urug\'lantirish mezonlari', 'keywords' => 'g\'unajin, urug\'lantirish, qochirish, yetuklik, vazn', 'content' => "G'unajinlarni birinchi marta urug'lantirish qarori ularning tana vazniga qarab belgilanishi kerak. Odatda, g'unajin yetuk sigir vaznining 55-60% iga yetganda tayyor hisoblanadi. Erta qochirish qiyin tuqish xavfini orttiradi."],

            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Mis (Cu), Molibden (Mo) va Oltingugurt (S) antagonizmi', 'keywords' => 'mis, molibden, oltingugurt, antagonizm, mikromineral', 'content' => "Agar ozuqa yoki suv tarkibida molibden va oltingugurt ko'p bo'lsa, ular qorinda tiomolibdatlar hosil qiladi. Bu modda misni qattiq bog'lab, uning so'rilishiga to'sqinlik qiladi. Natijada mis yetishmovchiligi (immunitet pasayishi, bepushtlik) kelib chiqadi."],

            ['direction' => 'umumiy', 'category' => 'kasallik', 'topic' => 'Selen (Se) va E vitaminining antioksidant sinergiyasi', 'keywords' => 'selen, E vitamini, antioksidant, yo\'ldosh ushlanib qolishi', 'content' => "Selen va E vitamini organizmda antioksidant sifatida birgalikda ishlaydi. Tranzit davridagi sigirlar ratsionida bu moddalar yetishmasligi yo'ldoshning ushlanib qolishi, metrit va mastit kasalliklari ehtimolini oshiradi."],

            ['direction' => 'sut', 'category' => 'ozuqa_ratsion', 'topic' => 'Sutdagi mochevina azoti (MUN)', 'keywords' => 'MUN, mochevina, azot, oqsil, energiya balansi', 'content' => "Sut tarkibidagi mochevina azoti miqdori sigir ratsionidagi oqsil va energiya balansi ko'rsatkichi hisoblanadi. Agar MUN darajasi 14-16 mg/dL dan oshsa, bu ratsionda tez parchalanuvchi oqsil ko'pligini yoki energiya yetishmayotganini anglatadi."],

            ['direction' => 'sut', 'category' => 'kasallik', 'topic' => 'Yog\'li sigir sindromi va Jigar lipidozi', 'keywords' => 'yog\'li sigir, jigar, semizlik, lipidoz, ishtaha', 'content' => "Sigirlarning haddan tashqari semirib ketishi (BCS > 3.75) tuqqandan so'ng og'ir oqibatlarga olib keladi. Kuchli energiya yetishmovchiligi tana yog'larining tez parchalanishiga va jigarni yog' bosishiga olib keladi, bu immunitetni izdan chiqaradi."],

        ];



        foreach ($entries as $entry) {

            DB::table('knowledge_base')->insert(array_merge($entry, [

                'created_at' => now(),

                'updated_at' => now(),

            ]));

        }

    }

}

