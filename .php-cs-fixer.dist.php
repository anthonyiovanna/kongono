<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__.'/packages/api-metadata/src')
    ->in(__DIR__.'/packages/api-metadata/tests')
    ->in(__DIR__.'/packages/application-graph/src')
    ->in(__DIR__.'/packages/application-graph/tests')
    ->in(__DIR__.'/packages/dependency-injection/src')
    ->in(__DIR__.'/packages/dependency-injection/tests')
    ->in(__DIR__.'/packages/diagnostic-activation/src')
    ->in(__DIR__.'/packages/diagnostic-activation/tests')
    ->in(__DIR__.'/packages/diagnostic-handler/src')
    ->in(__DIR__.'/packages/diagnostic-handler/tests')
    ->in(__DIR__.'/packages/diagnostic-presentation/src')
    ->in(__DIR__.'/packages/diagnostic-presentation/tests')
    ->in(__DIR__.'/packages/diagnostics/src')
    ->in(__DIR__.'/packages/diagnostics/tests')
    ->in(__DIR__.'/packages/http-message/src')
    ->in(__DIR__.'/packages/http-message/tests')
    ->in(__DIR__.'/packages/http-server/src')
    ->in(__DIR__.'/packages/http-server/tests')
    ->in(__DIR__.'/packages/kernel/src')
    ->in(__DIR__.'/packages/kernel/tests')
    ->in(__DIR__.'/packages/php-cgi/src')
    ->in(__DIR__.'/packages/php-cgi/tests')
    ->in(__DIR__.'/packages/routing/src')
    ->in(__DIR__.'/packages/routing/tests')
    ->in(__DIR__.'/packages/web-application/src')
    ->in(__DIR__.'/packages/web-application/tests')
    ->in(__DIR__.'/packages/web-runtime/src')
    ->in(__DIR__.'/packages/web-runtime/tests')
    ->in(__DIR__.'/tests');

return (new PhpCsFixer\Config())
    // Enforcing strict types can change runtime coercion, so the formatter must acknowledge the rule as risky.
    ->setRiskyAllowed(true)
    ->setRules([
        '@PER-CS' => true,
        'declare_strict_types' => true,
        'no_unused_imports' => true,
        'ordered_imports' => [
            'sort_algorithm' => 'alpha',
        ],
        'trailing_comma_in_multiline' => [
            'elements' => [
                'arguments',
                'array_destructuring',
                'arrays',
                'match',
                'parameters',
            ],
        ],
    ])
    ->setFinder($finder)
    ->setCacheFile(__DIR__.'/.php-cs-fixer.cache');
