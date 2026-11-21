<?php

declare(strict_types=1);

namespace Kongono\Cli\Companions;

/** Clean unified-style line diff renderer for Chie code previews. */
final class DiffRenderer
{
    public static function render(string $path, ?string $before, string $after, int $maxLines = 40): string
    {
        $afterLines = explode("\n", str_replace("\r\n", "\n", $after));
        if (null === $before) {
            $out = [sprintf('--- /dev/null'), sprintf('+++ b/%s', $path)];
            $slice = array_slice($afterLines, 0, $maxLines);
            foreach ($slice as $line) {
                $out[] = '+' . $line;
            }
            if (count($afterLines) > $maxLines) {
                $out[] = sprintf('... (%d more lines)', count($afterLines) - $maxLines);
            }

            return implode("\n", $out);
        }

        $beforeLines = explode("\n", str_replace("\r\n", "\n", $before));
        $out = [sprintf('--- a/%s', $path), sprintf('+++ b/%s', $path)];
        $max = max(count($beforeLines), count($afterLines));
        $emitted = 0;

        for ($i = 0; $i < $max && $emitted < $maxLines; ++$i) {
            $old = $beforeLines[$i] ?? null;
            $new = $afterLines[$i] ?? null;
            if ($old === $new) {
                continue;
            }
            if (null !== $old) {
                $out[] = '-' . $old;
                ++$emitted;
            }
            if (null !== $new) {
                $out[] = '+' . $new;
                ++$emitted;
            }
        }

        return implode("\n", $out);
    }
}
