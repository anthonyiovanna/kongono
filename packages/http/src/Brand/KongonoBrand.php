<?php

declare(strict_types=1);

namespace Kongono\Http\Brand;

/**
 * Canonical pure-vector Kongono brand marks:
 *  - Core mark: Gunmetal × Platinum Solid Katana-Cut Hanko
 *  - Oshie (AI Tutor): Gunmetal × Sapphire Sensei Dialogue (O5)
 *  - Chie (AI Builder): Gunmetal × Hisui Jade Graph Forge (C5)
 *  - Hantei (AI Debugger & Judge): Gunmetal × Urushi Crimson Forensic Lens (H5)
 *
 * Stateless helper shared across built-in error pages, Dev Studio, Ops Dashboard,
 * and OpenAPI Docs without external requests or mutable global state.
 */
final readonly class KongonoBrand
{
    private const string HANKO_BODY_PATH = 'M17 6H47A11 11 0 0 1 58 17V47A11 11 0 0 1 54.9 54.6L35 34.7A21.2 21.2 0 0 1 27.7 37L48.6 57.9A11 11 0 0 1 47 58H17A11 11 0 0 1 6 47V17A11 11 0 0 1 17 6ZM15.7 16V48H22.3V16H15.7ZM43.5 16H36.9A11.8 11.8 0 0 1 25.1 27.8V34.4A18.4 18.4 0 0 0 43.5 16Z';
    private const string SPINE_PATH = 'M15.7 16H22.3V48H15.7V16Z';
    private const string CRESCENT_PATH = 'M43.5 16A18.4 18.4 0 0 1 25.1 34.4V27.8A11.8 11.8 0 0 0 36.9 16H43.5Z';
    private const string BLADE_PATH = 'M27.7 37A21.2 21.2 0 0 0 35 34.7L54.9 54.6A11 11 0 0 1 48.6 57.9L27.7 37Z';

    /**
     * Inline HTML `<svg>` for headers, error pages, and dashboards (strict-CSP safe).
     */
    public static function markSvg(int $size = 24, string $gradId = 'kgn-mark-gm'): string
    {
        $safeId = self::sanitizeId($gradId, 'kgn-mark-gm');
        $px = max(12, $size);

        return sprintf(
            '<svg width="%d" height="%d" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">'
            . '<defs><linearGradient id="%s" x1="6" y1="6" x2="58" y2="58" gradientUnits="userSpaceOnUse">'
            . '<stop offset="0%%" stop-color="#4a4f5c"/><stop offset="100%%" stop-color="#21242c"/>'
            . '</linearGradient></defs>'
            . '<path fill-rule="evenodd" clip-rule="evenodd" d="%s" fill="url(#%s)" stroke="#ffffff" stroke-opacity="0.18" stroke-width="1"/>'
            . '<path d="%s" fill="#f8fafc"/>'
            . '<path d="%s" fill="#f8fafc"/>'
            . '<path d="%s" fill="#f8fafc"/>'
            . '</svg>',
            $px,
            $px,
            $safeId,
            self::HANKO_BODY_PATH,
            $safeId,
            self::SPINE_PATH,
            self::CRESCENT_PATH,
            self::BLADE_PATH,
        );
    }

    /**
     * Standalone SVG document suitable for serving as `image/svg+xml` favicon.
     */
    public static function faviconSvg(): string
    {
        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" fill="none">'
            . '<defs><linearGradient id="kgn-fav-gm" x1="6" y1="6" x2="58" y2="58" gradientUnits="userSpaceOnUse">'
            . '<stop offset="0%%" stop-color="#4a4f5c"/><stop offset="100%%" stop-color="#21242c"/>'
            . '</linearGradient></defs>'
            . '<path fill-rule="evenodd" clip-rule="evenodd" d="%s" fill="url(#kgn-fav-gm)" stroke="#ffffff" stroke-opacity="0.18" stroke-width="1"/>'
            . '<path d="%s" fill="#f8fafc"/>'
            . '<path d="%s" fill="#f8fafc"/>'
            . '<path d="%s" fill="#f8fafc"/>'
            . '</svg>',
            self::HANKO_BODY_PATH,
            self::SPINE_PATH,
            self::CRESCENT_PATH,
            self::BLADE_PATH,
        );
    }

    /**
     * Oshie (`kongo oshie`) — Sensei Dialogue in Gunmetal × Sapphire alloy.
     */
    public static function oshieSvg(int $size = 24, string $gradId = 'kgn-oshie-gm'): string
    {
        return self::companionSvg(
            size: $size,
            gradId: self::sanitizeId($gradId, 'kgn-oshie-gm'),
            topColor: '#486285',
            botColor: '#1d2b3f',
            rimColor: '#bfdbfe',
            inner: '<path fill-rule="evenodd" clip-rule="evenodd" d="M20 15H44A6 6 0 0 1 50 21V36A6 6 0 0 1 44 42H27.5L17 50V42A6 6 0 0 1 14 36.8V21A6 6 0 0 1 20 15ZM32 20L29.2 25.7L23.5 28.5L29.2 31.3L32 37L34.8 31.3L40.5 28.5L34.8 25.7L32 20Z" fill="#f4f8ff"/>',
        );
    }

    /**
     * Chie (`kongo chie`) — Graph Forge in Gunmetal × Hisui Jade alloy.
     */
    public static function chieSvg(int $size = 24, string $gradId = 'kgn-chie-gm'): string
    {
        return self::companionSvg(
            size: $size,
            gradId: self::sanitizeId($gradId, 'kgn-chie-gm'),
            topColor: '#3d685e',
            botColor: '#182f2a',
            rimColor: '#a7f3d0',
            inner: '<path d="M20 21V43H43V25M24 21H35" stroke="#f2fbf8" stroke-width="3.6" opacity="0.46"/>'
                . '<circle cx="20" cy="21" r="5" fill="#f2fbf8" opacity="0.68"/>'
                . '<circle cx="20" cy="43" r="5" fill="#f2fbf8" opacity="0.68"/>'
                . '<circle cx="43" cy="43" r="5" fill="#f2fbf8" opacity="0.68"/>'
                . '<polygon points="43,12.5 51.5,21 43,29.5 34.5,21" fill="#f2fbf8"/>',
        );
    }

    /**
     * Hantei (`kongo hantei`) — Forensic Lens in Gunmetal × Urushi Crimson alloy.
     */
    public static function hanteiSvg(int $size = 24, string $gradId = 'kgn-hantei-gm'): string
    {
        return self::companionSvg(
            size: $size,
            gradId: self::sanitizeId($gradId, 'kgn-hantei-gm'),
            topColor: '#784148',
            botColor: '#32191e',
            rimColor: '#fecdd3',
            inner: '<circle cx="28.5" cy="28.5" r="13" stroke="#fff5f5" stroke-width="4.6"/>'
                . '<line x1="38.2" y1="38.2" x2="50.5" y2="50.5" stroke="#fff5f5" stroke-width="6.2" stroke-linecap="butt"/>'
                . '<polygon points="28.5,22.2 34.8,28.5 28.5,34.8 22.2,28.5" fill="#fff5f5"/>',
        );
    }

    private static function companionSvg(
        int $size,
        string $gradId,
        string $topColor,
        string $botColor,
        string $rimColor,
        string $inner,
    ): string {
        $px = max(12, $size);

        return sprintf(
            '<svg width="%d" height="%d" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">'
            . '<defs><linearGradient id="%s" x1="6" y1="6" x2="58" y2="58" gradientUnits="userSpaceOnUse">'
            . '<stop offset="0%%" stop-color="%s"/><stop offset="100%%" stop-color="%s"/>'
            . '</linearGradient></defs>'
            . '<rect x="6" y="6" width="52" height="52" rx="11" fill="url(#%s)" stroke="%s" stroke-opacity="0.24" stroke-width="1"/>'
            . '%s'
            . '</svg>',
            $px,
            $px,
            $gradId,
            $topColor,
            $botColor,
            $gradId,
            $rimColor,
            $inner,
        );
    }

    private static function sanitizeId(string $gradId, string $fallback): string
    {
        $safeId = preg_replace('/[^A-Za-z0-9_-]/', '', $gradId);

        return null !== $safeId && '' !== $safeId ? $safeId : $fallback;
    }
}
