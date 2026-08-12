<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;

/**
 * AiChatController
 *
 * Fermerlarning savollariga OpenAI GPT-4o-mini modeli orqali
 * real vaqtda javob beruvchi AI yordamchi.
 *
 * Chorvadorim.uz platformasining sun'iy intellekt qatlami.
 */
class AiChatController extends Controller
{
    protected string $model = 'gpt-4o-mini';

    /**
     * Foydalanuvchi xabarini qabul qilib, AI javobini qaytaradi.
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'image'   => 'nullable|image|max:5120', // vizual tahlil uchun (max 5MB)
        ]);

        $user = Auth::user();
        $userMessage = $request->input('message');

        // Agar rasm yuklangan bo'lsa - vizual tahlil rejimiga o'tamiz
        $imageUrl = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('ai-uploads', 'public');
            $imageUrl = asset('storage/' . $path);
        }

        $messages = $this->buildContext($user, $userMessage, $imageUrl);

        $response = $this->callOpenAi($messages);

        // Suhbat tarixini saqlaymiz - keyingi savollarga kontekst uchun
        ChatMessage::create([
            'user_id'  => $user->id,
            'question' => $userMessage,
            'answer'   => $response,
            'has_image'=> (bool) $imageUrl,
        ]);

        return response()->json([
            'success' => true,
            'answer'  => $response,
        ]);
    }

    /**
     * Fermer profili va oldingi suhbatlar asosida kontekst yig'amiz,
     * shunda AI javoblari shaxsiylashtirilgan bo'ladi.
     */
    protected function buildContext($user, string $message, ?string $imageUrl): array
    {
        $systemPrompt = "Siz Chorvadorim.uz platformasining AI yordamchisisiz. "
            . "O'zbek fermerlariga chorva mollarini parvarish qilish, vaksinatsiya "
            . "jadvali va moliyaviy hisob-kitob bo'yicha aniq va tushunarli maslahat bering. "
            . "Javoblaringiz qisqa, amaliy va o'zbek tilida bo'lsin.";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        // So'nggi 5 ta xabarni kontekstga qo'shamiz (uzun tarixni yubormaslik uchun)
        $recentHistory = ChatMessage::where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get()
            ->reverse();

        foreach ($recentHistory as $item) {
            $messages[] = ['role' => 'user', 'content' => $item->question];
            $messages[] = ['role' => 'assistant', 'content' => $item->answer];
        }

        if ($imageUrl) {
            $messages[] = [
                'role' => 'user',
                'content' => [
                    ['type' => 'text', 'text' => $message],
                    ['type' => 'image_url', 'image_url' => ['url' => $imageUrl]],
                ],
            ];
        } else {
            $messages[] = ['role' => 'user', 'content' => $message];
        }

        return $messages;
    }

    /**
     * OpenAI API'ga so'rov yuboradi.
     */
    protected function callOpenAi(array $messages): string
    {
        $response = Http::withToken(config('services.openai.key'))
            ->timeout(30)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model'    => $this->model,
                'messages' => $messages,
                'max_tokens' => 500,
                'temperature' => 0.6,
            ]);

        if ($response->failed()) {
            report('OpenAI API xatosi: ' . $response->body());
            return "Kechirasiz, hozir javob bera olmayapman. Birozdan so'ng qayta urinib ko'ring.";
        }

        return $response->json('choices.0.message.content') ?? 'Javob olishda xatolik yuz berdi.';
    }
}
