<?php

namespace App\Support;

use App\Models\Morphology;
use Illuminate\Support\Collection;

class MorphologyAnalysisService
{
    /**
     * @param  Collection<int, Morphology>  $analysisMorphologies
     * @return array{
     *   english:string,
     *   urdu:string,
     *   arabic:string,
     *   lemma:string,
     *   root:string,
     *   details:string,
     *   exact_arabic_label:string,
     *   chip_arabic_label:string,
     *   chip_urdu_label:string,
     *   chip_english_label:string,
     *   segment_kind:string,
     *   fallback:array{used:bool,label:string},
     *   attributes: array<int, array{label:string,value:string}>,
     *   pronoun:array{
     *     type:string,
     *     role:string,
     *     english_label_key:string,
     *     arabic_exact_label:string,
     *     urdu_label_key:string,
     *     confidence:string,
     *     reason:string
     *   }|null,
     *   verb:array{
     *     tense_aspect:?string,
     *     mood:?string,
     *     voice:?string,
     *     derived_form:?string,
     *     person:?string,
     *     gender:?string,
     *     number:?string
     *   }|null
     * }
     */
    public static function analyze(Morphology $morphology, Collection $analysisMorphologies, int $index): array
    {
        $features = MorphologyFeatureExtractor::for($morphology);
        $segment = SegmentClassifier::metadata($morphology);
        $pronoun = $features->isPronoun()
            ? PronounClassifier::classify($morphology, $analysisMorphologies, $index)
            : null;

        $exactArabic = $pronoun['arabic_exact_label'] ?? GrammarLabelService::arabicLabel($morphology);
        $chipArabic = $pronoun !== null
            ? ($pronoun['type'] === 'detached' ? 'ضمير منفصل' : 'ضمير متصل')
            : GrammarLabelService::chipArabicLabel($morphology);

        $english = self::englishExplanation($morphology, $features, $pronoun);
        $urdu = self::urduExplanation($morphology, $features, $pronoun, $exactArabic);
        $details = self::detailLine($morphology, $features, $pronoun);

        return [
            'english' => $english,
            'urdu' => $urdu,
            'arabic' => $exactArabic,
            'lemma' => $morphology->display_lemma ?: 'N/A',
            'root' => $morphology->display_root ?: 'N/A',
            'details' => $details,
            'exact_arabic_label' => $exactArabic,
            'chip_arabic_label' => $chipArabic,
            'chip_urdu_label' => BilingualGrammarService::urduForArabicLabel($chipArabic),
            'chip_english_label' => self::chipEnglishLabel($morphology, $pronoun),
            'segment_kind' => $segment['kind'],
            'fallback' => [
                'used' => GrammarLabelService::isFallback($morphology) && $pronoun === null,
                'label' => GrammarLabelService::fallbackLabel($morphology),
            ],
            'attributes' => self::attributes($morphology, $features, $pronoun),
            'pronoun' => $pronoun,
            'verb' => $features->isVerb() ? [
                'tense_aspect' => $features->tenseAspect(),
                'mood' => $features->mood(),
                'voice' => $features->voice(),
                'derived_form' => $features->derivedForm(),
                'person' => $features->personCode(),
                'gender' => $features->gender(),
                'number' => $features->number(),
            ] : null,
        ];
    }

