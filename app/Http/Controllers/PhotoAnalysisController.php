<?php



namespace App\Http\Controllers;



use App\Models\Animal;

use App\Models\AnimalPhotoAnalysis;

use App\Models\KnowledgeBase;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Http;

use Illuminate\Support\Facades\Storage;



class PhotoAnalysisController extends Controller

{

    public function index(Animal $animal)

    {

        $this->checkOwnership($animal);



        $analyses = $animal->photoAnalyses()->with('chatMessages')->get();



        return view('photo-analysis.index', compact('animal', 'analyses'));

    }



    public function store(Request $request, Animal $animal)

    {

        $this->checkOwnership($animal);



        $validated = $request->validate([

            'photo' => 'required|image|mimes:jpeg,jpg,png,webp|max:8192',

        ]);



        $path = $request->file('photo')->store('animal-photos', 'public');

        $fullPath = Storage::disk('public')->path($path);



        $imageData = base64_encode(file_get_contents($fullPath));

        $mimeType = $request->file('photo')->getMimeType();



        $systemPrompt = $this->buildAnalysisPrompt($animal);



        $response = Http::withToken(config('services.openai.api_key'))

            ->timeout(60)

            ->post('https://api.openai.com/v1/chat/completions', [

                'model' => 'gpt-4o-mini',

                'messages' => [

                    ['role' => 'system', 'content' => $systemPrompt],

                    [

                        'role' => 'user',

                        'content' => [

                            ['type' => 'text', 'text' => 'Ushbu hayvon rasmini tahlil qil va JSON formatida javob ber.'],

                            ['type' => 'image_url', 'image_url' => [

                                'url' => "data:{$mimeType};base64,{$imageData}",

                            ]],

                        ],

                    ],

                ],

                'temperature' => 0.3,

                'response_format' => ['type' => 'json_object'],

            ]);



        if ($response->failed()) {

            Storage::disk('public')->delete($path);

            return back()->with('error', 'Rasmni tahlil qilishda xatolik yuz berdi. Birozdan so\'ng qayta urinib ko\'ring.');

        }



        $raw = $response->json('choices.0.message.content');

        $parsed = json_decode($raw, true);



        if (!is_array($parsed)) {

            Storage::disk('public')->delete($path);

            return back()->with('error', 'AI javobini o\'qib bo\'lmadi. Qayta urinib ko\'ring.');

        }



        AnimalPhotoAnalysis::create([

            'animal_id' => $animal->id,

            'photo_path' => $path,

            'bcs_score' => $parsed['bcs_score'] ?? null,

            'estimated_weight' => $parsed['estimated_weight'] ?? null,

            'ai_summary' => $parsed['summary'] ?? null,

            'ai_raw_response' => $parsed,

        ]);



        return redirect()->route('photo-analysis.index', $animal)

            ->with('success', 'Rasm muvaffaqiyatli tahlil qilindi.');

    }



    public function askQuestion(Request $request, Animal $animal, AnimalPhotoAnalysis $analysis)

    {

        $this->checkOwnership($animal);



        if ($analysis->animal_id !== $animal->id) {

            abort(403);

        }



        $validated = $request->validate([

            'question' => 'required|string|max:1000',

        ]);



        $relevantKnowledge = $this->findRelevantKnowledge($validated['question'], $animal->direction);

        $systemPrompt = $this->buildQuestionPrompt($animal, $analysis, $relevantKnowledge);



        $response = Http::withToken(config('services.openai.api_key'))

            ->timeout(30)

            ->post('https://api.openai.com/v1/chat/completions', [

                'model' => 'gpt-4o-mini',

                'messages' => [

                    ['role' => 'system', 'content' => $systemPrompt],

                    ['role' => 'user', 'content' => $validated['question']],

                ],

                'temperature' => 0.7,

            ]);



        if ($response->failed()) {

            return back()->with('error', 'AI bilan bog\'lanishda xatolik yuz berdi. Birozdan so\'ng qayta urinib ko\'ring.');

        }



        $answer = $response->json('choices.0.message.content') ?? 'Javob olishda muammo yuz berdi.';

        $formattedAnswer = $this->formatAnswer($answer);



        $animal->chatMessages()->create([

            'photo_analysis_id' => $analysis->id,

            'question' => $validated['question'],

            'answer' => $formattedAnswer,

        ]);



        return redirect()->route('photo-analysis.index', $animal)->with('success', 'Savolingizga javob berildi.');

    }



    public function destroy(Animal $animal, AnimalPhotoAnalysis $analysis)

