<?php

namespace App\AI\QuranChat\Contracts;

use App\AI\DTOs\QuranChatRequestData;
use App\AI\DTOs\QuranChatResponseData;
use App\AI\QuranChat\DTOs\QuestionIntentData;

interface QuranChatHandler
{
    public function supports(QuestionIntentData $intent): bool;

    /**
     * @param  array<string, mixed>  $evidence
     */
    public function handle(QuranChatRequestData $request, QuestionIntentData $intent, array $evidence): QuranChatResponseData;
}
