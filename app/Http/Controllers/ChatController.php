<?php

namespace App\Http\Controllers;

use App\Models\ChatDataset;
use App\Models\ChatHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class ChatController extends Controller
{
    public function index()
    {
        $chats = ChatHistory::where('user_id', Auth::id())
            ->orderBy('created_at', 'asc')
            ->get();

        return view('dashboard', compact('chats'));
    }

    public function sendMessage(Request $request)
    {
        $userMessage = trim($request->message);

        // 🔒 Check for prompt injection
        if ($this->isPromptInjection($userMessage)) {
            return response()->json([
                'bot_response' => "🚫 Unsafe or suspicious prompt detected. Please rephrase your message."
            ]);
        }

        // Process message
        $botReply = $this->generateBotReply($userMessage);

        return response()->json(['bot_response' => $botReply]);
    }

    // ------------------------------
    // 🔍 Detect Prompt Injection
    // ------------------------------
    private function isPromptInjection($text)
    {
        $patterns = [
            '/ignore\s+previous\s+instructions/i',
            '/system:/i',
            '/jailbreak/i',
            '/hack/i',
            '/naked/i',
            '/bypass/i',
            '/disregard\s+rules/i',
            '/reset\s+the\s+rules/i',
            '/you\s+are\s+no\s+longer/i',
            '/act\s+as\s+an\s+evil/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }
        return false;
    }

    // ------------------------------
    // 🤖 Main Reply Generator
    // ------------------------------
    private function generateBotReply($input)
    {
        $inputLower = strtolower($input);
        $botReply = null;

        // 1️⃣ Check Local JSON Dataset
        $path = storage_path('app/chat_dataset.json');
        if (file_exists($path)) {
            $data = json_decode(file_get_contents($path), true);

            foreach ($data as $item) {
                if (str_contains($inputLower, strtolower($item['keyword']))) {
                    $botReply = $item['response'];
                    break;
                }
            }
        }

        // 2️⃣ Check Database Dataset
        if (!$botReply) {
            $datasets = ChatDataset::all();
            foreach ($datasets as $data) {
                if (str_contains($inputLower, strtolower($data->keyword))) {
                    $botReply = $data->response;
                    break;
                }
            }
        }

        // 3️⃣ Fallback → HuggingFace AI
        if (!$botReply) {
            $botReply = $this->getBotResponseFromHuggingFace($input);
        }

        return $botReply ?? "🤖 Sorry, I didn’t quite understand that.";
    }

    // ------------------------------
    // 🤖 HuggingFace Chat Endpoint
    // ------------------------------
    private function getBotResponseFromHuggingFace($message)
    {
        $apiKey = config('services.huggingface.key');

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post("https://api.huggingface.co/v1/chat/completions", [
                "model" => "meta-llama/Llama-3-8b-chat-hf",
                "messages" => [
                    ["role" => "user", "content" => $message]
                ],
                "max_tokens" => 200
            ]);

            if ($response->failed()) {
                return "⚠️ Hugging Face Error: " . $response->body();
            }

            $json = $response->json();

            return $json['choices'][0]['message']['content']
                ?? "🤖 No response from AI.";
        } catch (\Exception $e) {
            return "⚠️ API Error: " . $e->getMessage();
        }
    }
}
