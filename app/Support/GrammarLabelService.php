<?php

namespace App\Support;

use App\Models\Morphology;

class GrammarLabelService
{
    protected const POS_ENGLISH = [
        'P' => 'preposition',
        'N' => 'noun',
        'PN' => 'proper noun',
        'ADJ' => 'adjective',
        'DET' => 'determiner',
        'V' => 'verb',
        'PRON' => 'pronoun',
        'CONJ' => 'conjunction',
        'REL' => 'relative pronoun',
        'NEG' => 'negative particle',
        'INTG' => 'interrogative particle',
        'COND' => 'conditional particle',
        'CERT' => 'particle of certainty',
        'RSLT' => 'result particle',
        'RES' => 'restriction particle',
        'EXP' => 'exception particle',
        'SUB' => 'subordinating particle',
        'REM' => 'resumption particle',
        'PREV' => 'preventive particle',
        'VOC' => 'vocative particle',
        'RET' => 'retraction particle',
        'CAUS' => 'causative particle',
        'CIRC' => 'circumstantial particle',
        'COM' => 'comitative particle',
        'AVR' => 'aversion particle',
        'FUT' => 'future particle',
        'EMPH' => 'emphatic particle',
        'EXH' => 'exhortation particle',
        'INC' => 'inceptive particle',
        'SUR' => 'surprise particle',
        'PRO' => 'prohibitive particle',
        'AMD' => 'amendment particle',
        'ANS' => 'answer particle',
        'EQ' => 'equalization particle',
        'INL' => 'Quranic initials',
        'INT' => 'interpretation particle',
        'SUP' => 'supplemental particle',
        'REP' => 'reply particle',
        'DEM' => 'demonstrative pronoun',
        'T' => 'time adverb',
        'LOC' => 'location adverb',
        'A' => 'particle',
        'ACC' => 'accusative particle',
        'PRP' => 'purpose particle',
        'IMPN' => 'imperative noun',
        'EXL' => 'explanation particle',
        'FORM' => 'formula',
        'Y' => 'vocative letter',
    ];

    protected const EXACT_ARABIC_BY_TAG = [
        'P' => 'حرف جر',
        'DET' => 'أداة تعريف',
        'CONJ' => 'حرف عطف',
        'INTG' => 'حرف استفهام',
        'COND' => 'حرف شرط',
        'CERT' => 'حرف تحقيق',
        'RSLT' => 'حرف جواب',
        'SUB' => 'حرف ربط',
        'REM' => 'حرف ابتداء',
        'VOC' => 'حرف نداء',
        'FUT' => 'حرف استقبال',
        'EMPH' => 'حرف تأكيد',
        'DEM' => 'اسم إشارة',
        'REL' => 'اسم موصول',
        'LOC' => 'ظرف مكان',
        'ACC' => 'حرف نصب',
        'PREV' => 'حرف كف',
        'RET' => 'حرف إضراب',
        'CAUS' => 'حرف سببية',
        'CIRC' => 'واو الحال',
        'COM' => 'واو المعية',
        'AVR' => 'حرف إضراب',
        'EXH' => 'حرف تحضيض',
        'INC' => 'حرف تنبيه',
        'SUR' => 'حرف فجاءة',
        'AMD' => 'حرف استدراك',
        'ANS' => 'حرف جواب',
        'EQ' => 'همزة التسوية',
        'INL' => 'حروف مقطعة',
        'INT' => 'حرف تفسير',
        'SUP' => 'حرف زائد',
        'REP' => 'حرف جواب',
        'PRP' => 'حرف تعليل',
        'IMPN' => 'اسم فعل أمر',
        'EXL' => 'حرف تفصيل',
        'FORM' => 'صيغة دعاء',
        'Y' => 'حرف نداء',
        'PRON' => 'ضمير',
        'V' => 'فعل',
    ];

    public static function englishLabel(Morphology $morphology): string
    {
        $features = MorphologyFeatureExtractor::for($morphology);

        return self::exactEnglishLabel($features) ?? self::defaultEnglishLabel($features);
    }

    public static function arabicLabel(Morphology $morphology): string
    {
        $features = MorphologyFeatureExtractor::for($morphology);

        return self::exactArabicLabel($features)
            ?? self::broadArabicLabel($features)
            ?? self::fallbackLabel($morphology);
    }

    public static function chipArabicLabel(Morphology $morphology): string
    {
        $features = MorphologyFeatureExtractor::for($morphology);

        if ($features->isVerb()) {
            return match ($features->tenseAspect()) {
                'imperative' => 'فعل أمر',
                'imperfect' => 'فعل مضارع',
                'perfect' => 'فعل ماض',
                default => 'فعل',
            };
        }

        return self::exactArabicLabel($features)
            ?? self::broadArabicLabel($features)
            ?? self::fallbackLabel($morphology);
    }

