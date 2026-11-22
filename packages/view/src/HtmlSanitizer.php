<?php

declare(strict_types=1);

namespace Kongono\View;

use Dom\Element;
use Dom\HTMLDocument;
use Dom\Node;
use Dom\Text;

/**
 * Zero-dependency WHATWG HTML5 strict allowlist sanitizer powered by PHP 8.5's native
 * `Dom\HTMLDocument` (Lexbor HTML5 parser).
 *
 * Parses untrusted HTML using the exact HTML5 specification used by modern browsers and
 * reconstructs output strictly from allowlisted elements, attributes, and URL schemes —
 * neutralizing XSS, mXSS, `<script>`, `<svg>`, `<math>`, event handlers, and `javascript:`/`data:` URIs.
 */
final readonly class HtmlSanitizer
{
    private const int MAX_INPUT_BYTES = 2_097_152;
    private const int MAX_DEPTH = 64;

    /** @var array<string, true> */
    private const array ALLOWED_TAGS = [
        'a' => true,
        'abbr' => true,
        'b' => true,
        'blockquote' => true,
        'br' => true,
        'caption' => true,
        'code' => true,
        'dd' => true,
        'del' => true,
        'details' => true,
        'div' => true,
        'dl' => true,
        'dt' => true,
        'em' => true,
        'figcaption' => true,
        'figure' => true,
        'h1' => true,
        'h2' => true,
        'h3' => true,
        'h4' => true,
        'h5' => true,
        'h6' => true,
        'hr' => true,
        'i' => true,
        'img' => true,
        'ins' => true,
        'kbd' => true,
        'li' => true,
        'mark' => true,
        'ol' => true,
        'p' => true,
        'pre' => true,
        'q' => true,
        's' => true,
        'samp' => true,
        'small' => true,
        'span' => true,
        'strong' => true,
        'sub' => true,
        'summary' => true,
        'sup' => true,
        'table' => true,
        'tbody' => true,
        'td' => true,
        'tfoot' => true,
        'th' => true,
        'thead' => true,
        'time' => true,
        'tr' => true,
        'u' => true,
        'ul' => true,
        'var' => true,
        'wbr' => true,
    ];

    /** @var array<string, true> */
    private const array VOID_TAGS = [
        'br' => true,
        'hr' => true,
        'img' => true,
        'wbr' => true,
    ];

    /**
     * Elements whose entire subtree (tags + inner text/children) MUST be discarded completely.
     *
     * @var array<string, true>
     */
    private const array DROP_SUBTREE_TAGS = [
        'applet' => true,
        'audio' => true,
        'base' => true,
        'button' => true,
        'canvas' => true,
        'dialog' => true,
        'embed' => true,
        'form' => true,
        'frame' => true,
        'frameset' => true,
        'head' => true,
        'iframe' => true,
        'input' => true,
        'link' => true,
        'math' => true,
        'meta' => true,
        'noscript' => true,
        'object' => true,
        'optgroup' => true,
        'option' => true,
        'portal' => true,
        'script' => true,
        'select' => true,
        'source' => true,
        'style' => true,
        'svg' => true,
        'template' => true,
        'textarea' => true,
        'title' => true,
        'track' => true,
        'video' => true,
        'xmp' => true,
    ];

    /** @var array<string, true> */
    private const array SAFE_REL_TOKENS = [
        'nofollow' => true,
        'noopener' => true,
        'noreferrer' => true,
        'ugc' => true,
    ];

    /** Sanitize untrusted HTML into safe, allowlisted HTML5 output. */
    public static function sanitize(string $html): string
    {
        if ('' === $html) {
            return '';
        }
        if (strlen($html) > self::MAX_INPUT_BYTES) {
            throw new ViewException(sprintf('HTML input exceeds maximum allowed size of %d bytes.', self::MAX_INPUT_BYTES));
        }

        // Strip NUL bytes prior to HTML5 parsing
        $cleanInput = str_replace("\0", '', $html);
        $doc = HTMLDocument::createFromString(
            '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>' . $cleanInput . '</body></html>',
            LIBXML_NOERROR,
        );

        $body = $doc->body;
        if (null === $body) {
            return '';
        }

        return self::serializeChildren($body, 0);
    }

    private static function serializeChildren(Node $parent, int $depth): string
    {
        if ($depth >= self::MAX_DEPTH) {
            return '';
        }

        $out = '';
        foreach ($parent->childNodes as $child) {
            if ($child instanceof Text) {
                $out .= htmlspecialchars($child->data, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                continue;
            }

            if (!$child instanceof Element) {
                // Drop comments, processing instructions, CDATA, etc.
                continue;
            }

            $tag = strtolower($child->localName);

            // Drop dangerous executable / foreign-namespace subtrees entirely
            if (isset(self::DROP_SUBTREE_TAGS[$tag])) {
                continue;
            }

            // Unwrap unknown non-dangerous elements while sanitizing their children
            if (!isset(self::ALLOWED_TAGS[$tag])) {
                $out .= self::serializeChildren($child, $depth + 1);
                continue;
            }

            $attrs = self::sanitizeAttributes($tag, $child);
            $attrSql = '' !== $attrs ? ' ' . $attrs : '';

            if (isset(self::VOID_TAGS[$tag])) {
                $out .= '<' . $tag . $attrSql . '>';
                continue;
            }

            $out .= '<' . $tag . $attrSql . '>' . self::serializeChildren($child, $depth + 1) . '</' . $tag . '>';
        }

        return $out;
    }

    private static function sanitizeAttributes(string $tag, Element $element): string
    {
        /** @var array<string, string> $safe */
        $safe = [];

        foreach ($element->attributes as $attr) {
            $name = strtolower($attr->localName);
            $val = trim(str_replace("\0", '', $attr->value));

            if ('class' === $name) {
                if (1 === preg_match('/\A[a-zA-Z0-9_\- ]{1,256}\z/', $val)) {
                    $safe['class'] = $val;
                }
                continue;
            }

            if ('title' === $name && '' !== $val && strlen($val) <= 256) {
                $safe['title'] = $val;
                continue;
            }

            if ('a' === $tag) {
                if ('href' === $name && self::isSafeUrl($val, allowMailto: true, allowFragment: true)) {
                    $safe['href'] = $val;
                } elseif ('target' === $name && '_blank' === strtolower($val)) {
                    $safe['target'] = '_blank';
                } elseif ('rel' === $name) {
                    $tokens = preg_split('/\s+/', strtolower($val));
                    if (is_array($tokens)) {
                        $filtered = array_values(array_unique(array_filter(
                            $tokens,
                            static fn(string $t): bool => isset(self::SAFE_REL_TOKENS[$t]),
                        )));
                        if ([] !== $filtered) {
                            $safe['rel'] = implode(' ', $filtered);
                        }
                    }
                }
                continue;
            }

            if ('img' === $tag) {
                if ('src' === $name && self::isSafeUrl($val, allowMailto: false, allowFragment: false)) {
                    $safe['src'] = $val;
                } elseif ('alt' === $name && strlen($val) <= 512) {
                    $safe['alt'] = $val;
                } elseif (('width' === $name || 'height' === $name) && 1 === preg_match('/\A[1-9][0-9]{0,3}\z/', $val)) {
                    $safe[$name] = $val;
                }
                continue;
            }

            if (('td' === $tag || 'th' === $tag) && ('colspan' === $name || 'rowspan' === $name)) {
                if (1 === preg_match('/\A[1-9][0-9]?\z/', $val)) {
                    $safe[$name] = $val;
                }
                continue;
            }

            if ('ol' === $tag && 'start' === $name && 1 === preg_match('/\A[0-9]{1,6}\z/', $val)) {
                $safe['start'] = $val;
                continue;
            }

            if ('details' === $tag && 'open' === $name) {
                $safe['open'] = 'open';
                continue;
            }

            if ('time' === $tag && 'datetime' === $name && 1 === preg_match('/\A[0-9TZ:.+\- ]{4,40}\z/', $val)) {
                $safe['datetime'] = $val;
            }
        }

        // Always enforce noopener noreferrer on target="_blank" links against tabnabbing
        if ('a' === $tag && '_blank' === ($safe['target'] ?? null)) {
            $existing = isset($safe['rel']) ? explode(' ', $safe['rel']) : [];
            $merged = array_values(array_unique([...$existing, 'noopener', 'noreferrer']));
            $safe['rel'] = implode(' ', $merged);
        }

        $parts = [];
        foreach ($safe as $k => $v) {
            if ('open' === $k && 'details' === $tag) {
                $parts[] = 'open';
                continue;
            }
            $parts[] = sprintf('%s="%s"', $k, htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        }

        return implode(' ', $parts);
    }

    /**
     * Strict URL allowlist check rejecting javascript:, vbscript:, data:, protocol-relative //,
     * backslashes, and control characters (including whitespace-obfuscated schemes).
     */
    public static function isSafeUrl(string $url, bool $allowMailto = true, bool $allowFragment = true): bool
    {
        $trimmed = trim($url);
        if ('' === $trimmed || strlen($trimmed) > 2048) {
            return false;
        }
        // Reject any ASCII control chars (CR, LF, TAB, NUL, DEL) or backslashes
        if (1 === preg_match('/[\x00-\x1F\x7F\\\\]/', $trimmed)) {
            return false;
        }
        if ($allowFragment && str_starts_with($trimmed, '#')) {
            return true;
        }
        // Reject protocol-relative `//evil.test` explicitly before relative-path admission
        if (str_starts_with($trimmed, '//')) {
            return false;
        }
        // Allow same-origin absolute paths (`/docs/1`)
        if (str_starts_with($trimmed, '/')) {
            return true;
        }

        // Check scheme strictly (before first `:`, `/`, `?`, `#`)
        if (1 === preg_match('/\A([a-zA-Z][a-zA-Z0-9+.-]*):/', $trimmed, $m)) {
            $scheme = strtolower($m[1]);
            if ('https' === $scheme || 'http' === $scheme) {
                return str_starts_with(strtolower($trimmed), $scheme . '://');
            }
            if ($allowMailto && 'mailto' === $scheme) {
                return '' !== trim(substr($trimmed, 7));
            }

            return false;
        }

        // Relative path without scheme (must not contain `:` before first `/` which browsers parse as scheme)
        $colonPos = strpos($trimmed, ':');
        $slashPos = strpos($trimmed, '/');
        if (false !== $colonPos && (false === $slashPos || $colonPos < $slashPos)) {
            return false;
        }

        return true;
    }
}
