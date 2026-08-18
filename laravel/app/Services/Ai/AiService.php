<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AiService
{
    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    public function chat(array $messages, array $options = []): string
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
                    'model' => $options['model'] ?? config('ai.model'),
                    'messages' => $messages,
                ])
                ->throw();
        } catch (ConnectionException|RequestException $e) {
            throw new RuntimeException('AI request failed: '.$e->getMessage(), 0, $e);
        }

        return $response->json('choices.0.message.content', '');
    }
}
