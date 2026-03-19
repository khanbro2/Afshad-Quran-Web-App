<?php

namespace App\Support;

use App\Models\Morphology;

class MorphologyCardColorService
{
    protected const PALETTE = [
        'teal' => '#0f766e',
        'green' => '#15803d',
        'blue' => '#2563eb',
        'purple' => '#7c3aed',
        'amber' => '#b45309',
        'rose' => '#be123c',
        'neutral' => '#334155',
    ];

    public static function cardClasses(string $posTag): string
    {
        return match ($posTag) {
            'P' => 'border-emerald-200 bg-emerald-50/70',
            'N' => 'border-sky-200 bg-sky-50/70',
            'PN' => 'border-teal-200 bg-teal-50/70',
            'ADJ' => 'border-violet-200 bg-violet-50/70',
            'DET' => 'border-amber-200 bg-amber-50/70',
            'V' => 'border-rose-200 bg-rose-50/70',
            'PRON' => 'border-pink-200 bg-pink-50/70',
            'CONJ' => 'border-orange-200 bg-orange-50/70',
            'REL' => 'border-indigo-200 bg-indigo-50/70',
            'NEG' => 'border-stone-300 bg-stone-100',
            default => 'border-stone-200 bg-stone-50',
        };
    }

    public static function chipClasses(string $posTag): string
    {
        return match ($posTag) {
            'P' => 'bg-emerald-100 text-emerald-800',
            'N' => 'bg-sky-100 text-sky-800',
            'PN' => 'bg-teal-100 text-teal-800',
            'ADJ' => 'bg-violet-100 text-violet-800',
            'DET' => 'bg-amber-100 text-amber-800',
            'V' => 'bg-rose-100 text-rose-800',
            'PRON' => 'bg-pink-100 text-pink-800',
            'CONJ' => 'bg-orange-100 text-orange-800',
            'REL' => 'bg-indigo-100 text-indigo-800',
            'NEG' => 'bg-stone-200 text-stone-800',
            default => 'bg-stone-100 text-stone-700',
        };
    }

    public static function wordSegmentClasses(?Morphology $morphology, string $segmentKind, int $variant = 0): string
    {
        if ($morphology === null) {
            return 'text-sky-700';
        }

        if ($segmentKind === 'PREFIX') {
            return $morphology->pos_tag === 'P' ? 'text-emerald-700' : 'text-teal-700';
        }

        if ($segmentKind === 'SUFFIX') {
            return match ($variant % 3) {
                1 => 'text-amber-700',
                2 => 'text-rose-700',
                default => 'text-violet-700',
            };
        }

        return 'text-sky-700';
    }

    public static function compactChipClasses(Morphology $morphology): string
    {
        return match (self::wordPaletteKey($morphology, SegmentClassifier::kind($morphology))) {
            'teal' => 'border-teal-200 bg-teal-50 text-teal-800',
            'green' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
            'blue' => 'border-sky-200 bg-sky-50 text-sky-800',
            'purple' => 'border-violet-200 bg-violet-50 text-violet-800',
            default => 'border-stone-200 bg-stone-50 text-stone-700',
        };
    }

    public static function wordGradientStyle(array $segments): string
    {
        if ($segments === []) {
            return 'color: '.self::PALETTE['neutral'].';';
        }

        $totalLength = array_sum(array_map(
            fn (array $segment) => mb_strlen((string) $segment['text'], 'UTF-8'),
            $segments
        ));

        if ($totalLength <= 0) {
            return 'color: '.self::PALETTE['neutral'].';';
        }

        $stops = [];
        $current = 0.0;

        foreach ($segments as $segment) {
            $length = mb_strlen((string) $segment['text'], 'UTF-8');
            $ratio = ($length / $totalLength) * 100;
            $next = min(100, $current + $ratio);
            $color = self::segmentHex(
                $segment['morphology'] ?? null,
                $segment['kind'] ?? 'STEM',
                $segment['variant'] ?? 0
            );

            $stops[] = sprintf('%s %.4f%%, %s %.4f%%', $color, $current, $color, $next);
            $current = $next;
        }

        if (count($segments) === 1) {
            return 'color: '.self::segmentHex(
                $segments[0]['morphology'] ?? null,
                $segments[0]['kind'] ?? 'STEM',
                $segments[0]['variant'] ?? 0
            ).';';
        }

        return 'background-image: linear-gradient(to left, '.implode(', ', $stops).'); color: transparent; -webkit-background-clip: text; background-clip: text;';
    }

    protected static function segmentHex(?Morphology $morphology, string $segmentKind, int $variant = 0): string
    {
        return self::PALETTE[self::wordPaletteKey($morphology, $segmentKind, $variant)] ?? self::PALETTE['neutral'];
    }

    protected static function wordPaletteKey(?Morphology $morphology, string $segmentKind, int $variant = 0): string
    {
        if ($segmentKind === 'SUFFIX') {
            return match ($variant % 3) {
                1 => 'amber',
                2 => 'rose',
                default => 'purple',
            };
        }

        if ($segmentKind === 'PREFIX') {
            return $morphology?->pos_tag === 'P' ? 'green' : 'teal';
        }

        return 'blue';
    }

    protected static function segmentKind(Morphology $morphology): string
    {
        $raw = strtoupper((string) $morphology->raw_features);

        return SegmentClassifier::kind($morphology);
    }
}
