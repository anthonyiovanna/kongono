<?php

declare(strict_types=1);

namespace Kongono\Http\Dev;

use FilesystemIterator;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

/**
 * Zero-Node.js, zero-bundler file fingerprint watcher powering Kongono Dev Hot Reload.
 *
 * Separates CSS fingerprints (`css_hash`, hot-swapped in-place without losing form state)
 * from code/template fingerprints (`code_hash`, triggers instant page reload).
 */
final readonly class DevHotReloadWatcher
{
    /** @var list<string> */
    private const array IGNORED_DIRS = ['vendor', 'node_modules', '.git', 'var', 'storage', '.cache'];

    /** @var list<string> */
    private const array WATCH_DIRS = ['views', 'src', 'app', 'public'];

    /**
     * Compute current CSS and code/template fingerprints across the app directory.
     *
     * @return array{css_hash: string, code_hash: string, files_watched: int}
     */
    public function fingerprint(string $appRoot): array
    {
        $real = realpath($appRoot);
        $root = rtrim(false !== $real ? $real : $appRoot, '\\/');

        $cssParts = [];
        $codeParts = [];
        $watched = 0;

        foreach (self::WATCH_DIRS as $sub) {
            $dir = $root . DIRECTORY_SEPARATOR . $sub;
            if (!is_dir($dir)) {
                continue;
            }
            $dirIt = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
            $filter = new RecursiveCallbackFilterIterator(
                $dirIt,
                static fn(SplFileInfo $cur): bool => !$cur->isDir() || !in_array($cur->getFilename(), self::IGNORED_DIRS, true),
            );
            $it = new RecursiveIteratorIterator($filter);

            foreach ($it as $file) {
                if (!$file instanceof SplFileInfo || $file->isLink() || !$file->isFile()) {
                    continue;
                }
                $name = strtolower($file->getFilename());
                $isCss = str_ends_with($name, '.css');
                $isCode = str_ends_with($name, '.kgn.html') || str_ends_with($name, '.php') || str_ends_with($name, '.js') || str_ends_with($name, '.json');
                if (!$isCss && !$isCode) {
                    continue;
                }

                try {
                    $entry = $file->getPathname() . ':' . (string) $file->getMTime() . ':' . (string) $file->getSize();
                } catch (RuntimeException) {
                    continue;
                }
                ++$watched;
                if ($isCss) {
                    $cssParts[] = $entry;
                } else {
                    $codeParts[] = $entry;
                }
            }
        }

        sort($cssParts, SORT_STRING);
        sort($codeParts, SORT_STRING);

        return [
            'css_hash' => substr(hash('xxh64', implode('|', $cssParts)), 0, 12),
            'code_hash' => substr(hash('xxh64', implode('|', $codeParts)), 0, 12),
            'files_watched' => $watched,
        ];
    }

    /**
     * Strict-CSP external client script (`/assets/hot-reload.js`) — non-blocking on Windows `php -S`,
     * pauses when tab is hidden, hot-swaps `<link rel="stylesheet">` without losing form inputs,
     * and reloads immediately when `.kgn.html` or `.php` changes.
     */
    public static function clientScript(string $basePath = '/_kongono'): string
    {
        $safeBase = json_encode(rtrim($basePath, '/'), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        return <<<JS
(function(){
  var BASE = {$safeBase};
  var lastCss = null;
  var lastCode = null;
  var busy = false;

  function swapStylesheets(hash) {
    var links = document.querySelectorAll('link[rel="stylesheet"]');
    for (var i = 0; i < links.length; i++) {
      var link = links[i];
      var href = link.getAttribute('href');
      if (!href || /^https?:\\/\\//i.test(href)) continue;
      var clean = href.replace(/([?&])_khr=[^&]*(&?)/g, function(_m, p1, p2) { return p2 ? p1 : ''; }).replace(/[?&]$/, '');
      var sep = clean.indexOf('?') === -1 ? '?' : '&';
      link.setAttribute('href', clean + sep + '_khr=' + encodeURIComponent(hash));
    }
  }

  function poll() {
    if (busy || document.hidden) return;
    busy = true;
    fetch(BASE + '/api/reload-token', { cache: 'no-store' })
      .then(function(r) { return r.ok ? r.json() : null; })
      .then(function(data) {
        if (!data) return;
        if (lastCode === null) {
          lastCode = data.code_hash;
          lastCss = data.css_hash;
          return;
        }
        if (data.code_hash !== lastCode) {
          lastCode = data.code_hash;
          window.location.reload();
          return;
        }
        if (data.css_hash !== lastCss) {
          lastCss = data.css_hash;
          swapStylesheets(data.css_hash);
        }
      })
      .catch(function() {})
      .finally(function() { busy = false; });
  }

  window.setInterval(poll, 500);
  poll();
})();
JS;
    }
}
