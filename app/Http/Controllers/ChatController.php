<?php



namespace App\Http\Controllers;



use App\Models\Animal;

use App\Models\KnowledgeBase;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Http;



class ChatController extends Controller

{

    public function show(Animal $animal)

    {

        $this->checkOwnership($animal);

        $messages = $animal->chatMessages;

        return view('chat.show', compact('animal', 'messages'));

    }



    public function ask(Request $request, Animal $animal)

    {

        $this->checkOwnership($animal);



        $validated = $request->validate([

            'question' => 'required|string|max:1000',

        ]);



        $relevantKnowledge = $this->findRelevantKnowledge($validated['question'], $animal->direction);

        $systemPrompt = $this->buildSystemPrompt($animal, $relevantKnowledge);

        $history = $this->buildHistory($animal);



        $messages = array_merge(

            [['role' => 'system', 'content' => $systemPrompt]],

            $history,

            [['role' => 'user', 'content' => $validated['question']]]

        );



        $response = Http::withToken(config('services.openai.api_key'))

            ->timeout(30)

            ->post('https://api.openai.com/v1/chat/completions', [

                'model' => 'gpt-4o-mini',

                'messages' => $messages,

                'temperature' => 0.7,

            ]);



        if ($response->failed()) {

            return back()->with('error', 'AI bilan bog\'lanishda xatolik yuz berdi. Birozdan so\'ng qayta urinib ko\'ring.');

        }



        $answer = $response->json('choices.0.message.content') ?? 'Javob olishda muammo yuz berdi.';

        $formattedAnswer = $this->formatAnswer($answer);



        $animal->chatMessages()->create([

            'question' => $validated['question'],

            'answer' => $formattedAnswer,

        ]);



        return redirect()->route('chat.show', $animal);

    }



    private function findRelevantKnowledge(string $question, string $direction): array

    {

        $questionLower = mb_strtolower($question);



        $entries = KnowledgeBase::where(function ($q) use ($direction) {

                $q->where('direction', $direction)->orWhere('direction', 'umumiy');

            })

            ->get();



        $matched = [];

        foreach ($entries as $entry) {

            $keywords = explode(',', mb_strtolower($entry->keywords));

            foreach ($keywords as $keyword) {

                $keyword = trim($keyword);

                if ($keyword !== '' && str_contains($questionLower, $keyword)) {

                    $matched[] = $entry;

                    break;

                }

            }

        }



        return array_slice($matched, 0, 3);

    }



    private function buildHistory(Animal $animal): array

    {

        $recentMessages = $animal->chatMessages()->orderBy('created_at', 'desc')->limit(5)->get()->reverse();



        $history = [];

        foreach ($recentMessages as $msg) {

            $history[] = ['role' => 'user', 'content' => $msg->question];

            $history[] = ['role' => 'assistant', 'content' => strip_tags($msg->answer)];

        }



        return $history;

    }



    /**

     * Ba'zi o'zbekcha chorvachilik atamalarini to'g'ri shaklga almashtiradi.

     * Masalan: "g'unajin" -> "g'o'najin"

     */

    private function fixTerminology(string $text): string

    {

        $replacements = [

            "g'unajin" => "g'o'najin",

            "gʻunajin" => "g'o'najin",

            "g\u02bbunajin" => "g'o'najin",

            "G'unajin" => "G'o'najin",

            "Gʻunajin" => "G'o'najin",

        ];



        return str_replace(array_keys($replacements), array_values($replacements), $text);

    }



    private function formatAnswer(string $text): string

    {

        $text = $this->fixTerminology($text);

        $escaped = e($text);

        $bold = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $escaped);

