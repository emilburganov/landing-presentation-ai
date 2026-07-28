<?php

namespace App\Services\Contact\Repositories;

use App\Models\Contact;
use App\Services\Contact\Analysis\CommentAnalysis;
use App\Services\Contact\DTO\ContactDTO;
use App\Services\Contact\DTO\ContactMetricsDTO;
use Illuminate\Support\Carbon;

readonly class EloquentContactRepository implements ContactRepositoryInterface
{
    public function create(ContactDTO $contact, CommentAnalysis $analysis): Contact
    {
        return Contact::query()->create([
            'name' => $contact->name,
            'phone' => $contact->phone,
            'email' => $contact->email,
            'comment' => $contact->comment,
            'sentiment' => $analysis->sentiment,
            'type' => $analysis->type,
            'auto_reply' => $analysis->autoReply,
            'ai_used' => $analysis->usedAi,
        ]);
    }

    public function metrics(): ContactMetricsDTO
    {
        $total = Contact::query()->count();

        $bySentiment = Contact::query()
            ->selectRaw('sentiment, COUNT(*) as aggregate')
            ->groupBy('sentiment')
            ->pluck('aggregate', 'sentiment')
            ->map(fn($count) => (int)$count)
            ->all();

        $byType = Contact::query()
            ->selectRaw('type, COUNT(*) as aggregate')
            ->groupBy('type')
            ->pluck('aggregate', 'type')
            ->map(fn($count) => (int)$count)
            ->all();

        $aiUsed = Contact::query()->where('ai_used', true)->count();

        $last24Hours = Contact::query()
            ->where('created_at', '>=', Carbon::now()->subDay())
            ->count();

        $last7Days = Contact::query()
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->count();

        return new ContactMetricsDTO(
            total: $total,
            bySentiment: $bySentiment,
            byType: $byType,
            aiUsed: $aiUsed,
            last24Hours: $last24Hours,
            last7Days: $last7Days,
        );
    }
}