    /**
     * @return array<int, array{label:string,value:string}>
     */
    protected static function attributes(
        Morphology $morphology,
        MorphologyFeatureExtractor $features,
        ?array $pronoun
    ): array {
        $attributes = [];

        $add = static function (string $label, ?string $value) use (&$attributes): void {
            if ($value === null || $value === '' || $value === 'N/A') {
                return;
            }

            $attributes[] = [
                'label' => $label,
                'value' => $value,
            ];
        };

        if ($pronoun !== null) {
            $add('Person', self::bilingualTerm(self::personPhrase($features->pronounCode())));
            $add('Gender', self::bilingualTerm(self::genderPhrase($features->pronounCode())));
            $add('Number', self::bilingualTerm(self::numberPhrase($features->pronounCode(), null)));

            return $attributes;
        }

        if ($features->isVerb()) {
            $add('Tense', self::bilingualTerm($features->tenseAspect()));
            $add('Mood', BilingualGrammarService::abbreviation($features->mood()));
            $add('Voice', self::bilingualTerm(self::voiceEnglish($features->voice())));
            $add('Derived Form', $features->derivedForm());
            $add('Person', self::bilingualTerm(self::personPhrase($features->personCode())));
            $add('Gender', self::bilingualTerm(self::genderPhrase($features->personCode())));
            $add('Number', self::bilingualTerm(self::numberPhrase($features->personCode(), $features->number())));

            return $attributes;
        }

        if ($features->isNounLike()) {
            $add('Case', BilingualGrammarService::abbreviation($features->caseType()));
            $add('Gender', BilingualGrammarService::abbreviation($features->gender()));
            $add('Number', BilingualGrammarService::abbreviation($features->number()));
            $add('Participle Type', self::bilingualTerm(self::participleEnglish($features->participleType())));
            $add('Derived Form', $features->derivedForm());

            return $attributes;
        }

        if ($features->isParticle()) {
            $add('Grammar', $morphology->pos_tag);
        }

        return $attributes;
    }

    protected static function englishExplanation(
        Morphology $morphology,
        MorphologyFeatureExtractor $features,
        ?array $pronoun
    ): string {
        if ($pronoun !== null) {
            $person = self::pronounPersonPhrase($features->pronounCode());

            return trim($person.' '.$pronoun['english_label_key']);
        }

        if ($features->isVerb()) {
            $pieces = array_filter([
                self::personPhrase($features->personCode()),
                self::numberPhrase($features->personCode(), $features->number()),
                self::voiceEnglish($features->voice()),
                $features->tenseAspect(),
                'verb',
            ]);

            return implode(' ', $pieces);
        }

        return str_replace(' - ', ' ', GrammarLabelService::englishLabel($morphology));
    }

    protected static function urduExplanation(
        Morphology $morphology,
        MorphologyFeatureExtractor $features,
        ?array $pronoun,
        string $exactArabic
    ): string {
        if ($pronoun !== null) {
            $parts = array_filter([
                BilingualGrammarService::urduForTerm('pronoun'),
                self::urduPersonComponent($features->pronounCode()),
                self::urduGenderComponent($features->pronounCode()),
                self::urduNumberComponent($features->pronounCode(), null),
                $pronoun['urdu_label_key'],
            ]);

            return implode(' ', $parts);
        }

        if ($features->isVerb()) {
            return implode('، ', array_filter([
                self::verbArabicLabel($features),
                self::voiceUrdu($features->voice()),
                self::urduPersonComponent($features->personCode()),
                self::urduGenderComponent($features->personCode()),
                self::urduNumberComponent($features->personCode(), $features->number()),
            ]));
        }

        return BilingualGrammarService::urduForArabicLabel($exactArabic);
    }

    protected static function detailLine(Morphology $morphology, MorphologyFeatureExtractor $features, ?array $pronoun): string
    {
        $parts = [];

        if ($pronoun !== null && $features->pronounCode()) {
            $parts[] = $features->pronounCode();
        }

        if ($features->caseType()) {
            $parts[] = BilingualGrammarService::abbreviation($features->caseType());
        }

        if ($features->gender()) {
            $parts[] = BilingualGrammarService::abbreviation($features->gender());
        }

        if ($features->number()) {
            $parts[] = BilingualGrammarService::abbreviation($features->number());
        }

        if ($features->mood()) {
            $parts[] = BilingualGrammarService::abbreviation($features->mood());
        }

        if ($features->derivedForm()) {
            $parts[] = 'Form '.$features->derivedForm().' / باب '.$features->derivedForm();
        }

        if ($features->voice()) {
            $parts[] = self::voiceDetail($features->voice());
        }

        return $parts === [] ? BilingualGrammarService::label('Segment analysis') : implode(' | ', $parts);
    }

