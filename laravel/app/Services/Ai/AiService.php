<?php

namespace App\Services\Ai;

use App\Contracts\Ai\AiChat;
use App\DTOs\Ai\AiMessageDto;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/** `/chat/completions` d'une API compatible OpenAI (OpenAI, Mistral, Groq, OpenRouter, Ollama). */
class AiService implements AiChat
{
    public function chat(array $messages, ?string $modele = null): string
    {
        $apiKey = config('ai.api_key');

        if (! $apiKey) {
            throw new RuntimeException('AI_API_KEY is not configured.');
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout((int) config('ai.timeout', 30))
                ->baseUrl(config('ai.base_url'))
                ->post('/chat/completions', [
                    'model' => $modele ?? config('ai.model'),
                    'messages' => array_map(fn (AiMessageDto $m) => ['role' => $m->role, 'content' => $m->content], $messages),
                ])
                ->throw();
        } catch (ConnectionException|RequestException $e) {
            throw new RuntimeException('AI request failed: '.$e->getMessage(), 0, $e);
        }

        return $response->json('choices.0.message.content', '');
    }
}
