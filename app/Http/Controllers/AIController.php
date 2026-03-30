<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIController extends Controller
{
    public function generateIntro(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string|max:500',
            'style' => 'required|string|in:dramatic,lyrical,business,mystery'
        ]);

        $apiKey = env('OPENROUTER_API_KEY');
        if (!$apiKey) {
            return response()->json(['message' => 'AI Service not configured'], 500);
        }

        $stylePrompts = [
            'dramatic' => 'Ты — профессиональный писатель. Напиши захватывающее, драматичное и атмосферное вступление (2-3 предложения) на основе идеи пользователя. Используй сильные образы и глубокие эмоции.',
            'lyrical' => 'Ты — профессиональный писатель. Напиши лиричное, трогательное и поэтичное вступление (2-3 предложения). Сосредоточься на чувствах, воспоминаниях и красоте момента.',
            'business' => 'Ты — экспертный автор нон-фикшн. Напиши профессиональное, четкое и вдохновляющее вступление (2-3 предложения) для деловой или обучающей книги.',
            'mystery' => 'Ты — мастер триллеров и детективов. Напиши загадочное, пугающее или интригующее вступление (2-3 предложения), которое сразу заставит читателя искать ответы.'
        ];

        $systemPrompt = $stylePrompts[$request->style] . ' Пиши СТРОГО на русском языке. Кратко, без лишних вступлений и приветствий. Только сам текст начала книги.';

        try {
            $response = Http::withoutVerifying()->withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'HTTP-Referer' => config('app.url'),
                'X-Title' => 'BioBook App',
            ])->timeout(30)->post('https://openrouter.ai/api/v1/chat/completions', [
                'model' => 'google/gemini-2.0-flash-001',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => "Идея книги: " . $request->prompt],
                ],
                'temperature' => 0.7,
                'max_tokens' => 500,
            ]);

            if ($response->successful()) {
                $content = $response->json('choices.0.message.content');
                if (!$content) {
                    Log::error('OpenRouter Empty Content: ' . $response->body());
                    return response()->json(['message' => 'ИИ вернул пустой ответ'], 500);
                }
                return response()->json([
                    'status' => 'ok',
                    'text' => trim($content)
                ]);
            }

            $errorMsg = $response->json('error.message') ?? 'Ошибка генерации текста';
            Log::error('OpenRouter Error Status ' . $response->status() . ': ' . $response->body());
            return response()->json(['message' => $errorMsg], 500);

        } catch (\Exception $e) {
            Log::error('AI Generation Exception: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json(['message' => 'Не удалось связаться с ИИ: ' . $e->getMessage()], 500);
        }
    }
}
