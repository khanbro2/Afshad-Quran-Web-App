<?php

namespace App\Support;

class BilingualGrammarService
{
    public static function label(string $english): string
    {
        return self::combine($english, config('grammar.labels.'.$english));
    }

    public static function heading(string $arabic): string
    {
        return config('grammar.headings.'.$arabic, $arabic);
    }

    public static function term(string $english): string
    {
        return self::combine($english, config('grammar.terms.'.$english));
    }

    public static function pronounRole(string $role): array
    {
        return config('grammar.pronoun_roles.'.$role, [
            'english' => $role.' pronoun',
            'urdu' => $role,
            'arabic' => $role,
        ]);
    }

    public static function abbreviation(?string $code): string
    {
        if ($code === null || $code === '') {
            return 'N/A';
        }

        return self::combine($code, config('grammar.abbreviations.'.$code));
    }

    public static function combine(string $english, ?string $urdu): string
    {
        return $urdu ? $english.' / '.$urdu : $english;
    }

    public static function urduForTerm(string $english): string
    {
        return config('grammar.terms.'.$english, $english);
    }

    public static function urduForLabel(string $english): string
    {
        return config('grammar.labels.'.$english, $english);
    }

    public static function urduForArabicLabel(string $arabic): string
    {
        return config('grammar.exact_labels.'.$arabic)
            ?? config('grammar.chip_labels.'.$arabic)
            ?? config('grammar.fallback_labels.'.$arabic)
            ?? config('grammar.headings.'.$arabic)
            ?? $arabic;
    }
}
