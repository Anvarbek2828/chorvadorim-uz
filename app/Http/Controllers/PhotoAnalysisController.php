<?php



namespace App\Http\Controllers;



use App\Models\AiUsageLog;

use App\Models\Animal;

use App\Models\AnimalPhotoAnalysis;

use App\Models\KnowledgeBase;

use App\Services\AiUsageService;

use App\Services\OpenAiChat;

use App\Services\OpenAiException;

use App\Services\VisionImage;

use Illuminate\Http\Request;

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

        $farm = $this->currentFarm();

        // Limit oshgan bo'lsa rasm saqlanmaydi va AI chaqirilmaydi
        if ($denied = app(AiUsageService::class)->denyReason($farm, AiUsageLog::TYPE_VISION, $animal)) {
            return back()->with('error', $denied['message'])->with('show_plans_link', $denied['upgrade']);
        }



        $path = $request->file('photo')->store('animal-photos', 'public');

        $fullPath = Storage::disk('public')->path($path);



        // Token tejash uchun rasm yuborishdan oldin kichraytiriladi (saqlangan asl fayl o'zgarmaydi)
        $image = VisionImage::encode($fullPath, $request->file('photo')->getMimeType(), (int) config('ai.vision_max_side', 1024));

        $imageData = $image['data'];

        $mimeType = $image['mime'];



        $systemPrompt = $this->buildAnalysisPrompt($animal);



        // Model ferma tarifidan olinadi
        $model = $farm->activePlan()->aiModel(AiUsageLog::TYPE_VISION);

        $messages = [

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

        ];

        try {
            $data = app(OpenAiChat::class)->complete(
                $model,
                $messages,
                (int) config('ai.vision_max_tokens', 600),
                0.3,
                ['response_format' => ['type' => 'json_object']],
                60
            );
        } catch (OpenAiException $e) {
            Storage::disk('public')->delete($path);

            return back()->with('error', $e->userMessage('Rasmni tahlil qilishda xatolik yuz berdi. Birozdan so\'ng qayta urinib ko\'ring.'));
        }

        app(AiUsageService::class)->record(auth()->user(), $farm, $animal, AiUsageLog::TYPE_VISION, $data, $model);



        $raw = data_get($data, 'choices.0.message.content');

        $parsed = is_string($raw) ? json_decode($raw, true) : null;



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



        if ((int) $analysis->animal_id !== (int) $animal->id) {

            abort(404);

        }



        $validated = $request->validate([

            'question' => 'required|string|max:1000',

        ]);

        $farm = $this->currentFarm();

        if ($denied = app(AiUsageService::class)->denyReason($farm, AiUsageLog::TYPE_CHAT, $animal)) {
            return back()->withInput()->with('error', $denied['message'])->with('show_plans_link', $denied['upgrade']);
        }



        $relevantKnowledge = $this->findRelevantKnowledge($validated['question'], $animal->direction);

        $systemPrompt = $this->buildQuestionPrompt($animal, $analysis, $relevantKnowledge);



        // Model ferma tarifidan olinadi
        $model = $farm->activePlan()->aiModel(AiUsageLog::TYPE_CHAT);

        $messages = [

            ['role' => 'system', 'content' => $systemPrompt],

            ['role' => 'user', 'content' => $validated['question']],

        ];

        try {
            $data = app(OpenAiChat::class)->complete($model, $messages, (int) config('ai.chat_max_tokens', 800), 0.7);
        } catch (OpenAiException $e) {
            return back()->with('error', $e->userMessage('AI bilan bog\'lanishda xatolik yuz berdi. Birozdan so\'ng qayta urinib ko\'ring.'));
        }

        app(AiUsageService::class)->record(auth()->user(), $farm, $animal, AiUsageLog::TYPE_CHAT, $data, $model);



        $answer = data_get($data, 'choices.0.message.content') ?: 'Javob olishda muammo yuz berdi.';

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



        if ((int) $analysis->animal_id !== (int) $animal->id) {

            abort(404);

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

        $this->ensureAnimalInFarm($animal);

    }

}

