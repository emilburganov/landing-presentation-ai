<?php

return [
    'provider' => env('AI_PROVIDER', 'groq'),

    'groq' => [
        'key' => env('GROQ_API_KEY'),
        'model' => env('AI_MODEL', 'openai/gpt-oss-120b'),
        'url' => 'https://api.groq.com/openai/v1/chat/completions',
    ],
];
