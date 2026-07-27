<?php

return [
    'provider' => env('AI_PROVIDER', 'groq'),

    'groq' => [
        'key' => env('GROQ_API_KEY'),
        'model' => env('AI_MODEL', 'llama3-8b-8192'),
        'url' => 'https://api.groq.com/openai/v1/chat/completions',
    ],
];