    protected static function chipEnglishLabel(Morphology $morphology, ?array $pronoun): string
    {
        if ($pronoun !== null) {
            return $pronoun['english_label_key'];
        }

        return str_replace(' - ', ' ', GrammarLabelService::englishLabel($morphology));
    }

    protected static function verbArabicLabel(MorphologyFeatureExtractor $features): string
    {
        return match ($features->tenseAspect()) {
            'imperative' => 'فعل أمر',
            'imperfect' => 'فعل مضارع',
            'perfect' => 'فعل ماض',
            default => 'فعل',
        };
    }

    protected static function personPhrase(?string $code): ?string
    {
        return match (substr((string) $code, 0, 1)) {
            '1' => '1st person',
            '2' => '2nd person',
            '3' => '3rd person',
            default => null,
        };
    }

    protected static function numberPhrase(?string $code, ?string $fallback): ?string
    {
        $suffix = substr((string) $code, -1);

        return match (true) {
            str_ends_with((string) $code, 'P') => 'plural',
            str_ends_with((string) $code, 'D') => 'dual',
            $suffix === 'S' => 'singular',
            $fallback === 'P' => 'plural',
            $fallback === 'D' => 'dual',
            $fallback === 'S' => 'singular',
            default => null,
        };
    }

    protected static function pronounPersonPhrase(?string $code): string
    {
        return trim(implode(' ', array_filter([
            self::personPhrase($code),
            self::genderPhrase($code),
            self::numberPhrase($code, null),
        ])));
    }

    protected static function genderPhrase(?string $code): ?string
    {
        return match (true) {
            str_contains((string) $code, 'M') => 'masculine',
            str_contains((string) $code, 'F') => 'feminine',
            default => null,
        };
    }

    protected static function urduPersonComponent(?string $code): ?string
    {
        return match (substr((string) $code, 0, 1)) {
            '1' => BilingualGrammarService::urduForTerm('1st person'),
            '2' => BilingualGrammarService::urduForTerm('2nd person'),
            '3' => BilingualGrammarService::urduForTerm('3rd person'),
            default => null,
        };
    }

    protected static function urduNumberComponent(?string $code, ?string $fallback): ?string
    {
        $suffix = substr((string) $code, -1);

        return match (true) {
            str_ends_with((string) $code, 'P') => BilingualGrammarService::urduForTerm('plural'),
            str_ends_with((string) $code, 'D') => BilingualGrammarService::urduForTerm('dual'),
            $suffix === 'S' => BilingualGrammarService::urduForTerm('singular'),
            $fallback === 'P' => BilingualGrammarService::urduForTerm('plural'),
            $fallback === 'D' => BilingualGrammarService::urduForTerm('dual'),
            $fallback === 'S' => BilingualGrammarService::urduForTerm('singular'),
            default => null,
        };
    }

    protected static function urduGenderComponent(?string $code): ?string
    {
        return match (true) {
            str_contains((string) $code, 'M') => BilingualGrammarService::urduForTerm('masculine'),
            str_contains((string) $code, 'F') => BilingualGrammarService::urduForTerm('feminine'),
            default => null,
        };
    }

    protected static function voiceEnglish(?string $voice): ?string
    {
        return match ($voice) {
            'ACT' => 'active',
            'PASS' => 'passive',
            default => null,
        };
    }

    protected static function voiceUrdu(?string $voice): ?string
    {
        return match ($voice) {
            'ACT' => BilingualGrammarService::urduForTerm('active'),
            'PASS' => BilingualGrammarService::urduForTerm('passive'),
            default => null,
        };
    }

    protected static function voiceDetail(?string $voice): ?string
    {
        return match ($voice) {
            'ACT' => 'ACT / '.BilingualGrammarService::urduForTerm('active'),
            'PASS' => 'PASS / '.BilingualGrammarService::urduForTerm('passive'),
            default => null,
        };
    }

    protected static function participleEnglish(?string $type): ?string
    {
        return $type;
    }

    protected static function bilingualTerm(?string $term): ?string
    {
        if ($term === null || $term === '') {
            return null;
        }

        return BilingualGrammarService::combine($term, config('grammar.terms.'.$term));
    }
}
