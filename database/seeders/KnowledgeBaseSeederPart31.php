<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KnowledgeBaseSeederPart31 extends Seeder
{
    public function run(): void
    {
        $entries = [
            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Dukkakli pichanlar (beda, yonuchqa) bilan donli ozuqalarning Kalsiy-Fosfor nisbati bo\'yicha bir-birini to\'ldirishi', 'keywords' => 'beda pichani, don, kalsiy fosfor nisbati, dukkakli ozuqa, mineral balans', 'content' => "Ozuqa tarkibi jadvallariga ko'ra, beda va boshqa dukkakli pichanlar kalsiyga juda boy, ammo fosforga nisbatan kambag'al bo'ladi (Ca:P nisbati 10:1 dan ham oshishi mumkin). Aksincha, makkajo'xori, arpa, bug'doy kabi donli ozuqalarda fosfor kalsiyga qaraganda bir necha barobar ko'p bo'ladi. Shu sababli faqat dukkakli pichan yoki faqat don bilan boqish mineral muvozanatini buzadi — ikkala guruh ozuqasini to'g'ri nisbatda aralashtirish yoki mineral qo'shimchalar bilan tuzatish zarur.", ],
            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'G\'alla somonining past oqsil va mineral tarkibi hamda uni ratsionda ishlatish chegarasi', 'keywords' => 'somon, past protein, dag\'al ozuqa, to\'ldiruvchi, mineral yetishmovchiligi', 'content' => "Bug'doy va boshqa g'alla somonlarida xom protein miqdori juda past (taxminan 37-46 g/kg quruq moddada), hazm bo'ladigan protein esa bundan ham kamroq bo'ladi, shuningdek kalsiy va fosfor miqdori ham minimal darajada. Shu sababli somon ratsionning asosiy ozuqa manbai sifatida emas, faqat qorin hajmini to'ldiruvchi qo'shimcha dag'al ozuqa sifatida ishlatilishi, va albatta protein hamda mineral qo'shimchalar bilan boyitilishi zarur.", ],
            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Soya shroti va boshqa yog\' kunjaralarining protein sifati bo\'yicha taqqoslanishi', 'keywords' => 'soya shroti, kunjaralar, protein sifati, aminokislota, oqsil manbai', 'content' => "Yog' ekstraksiyasidan qolgan kunjaralar (shrot/jmix) orasida soya shroti eng yuqori protein miqdoriga (35% atrofida) va eng muvozanatlashgan aminokislota tarkibiga ega hisoblanadi, shu sababli u \"etalon\" oqsil manbai sifatida qaraladi. Kungaboqar va rapsning kunjaralari protein miqdori bo'yicha soyaga yaqinlashsa-da, aminokislota tarkibi (ayniqsa lizin) bo'yicha undan pastroq, paxta kunjarasi esa qo'shimcha ravishda gossipol tufayli ehtiyotkorlik talab qiladi.", ],
            ['direction' => 'sut', 'category' => 'ozuqa_ratsion', 'topic' => 'Lyutserna (beda)ning o\'sish bosqichiga qarab ozuqaviy qiymatining pasayishi', 'keywords' => 'lyutserna, beda, o\'sish bosqichi, gullash, oqsil pasayishi, o\'rish vaqti', 'content' => "Beda (lyutserna)ning ozuqaviy qiymati uning o'sish bosqichiga qat'iy bog'liq: g'unchalash bosqichida protein va energiya miqdori eng yuqori bo'ladi, gullash bosqichiga o'tgan sari esa protein foizi va hazmlilik darajasi sezilarli pasayib, kletchatka ulushi ortadi. Shuning uchun bedani yuqori sifatli pichan yoki silos sifatida yig'ish uchun uni gullashdan oldin, g'unchalash bosqichida o'rib olish tavsiya etiladi.", ],
            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Makkajo\'xori silosining energiya manbai sifatida protein bilan to\'ldirilishi zarurati', 'keywords' => 'makkajo\'xori silosi, energiya manbai, protein kamligi, ratsion balansi, to\'ldiruvchi ozuqa', 'content' => "Makkajo'xori silosi ratsionning asosiy energiya manbalaridan biri bo'lsa-da, uning xom protein miqdori nisbatan past (taxminan 23 g/kg quruq moddada). Shu sababli ratsionda makkajo'xori silosining ulushi katta bo'lgan sari, uni muvozanatlash uchun yuqori proteinli ozuqalar (dukkakli pichan, kunjaralar, mochevina) qo'shish ehtiyoji ham shunga mutanosib ravishda ortadi, aks holda ratsionning umumiy protein darajasi yetishmay qoladi.", ],
            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Ildizmevali ozuqalar (kartoshka, lavlagi, sabzi)ning yuqori namlik va shakar/kraxmal manbai sifatidagi o\'rni', 'keywords' => 'ildizmeva, lavlagi, kartoshka, sabzi, namlik, shakar, kraxmal, energiya', 'content' => "Kartoshka, yem lavlagi va sabzi kabi ildizmevali ozuqalarning quruq modda miqdori juda past (12-23% atrofida), ya'ni ularning katta qismi suvdan iborat, biroq quruq moddasining aksariyati oson hazm bo'ladigan shakar (lavlagida) yoki kraxmal (kartoshkada) ko'rinishida bo'ladi. Bu ularni tez ta'sir qiluvchi energiya manbai sifatida qimmatli qiladi, lekin yuqori namligi sababli ratsiondagi ulushi cheklangan bo'lishi kerak — aks holda hayvon quruq modda bo'yicha yetarli energiya ololmay qoladi.", ],
            ['direction' => 'umumiy', 'category' => 'ozuqa_ratsion', 'topic' => 'Bug\'doy kepagining protein va fosfor manbai sifatidagi arzon alternativligi', 'keywords' => 'bug\'doy kepagi, otrubi, arzon protein, fosfor, ratsion, iqtisod', 'content' => "Bug'doy kepagi (otrubi) donga qaraganda ancha arzon bo'lishiga qaramay, nisbatan yaxshi protein miqdoriga (taxminan 150 g/kg) va yuqori fosfor tarkibiga ega, bu esa uni ayniqsa dukkakli pichanga boy (fosforga kambag'al) ratsionlarni muvozanatlashda foydali va iqtisodiy jihatdan tejamkor qo'shimcha qiladi.", ],
        ];

        foreach ($entries as $entry) {
            DB::table('knowledge_base')->insert(array_merge($entry, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
