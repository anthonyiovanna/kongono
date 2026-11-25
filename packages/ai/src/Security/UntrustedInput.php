<?php

declare(strict_types=1);

namespace Kongono\Ai\Security;

/**
 * Explicitly marks text originating from outside the trusted application boundary
 * (user input, emails, webhooks, scraped HTML, tool outputs) so PromptGuard can
 * isolate it from trusted system instructions.
 */
final readonly class UntrustedInput
{
    public function __construct(
        public string $content,
        public string $source = 'external',
        public ?string $origin = null,
    ) {}

    /**
     * Strip invisible zero-width Unicode obfuscators, normalize fullwidth homoglyphs,
     * and neutralize XML/boundary breakout attempts so untrusted content cannot forge
     * or escape system prompt delimiters.
     */
    public function sanitized(): string
    {
        $clean = self::normalizeForInspection($this->content);
        $clean = (string) preg_replace('/\[\s*KONGONO\s+SECURITY\s+BOUNDARY\s*\]/iu', '[neutralized-boundary]', $clean);

        return (string) preg_replace(
            '#<\s*/?\s*(untrusted_data|system|instructions|assistant|developer)\b[^>]*>#iu',
            '[neutralized-tag]',
            $clean,
        );
    }

    /**
     * Scrub invalid UTF-8 first (so invalid bytes can never disable PCRE /u mode), strip invisible/format/tag
     * Unicode obfuscators (including Variation Selectors Supplement), fold Unicode spaces (\p{Zs}), and fold
     * fullwidth ASCII + alternate angle/slash homoglyphs.
     */
    public static function normalizeForInspection(string $raw): string
    {
        // Scrub malformed UTF-8 sequences BEFORE running any /u regex so a single \x80 byte cannot bypass stripping
        $clean = function_exists('mb_scrub')
            ? mb_scrub($raw, 'UTF-8')
            : (string) @iconv('UTF-8', 'UTF-8//IGNORE', $raw);
        $clean = str_replace("\0", '', $clean);

        // Apply Unicode NFKC normalization when ext-intl Normalizer is available (folds Mathematical Alphanumerics & compat forms)
        if (class_exists(\Normalizer::class)) {
            $nfkc = \Normalizer::normalize($clean, \Normalizer::FORM_KC);
            if (is_string($nfkc)) {
                $clean = $nfkc;
            }
        }

        // Strip C0 controls (except \t, \n, \r), combining diacritical marks (\p{Mn}), all Unicode Cf format chars,
        // CGJ, fillers, variation selectors (BMP + Supplement), and Unicode Tag block
        $stripped = preg_replace(
            '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F\p{Cf}\p{Mn}\x{034F}\x{115F}\x{1160}\x{180E}\x{3164}\x{FE00}-\x{FE0F}\x{FFA0}\x{E0000}-\x{E007F}\x{E0100}-\x{E01EF}]/u',
            '',
            $clean,
        );
        if (is_string($stripped)) {
            $clean = $stripped;
        }

        // Fold Unicode space separators (NBSP, thin/hair/ideographic spaces, etc.) to ASCII space
        $spaced = preg_replace('/\p{Zs}+/u', ' ', $clean);
        if (is_string($spaced)) {
            $clean = $spaced;
        }

        // Fold fullwidth ASCII forms (U+FF01..U+FF5E -> ASCII !..~)
        $folded = preg_replace_callback('/[\x{FF01}-\x{FF5E}]/u', static function (array $m): string {
            $code = mb_ord($m[0], 'UTF-8');

            return $code >= 0xFF01 && $code <= 0xFF5E ? chr($code - 0xFEE0) : $m[0];
        }, $clean);
        if (is_string($folded)) {
            $clean = $folded;
        }

        // Fold common Cyrillic/Greek visual confusables & alternate angle/slash brackets
        return strtr($clean, [
            '﹤' => '<', '﹥' => '>', '∕' => '/', '⁄' => '/', '⟨' => '<', '⟩' => '>',
            '〈' => '<', '〉' => '>', '《' => '<', '》' => '>', '‹' => '<', '›' => '>',
            'а' => 'a', 'е' => 'e', 'о' => 'o', 'р' => 'p', 'с' => 'c', 'у' => 'y', 'х' => 'x', 'і' => 'i', 'ј' => 'j', 'ѕ' => 's',
            'А' => 'A', 'В' => 'B', 'Е' => 'E', 'К' => 'K', 'М' => 'M', 'Н' => 'H', 'О' => 'O', 'Р' => 'P', 'С' => 'C', 'Т' => 'T', 'Х' => 'X',
            'α' => 'a', 'ε' => 'e', 'ι' => 'i', 'ο' => 'o', 'ρ' => 'p', 'υ' => 'u', 'ν' => 'v', 'χ' => 'x',
        ]);
    }
}
