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
     * Strip invisible zero-width Unicode obfuscators, normalize fullwidth angle brackets,
     * and neutralize XML/boundary breakout attempts so untrusted content cannot forge
     * or escape system prompt delimiters.
     */
    public function sanitized(): string
    {
        $clean = self::normalizeForInspection($this->content);

        return (string) preg_replace(
            '#<\s*/?\s*(untrusted_data|system|instructions|assistant|developer)\b[^>]*>#i',
            '[neutralized-tag]',
            $clean,
        );
    }

    /**
     * Strip NUL bytes, zero-width/invisible Unicode format characters, and normalize
     * fullwidth angle brackets used to evade prompt-injection regexes.
     */
    public static function normalizeForInspection(string $raw): string
    {
        $clean = str_replace("\0", '', $raw);
        // Strip Unicode format/zero-width obfuscators (ZWSP, ZWNJ, ZWJ, BOM, soft hyphen, bidi overrides)
        $stripped = preg_replace('/[\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2060}-\x{206F}\x{FEFF}\x{00AD}]/u', '', $clean);
        if (is_string($stripped)) {
            $clean = $stripped;
        }

        return str_replace(['＜', '＞', '／'], ['<', '>', '/'], $clean);
    }
}
