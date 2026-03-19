<?php

namespace App\Support;

use App\Models\Morphology;

class MorphologyFeatureExtractor
{
    public function __construct(protected Morphology $morphology)
    {
    }

    public static function for(Morphology $morphology): self
    {
        return new self($morphology);
    }

    public function morphology(): Morphology
    {
        return $this->morphology;
    }

    public function posTag(): string
    {
        return (string) $this->morphology->pos_tag;
    }

    public function rawFeatures(): string
    {
        return (string) $this->morphology->raw_features;
    }

    public function rawFeaturesUpper(): string
    {
        return strtoupper($this->rawFeatures());
    }

    public function hasRawFeature(string $needle): bool
    {
        return str_contains($this->rawFeaturesUpper(), strtoupper($needle));
    }

    public function isPrefix(): bool
    {
        return str_starts_with($this->rawFeaturesUpper(), 'PREFIX|');
    }

    public function isSuffix(): bool
    {
        return str_starts_with($this->rawFeaturesUpper(), 'SUFFIX|');
    }

    public function isStem(): bool
    {
        return ! $this->isPrefix() && ! $this->isSuffix();
    }

    public function segmentKind(): string
    {
        return match (true) {
            $this->isPrefix() => 'PREFIX',
            $this->isSuffix() => 'SUFFIX',
            default => 'STEM',
        };
    }

    public function isPronoun(): bool
    {
        return $this->posTag() === 'PRON';
    }

    public function isVerb(): bool
    {
        return $this->posTag() === 'V';
    }

    public function isNounLike(): bool
    {
        return in_array($this->posTag(), ['N', 'PN', 'ADJ', 'DEM', 'REL', 'LOC', 'T'], true);
    }

    public function isParticle(): bool
    {
        return in_array($this->posTag(), [
            'P', 'CONJ', 'INTG', 'COND', 'CERT', 'RSLT', 'SUB', 'REM', 'NEG', 'VOC', 'FUT', 'EMPH', 'EXCEPT',
        ], true);
    }

    public function isDeterminer(): bool
    {
        return $this->posTag() === 'DET';
    }

    public function isAttachedPronoun(): bool
    {
        return $this->isPronoun() && $this->isSuffix();
    }

    public function isDetachedPronoun(): bool
    {
        return $this->isPronoun() && $this->isStem();
    }

    public function pronounCode(): ?string
    {
        if (preg_match('/PRON:([^|]+)/', $this->rawFeatures(), $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    public function personCode(): ?string
    {
        if (preg_match('/\|([123][MFPDS][MPFS]?)$/', $this->rawFeatures(), $matches) === 1) {
            return $matches[1];
        }

        return $this->morphology->person ?: null;
    }

    public function person(): ?string
    {
        return $this->personCode();
    }

    public function gender(): ?string
    {
        return $this->morphology->gender ?: null;
    }

    public function number(): ?string
    {
        return $this->morphology->number_type ?: null;
    }

    public function caseType(): ?string
    {
        return $this->morphology->case_type ?: null;
    }

    public function mood(): ?string
    {
        if ($this->morphology->mood) {
            return $this->morphology->mood;
        }

        return match (true) {
            $this->hasRawFeature('|IMPF|') => 'IMPF',
            $this->hasRawFeature('|PERF|') => 'PERF',
            $this->hasRawFeature('|IMPV|') => 'IMPV',
            $this->hasRawFeature('|JUS') => 'JUS',
            $this->hasRawFeature('|IND') => 'IND',
            $this->hasRawFeature('|SUBJ') => 'SUBJ',
            default => null,
        };
    }

    public function tenseAspect(): ?string
    {
        return match ($this->mood()) {
            'PERF' => 'perfect',
            'IMPF' => 'imperfect',
            'IMPV' => 'imperative',
            default => null,
        };
    }

    public function voice(): ?string
    {
        if ($this->morphology->voice) {
            return $this->morphology->voice;
        }

        return match (true) {
            $this->hasRawFeature('|PASS') => 'PASS',
            $this->hasRawFeature('|ACT') => 'ACT',
            default => null,
        };
    }

    public function participleType(): ?string
    {
        if (! $this->hasRawFeature('|PCPL')) {
            return null;
        }

        return match (true) {
            $this->hasRawFeature('|PASS') => 'passive participle',
            $this->hasRawFeature('|ACT') => 'active participle',
            default => null,
        };
    }

    public function state(): ?string
    {
        return $this->morphology->state ?: null;
    }

    public function derivedForm(): ?string
    {
        return $this->morphology->derived_form ?: null;
    }

    public function lemma(): ?string
    {
        if ($this->morphology->lemma) {
            return $this->morphology->lemma;
        }

        if (preg_match('/LEM(?::|MA:)([^|]+)/', $this->rawFeatures(), $matches) === 1) {
            return trim(trim($matches[1]), "{} \t\n\r");
        }

        return null;
    }
}
