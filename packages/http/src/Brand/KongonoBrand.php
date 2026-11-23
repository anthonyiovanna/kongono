<?php

declare(strict_types=1);

namespace Kongono\Http\Brand;

/**
 * Canonical pure-vector Kongono brand mark (Gunmetal × Platinum · Solid Katana-Cut Hanko).
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
        $safeId = preg_replace('/[^A-Za-z0-9_-]/', '', $gradId);
        if (null === $safeId || '' === $safeId) {
            $safeId = 'kgn-mark-gm';
        }
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
}
