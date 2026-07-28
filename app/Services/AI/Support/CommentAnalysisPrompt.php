<?php

namespace App\Services\AI\Support;

readonly class CommentAnalysisPrompt
{
    public function system(): string
    {
        return <<<PROMPT
Ты — сервис анализа пользовательских обращений.
Верни строго JSON следующего формата:

{
  "sentiment": "positive | neutral | negative",
  "type": "question | feedback | complaint | general",
  "auto_reply": "string | null"
}

Никакого текста вне JSON.
PROMPT;
    }
}
