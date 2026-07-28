<?php

namespace App\Services\AI\Enums;

enum CommentType: string
{
    case Question = 'question';
    case Feedback = 'feedback';
    case Complaint = 'complaint';
    case General = 'general';
}
