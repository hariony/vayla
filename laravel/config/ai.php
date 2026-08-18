<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Provider (OpenAI-compatible)
    |--------------------------------------------------------------------------
    |
    | Pointe vers n'importe quelle API compatible avec le format
    | "/chat/completions" d'OpenAI (OpenAI, Mistral, Groq, OpenRouter,
    | Ollama en mode compatible...). Changer AI_BASE_URL suffit à changer
    | de fournisseur.
    |
    */

    'base_url' => env('AI_BASE_URL', 'https://api.openai.com/v1'),

    'api_key' => env('AI_API_KEY'),

    'model' => env('AI_MODEL', 'gpt-4o-mini'),

    'timeout' => env('AI_TIMEOUT', 30),

];
