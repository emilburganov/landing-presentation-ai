<?php

namespace App\Services\AI\Enums;

enum Sentiment: string
{
    case Positive = 'positive';
    case Neutral = 'neutral';
    case Negative = 'negative';
    case Unknown = 'unknown';
}