    public static function isFallback(Morphology $morphology): bool
    {
        $features = MorphologyFeatureExtractor::for($morphology);

        return self::exactArabicLabel($features) === null && self::broadArabicLabel($features) === null;
    }

    public static function fallbackLabel(Morphology $morphology): string
    {
        $features = MorphologyFeatureExtractor::for($morphology);

        return match ($features->segmentKind()) {
            'PREFIX' => 'سابقة صرفية',
            'SUFFIX' => 'لاحقة صرفية',
            default => 'تحليل صرفي',
        };
    }

    protected static function exactEnglishLabel(MorphologyFeatureExtractor $features): ?string
    {
        return match ($features->posTag()) {
            'CERT' => 'particle of certainty',
            'T' => $features->caseType() === 'ACC' ? 'accusative time adverb' : 'time adverb',
            'LOC' => 'location adverb',
            'EXP' => 'exception particle',
            'RES' => self::lemma($features) === '<il~aA' ? 'exception particle' : 'restriction particle',
            'NEG' => self::exactNegativeEnglishLabel($features),
            'PRO' => 'prohibitive particle',
            default => null,
        };
    }

    protected static function exactArabicLabel(MorphologyFeatureExtractor $features): ?string
    {
        return match ($features->posTag()) {
            'NEG' => self::exactNegativeArabicLabel($features),
            'PRO' => 'حرف نهي',
            'EXP' => 'أداة استثناء',
            'RES' => self::lemma($features) === '<il~aA' ? 'أداة استثناء' : 'أداة حصر',
            'T' => $features->caseType() === 'ACC' ? 'ظرف زمان منصوب' : 'ظرف زمان',
            'LOC' => 'ظرف مكان',
            'PN' => match ($features->caseType()) {
                'GEN' => 'اسم علم مجرور',
                'ACC' => 'اسم علم منصوب',
                'NOM' => 'اسم علم مرفوع',
                default => 'اسم علم',
            },
            'ADJ' => match ($features->caseType()) {
                'GEN' => 'صفة مجرورة',
                'ACC' => 'صفة منصوبة',
                'NOM' => 'صفة مرفوعة',
                default => 'صفة',
            },
            'N' => match ($features->caseType()) {
                'GEN' => 'اسم مجرور',
                'ACC' => 'اسم منصوب',
                'NOM' => 'اسم مرفوع',
                default => 'اسم',
            },
            default => self::EXACT_ARABIC_BY_TAG[$features->posTag()] ?? null,
        };
    }

    protected static function broadArabicLabel(MorphologyFeatureExtractor $features): ?string
    {
        return match ($features->posTag()) {
            'N' => 'اسم',
            'PN' => 'اسم علم',
            'ADJ' => 'صفة',
            'PRON' => 'ضمير',
            'V' => 'فعل',
            'T' => 'ظرف زمان',
            'LOC' => 'ظرف مكان',
            default => self::EXACT_ARABIC_BY_TAG[$features->posTag()] ?? null,
        };
    }

    protected static function exactNegativeArabicLabel(MorphologyFeatureExtractor $features): string
    {
        return match (self::lemma($features)) {
            'lam' => 'حرف نفي وجزم',
            'lan' => 'حرف نفي ونصب',
            default => 'حرف نفي',
        };
    }

    protected static function exactNegativeEnglishLabel(MorphologyFeatureExtractor $features): string
    {
        return match (self::lemma($features)) {
            'lam' => 'negative jussive particle',
            'lan' => 'negative accusative particle',
            default => 'negative particle',
        };
    }

    protected static function defaultEnglishLabel(MorphologyFeatureExtractor $features): string
    {
        $parts = [self::POS_ENGLISH[$features->posTag()] ?? strtolower($features->posTag())];

        if ($features->caseType() === 'GEN') {
            $parts[] = 'genitive';
        } elseif ($features->caseType() === 'ACC') {
            $parts[] = 'accusative';
        } elseif ($features->caseType() === 'NOM') {
            $parts[] = 'nominative';
        }

        if ($features->gender() === 'M') {
            $parts[] = 'masculine';
        } elseif ($features->gender() === 'F') {
            $parts[] = 'feminine';
        }

        if ($features->number() === 'S') {
            $parts[] = 'singular';
        } elseif ($features->number() === 'P') {
            $parts[] = 'plural';
        } elseif (in_array($features->number(), ['D', 'DUAL'], true)) {
            $parts[] = 'dual';
        }

        if ($features->mood() === 'IMPF') {
            $parts[] = 'imperfect';
        } elseif ($features->mood() === 'PERF') {
            $parts[] = 'perfect';
        } elseif ($features->mood() === 'IMPV') {
            $parts[] = 'imperative';
        }

        return implode(' - ', array_filter($parts));
    }

    protected static function lemma(MorphologyFeatureExtractor $features): ?string
    {
        return $features->lemma();
    }
}