    {

        $this->checkOwnership($animal);



        if ($analysis->animal_id !== $animal->id) {

            abort(403);

        }



        Storage::disk('public')->delete($analysis->photo_path);

        $analysis->delete();



        return back()->with('success', 'Tahlil o\'chirildi.');

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



    private function fixTerminology(string $text): string

    {

        $replacements = [

            "g'unajin" => "g'o'najin",

            "gʻunajin" => "g'o'najin",

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

        return "TIL QOIDASI: Javobni foydalanuvchi savolining tiliga mos ravishda yoz. Agar savol tilini aniq bilib bo'lmasa, {$localeName} javob ber. ";
    }



    /**

     * Joriy tanlangan til (session) asosida AI uchun til ko'rsatmasini beradi.

     */

    private function getLanguageInstruction(): string

    {

        $locale = session('locale', 'uz');



        return match ($locale) {

            'ru' => "MUHIM: Javobni FAQAT rus tilida yoz. ",

            'uz_cyrl' => "MUHIM: Javobni albatta o'zbek tilida, lekin KIRILL alifbosida yoz. ",

            default => "Javobni o'zbek tilida, lotin alifbosida yoz. ",

        };

    }



    private function buildAnalysisPrompt(Animal $animal): string

    {

        $directionText = match ($animal->direction) {

            'sut' => 'sut yo\'nalishidagi',

            'gosht' => 'go\'sht yo\'nalishidagi',

            'nasl' => 'nasl yo\'nalishidagi',

            default => '',

        };



        $ageText = $animal->birth_date

            ? $animal->birth_date->diffForHumans(now(), true) . ' yoshli'

            : 'yoshi noma\'lum';



        return "Sen tajribali zootexnik-mutaxassissan. Senga {$directionText} qoramol rasmi beriladi. "

            . "Hayvon: \"{$animal->name}\", zoti: " . ($animal->breed ?? 'noma\'lum') . ", {$ageText}. "
            . "Rasmga qarab quyidagi ko'rsatkichlarni tahlil qil:\n"

            . "1) Tana holati bahosi (BCS) — 1 dan 5 gacha, 0.5 qadam bilan.\n"

            . "2) Taxminiy tirik vazn (kg).\n"

            . "3) Qisqa xulosa (2-3 gap) — tanlangan tilda.\n"
            . $this->getLanguageHint()
            . "MUHIM: Bu faqat rasmga asoslangan TAXMINIY baho, aniq o'lchov emasligini yodda tut.\n"

            . "Javobni FAQAT quyidagi JSON formatida ber, 'summary' matni tanlangan tilda bo'lsin:\n"

            . '{"bcs_score": <son>, "estimated_weight": <son>, "summary": "<matn>"}';

    }



    private function buildQuestionPrompt(Animal $animal, AnimalPhotoAnalysis $analysis, array $relevantKnowledge): string

    {

        $directionText = match ($animal->direction) {

            'sut' => 'sut yo\'nalishidagi',

            'gosht' => 'go\'sht yo\'nalishidagi',

            'nasl' => 'nasl yo\'nalishidagi',

            default => '',

        };



        $knowledgeText = '';

        if (!empty($relevantKnowledge)) {

            $knowledgeText = "\n\nQUYIDAGI TEKSHIRILGAN ILMIY MA'LUMOTLARGA ASOSLANIB JAVOB BER:\n";

            foreach ($relevantKnowledge as $entry) {

                $topic = $this->fixTerminology($entry->topic);

                $content = $this->fixTerminology($entry->content);

                $knowledgeText .= "- {$topic}: {$content}\n";

            }

        }



        return "Sen O'zbekistondagi chorvachilik bo'yicha tajribali zootexnik-maslahatchisan. "

            . "Foydalanuvchining {$directionText} \"{$animal->name}\" ismli hayvoni bor. "

            . "Ushbu hayvonning yaqinda tahlil qilingan rasmi natijasi: "

            . "Tana holati bahosi (BCS): " . ($analysis->bcs_score ?? 'noma\'lum') . "/5, "

            . "Taxminiy vazn: " . ($analysis->estimated_weight ?? 'noma\'lum') . " kg. "

            . "AI xulosasi: \"" . ($analysis->ai_summary ?? '') . "\". "
            . $this->getLanguageHint()
            . "Foydalanuvchi aynan shu tahlil natijasi bo'yicha savol beryapti. "
            . "Javobni shu kontekstga bog'lab, sodda va amaliy tilda ber. Murakkab atamalarni tushuntirib ber."

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

