<?php

declare(strict_types=1);

namespace Kongono\Cli\Diagnostics;

use Kongono\Core\Diagnostics\IncidentRecord;

/**
 * Generates a permanent PHPUnit 13 `TestClient` regression test directly from a recorded
 * `KGN-YYYYMMDD-XXXXXXXX` production incident (`kongo incident:test <KGN-...> [--apply]`).
 */
final readonly class IncidentTestGenerator
{
    /**
     * @return array{relative_path: string, class_name: string, content: string}
     */
    public function generate(IncidentRecord $incident): array
    {
        $safeId = (string) preg_replace('/[^A-Za-z0-9]/', '', $incident->reference);
        if ('' === $safeId) {
            $safeId = 'Unknown';
        }
        $className = 'Incident' . $safeId . 'RegressionTest';
        $relativePath = 'tests/Incidents/' . $className . '.php';

        $uri = $incident->path;
        if ([] !== $incident->query) {
            $qs = http_build_query($incident->query);
            if ('' !== $qs) {
                $uri .= '?' . $qs;
            }
        }

        $method = strtoupper($incident->method);
        $uriExport = var_export($uri, true);
        $methodExport = var_export($method, true);
        $isJson = str_contains(strtolower($incident->contentType), 'application/json');
        $headersExport = var_export($incident->headers, true);
        $bodyExport = var_export($incident->body, true);

        $requestBuild = $isJson && [] !== $incident->body
            ? sprintf('Request::json(%s, %s, %s, %s)', $methodExport, $uriExport, $bodyExport, $headersExport)
            : sprintf(
                'Request::create(%s, %s, %s, %s, parsedBody: %s)',
                $methodExport,
                $uriExport,
                $headersExport,
                [] !== $incident->body ? var_export(http_build_query($incident->body), true) : "''",
                [] !== $incident->body ? $bodyExport : 'null',
            );

        $safeRef = self::sanitizeDocComment($incident->reference, 64);
        $origCode = self::sanitizeDocComment(
            is_string($incident->explanation['code'] ?? null) ? $incident->explanation['code'] : 'KGN-ERR',
            64,
        );
        $origWhat = self::sanitizeDocComment(
            is_string($incident->explanation['what'] ?? null) ? $incident->explanation['what'] : 'Runtime incident',
            240,
        );
        $safeOccurredAt = self::sanitizeDocComment($incident->occurredAt, 64);
        $assertionMsg = $incident->statusCode >= 500
            ? sprintf('Incident %s regression: endpoint returned 5xx again.', $safeRef)
            : sprintf('Incident %s regression: endpoint returned error status again.', $safeRef);
        $assertion = $incident->statusCode >= 500
            ? sprintf('self::assertLessThan(500, $response->getStatusCode(), %s);', var_export($assertionMsg, true))
            : sprintf('self::assertLessThan(400, $response->getStatusCode(), %s);', var_export($assertionMsg, true));

        $content = <<<PHP
<?php

declare(strict_types=1);

namespace Tests\\Incidents;

use Kongono\\Http\\App;
use Kongono\\Http\\Request;
use PHPUnit\\Framework\\TestCase;

/**
 * Auto-generated regression test for Kongono Incident {$safeRef}.
 * Original status: HTTP {$incident->statusCode} [{$origCode}] — {$origWhat}
 * Recorded at: {$safeOccurredAt}
 */
final class {$className} extends TestCase
{
    public function testIncident{$safeId}DoesNotRegress(): void
    {
        \$app = require dirname(__DIR__, 2) . '/app/bootstrap.php';
        self::assertInstanceOf(App::class, \$app);

        \$request = {$requestBuild};
        \$response = \$app->handle(\$request);

        {$assertion}
    }
}

PHP;

        return [
            'relative_path' => $relativePath,
            'class_name' => $className,
            'content' => $content,
        ];
    }

    private static function sanitizeDocComment(string $value, int $maxLength): string
    {
        $clean = (string) preg_replace('/[\x00-\x1f\x7f]+/', ' ', $value);
        $clean = str_replace(['*/', '/*'], ['* /', '/ *'], $clean);
        $clean = trim((string) preg_replace('/\s+/', ' ', $clean));

        return substr($clean, 0, $maxLength);
    }
}
