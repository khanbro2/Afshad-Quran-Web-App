<?php

namespace App\Http\Requests\AI;

use App\AI\DTOs\AIRequestData;
use App\AI\Enums\AIAssistantMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AyahExplainRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ayah_id' => ['required', 'integer', 'exists:ayahs,id'],
            'language' => ['required', 'string', Rule::in(['ur', 'en'])],
            'mode' => ['nullable', 'string', Rule::in(['simple', 'deep'])],
            'translation_slugs' => ['nullable', 'array'],
            'translation_slugs.*' => ['string'],
            'tafsir_slugs' => ['nullable', 'array'],
            'tafsir_slugs.*' => ['string'],
            'question' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function toDto(): AIRequestData
    {
        return new AIRequestData(
            ayahId: (int) $this->integer('ayah_id'),
            language: (string) $this->string('language'),
            mode: AIAssistantMode::from((string) ($this->input('mode') ?: 'simple')),
            translationSlugs: array_values($this->input('translation_slugs', [])),
            tafsirSlugs: array_values($this->input('tafsir_slugs', [])),
            question: $this->filled('question') ? (string) $this->input('question') : null,
        );
    }
}
