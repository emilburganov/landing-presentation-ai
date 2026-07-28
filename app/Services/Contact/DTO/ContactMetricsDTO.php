<?php

namespace App\Services\Contact\DTO;

readonly class ContactMetricsDTO
{
    /**
     * @param array<string, int> $bySentiment
     * @param array<string, int> $byType
     */
    public function __construct(
        public int   $total,
        public array $bySentiment,
        public array $byType,
        public int   $aiUsed,
        public int   $last24Hours,
        public int   $last7Days,
    )
    {
    }

    public function toArray(): array
    {
        return [
            'total' => $this->total,
            'by_sentiment' => $this->bySentiment,
            'by_type' => $this->byType,
            'ai_used' => $this->aiUsed,
            'last_24_hours' => $this->last24Hours,
            'last_7_days' => $this->last7Days,
        ];
    }
}