        return nl2br($bold);

    }



    private function getLanguageHint(): string

    {

        $locale = session('locale', 'uz');

        $localeName = match ($locale) {

            'ru' => "rus tilida",

            'uz_cyrl' => "o'zbek tilida (lekin KIRILL alifbosida)",

            default => "o'zbek tilida (lotin alifbosida)",

        };



        return "TIL QOIDASI: Javobni foydalanuvchi savolining tiliga mos ravishda yoz. Agar savol tilini aniq bilib bo'lmasa (masalan imlo xatolari yoki aralash so'zlar tufayli), {$localeName} javob ber. ";

    }



    /**

     * Joriy tanlangan til (session) asosida AI uchun til ko'rsatmasini beradi.

     */

    private function getLanguageInstruction(): string

    {

        $locale = session('locale', 'uz');



        return match ($locale) {

            'ru' => "0) ENG MUHIM QOIDA: Javobni FAQAT rus tilida yoz (na o'zbek, na boshqa tilda), garchi bu ko'rsatma o'zbek tilida yozilgan bo'lsa ham. ",

            'uz_cyrl' => "0) ENG MUHIM QOIDA: Javobni albatta o'zbek tilida, lekin KIRILL alifbosида yoz (масалан: 'Ассалому алайкум' каби). ",

            default => "0) Javobni o'zbek tilida, lotin alifbosida yoz. ",

        };

    }



    private function buildSystemPrompt(Animal $animal, array $relevantKnowledge): string

    {

        $directionText = match ($animal->direction) {

            'sut' => 'sut yo\'nalishidagi (sut sog\'ish uchun boqiladigan) chorva mol',

            'gosht' => 'go\'sht yo\'nalishidagi (go\'sht uchun boqiladigan) chorva mol',

            'nasl' => 'nasl yo\'nalishidagi (urchitish, zot ko\'paytirish uchun boqiladigan) chorva mol',

            default => 'chorva mol',

        };



        $region = auth()->user()->region;

        $regionText = $region

            ? "Foydalanuvchi {$region} viloyatidan. Agar shu hududda mahalliy/xalq tilida boshqacha nomlanadigan atama bo'lsa, ikkalasini ko'rsat."

            : '';



        $knowledgeText = '';

        if (!empty($relevantKnowledge)) {

            $knowledgeText = "\n\nQUYIDAGI TEKSHIRILGAN ILMIY MA'LUMOTLARGA ASOSLANIB JAVOB BER:\n";

            foreach ($relevantKnowledge as $entry) {

                $topic = $this->fixTerminology($entry->topic);

                $content = $this->fixTerminology($entry->content);

                $knowledgeText .= "- {$topic}: {$content}\n";

            }

        }



        return "Sen O'zbekistondagi chorvachilik bo'yicha tajribali maslahatchisan. "

            . "Foydalanuvchining {$directionText} bor, ismi \"{$animal->name}\", zoti: "

            . ($animal->breed ?? 'noma\'lum') . ". "

            . $this->getLanguageHint()
            . "MUHIM TIL QOIDALARI: "
            . "1) Faqat sof, adabiy tilda yoz. "

            . "2) O'zbekistonda haqiqatan ishlatiladigan aniq atamalarni ishlat: 'beda', 'yonuchqa', 'pichan', 'somon', 'silos', 'kepak', 'kombikorm' (tarjima qilinganda ham asl ma'nosini saqla). "

            . "3) 'ot' kabi chalkash so'zlarni ASLO ishlatma. "

            . "4) Mol yoshi/jinsiga oid atamalarni TO'G'RI ishlat: buzoq (kichik bola, jinsi muhim emas), g'o'najin (mol qiz farzandi), novvos (mol o'g'il farzandi), xo'kiz (voyaga yetgan erkak mol), sigir (ona mol). 'g'unajin' emas, albatta 'g'o'najin' deb yoz (agar o'zbek tilida yozayotgan bo'lsang). "

            . "5) {$regionText} "

            . "6) Murakkab ilmiy atamalarni (masalan DMI, BCS, NDF) ishlatsang, qavs ichida oddiy tilda tushuntir. "

            . "7) Oddiy dehqon tushunadigan sodda, aniq tilda yoz. "

            . "Javoblaring qisqa, aniq va amaliy bo'lsin."

            . $knowledgeText;

    }



    private function checkOwnership(Animal $animal)

    {

        $animalUserId = (int) $animal->user_id;

        $currentUserId = (int) auth()->id();



        if ($animalUserId !== $currentUserId) {

            abort(403, 'Sizga bu ma\'lumotni ko\'rish huquqi berilmagan.');

        }

    }

}

