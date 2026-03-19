<?php

namespace App\Http\Requests\AI;

use App\AI\DTOs\QuranChatRequestData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuranChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'question' => ['required', 'string', 'max:500'],
            'language' => ['nullable', 'string', Rule::in(['ur', 'en'])],
        ];
    }

    public function toDto(): QuranChatRequestData
    {
        return new QuranChatRequestData(
            question: trim((string) $this->input('question')),
            language: (string) ($this->input('language') ?: 'ur'),
        );
    }
}
