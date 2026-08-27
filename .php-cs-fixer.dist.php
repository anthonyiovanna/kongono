<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__.'/packages/api-metadata/src')
    ->in(__DIR__.'/packages/api-metadata/tests')
    ->in(__DIR__.'/packages/application-graph/src')
    ->in(__DIR__.'/packages/application-graph/tests')
    ->in(__DIR__.'/packages/authentication/src')
    ->in(__DIR__.'/packages/authentication/tests')
    ->in(__DIR__.'/packages/authorization/src')
    ->in(__DIR__.'/packages/authorization/tests')
    ->in(__DIR__.'/packages/chie/src')
    ->in(__DIR__.'/packages/chie/tests')
    ->in(__DIR__.'/packages/composition/src')
    ->in(__DIR__.'/packages/composition/tests')
    ->in(__DIR__.'/packages/console/src')
    ->in(__DIR__.'/packages/console/tests')
    ->in(__DIR__.'/packages/console-application/src')
    ->in(__DIR__.'/packages/console-application/tests')
    ->in(__DIR__.'/packages/console-build/src')
    ->in(__DIR__.'/packages/console-build/tests')
    ->in(__DIR__.'/packages/console-commands/src')
    ->in(__DIR__.'/packages/console-commands/tests')
    ->in(__DIR__.'/packages/console-presentation/src')
    ->in(__DIR__.'/packages/console-presentation/tests')
    ->in(__DIR__.'/packages/console-runtime/src')
    ->in(__DIR__.'/packages/console-runtime/tests')
    ->in(__DIR__.'/packages/database/src')
    ->in(__DIR__.'/packages/database/tests')
    ->in(__DIR__.'/packages/database-application/src')
    ->in(__DIR__.'/packages/database-application/tests')
    ->in(__DIR__.'/packages/database-mapping/src')
    ->in(__DIR__.'/packages/database-mapping/tests')
    ->in(__DIR__.'/packages/database-migration/src')
    ->in(__DIR__.'/packages/database-migration/tests')
    ->in(__DIR__.'/packages/database-migration-pgsql/src')
    ->in(__DIR__.'/packages/database-migration-pgsql/tests')
    ->in(__DIR__.'/packages/database-pdo-pgsql/src')
    ->in(__DIR__.'/packages/database-pdo-pgsql/tests')
    ->in(__DIR__.'/packages/database-sql/src')
    ->in(__DIR__.'/packages/database-sql/tests')
    ->in(__DIR__.'/packages/database-sql-pgsql/src')
    ->in(__DIR__.'/packages/database-sql-pgsql/tests')
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
    ->in(__DIR__.'/packages/hantei/src')
    ->in(__DIR__.'/packages/hantei/tests')
    ->in(__DIR__.'/packages/http-message/src')
    ->in(__DIR__.'/packages/http-message/tests')
    ->in(__DIR__.'/packages/http-server/src')
    ->in(__DIR__.'/packages/http-server/tests')
    ->in(__DIR__.'/packages/identity-access/src')
    ->in(__DIR__.'/packages/identity-access/tests')
    ->in(__DIR__.'/packages/kernel/src')
    ->in(__DIR__.'/packages/kernel/tests')
    ->in(__DIR__.'/packages/knowledge/src')
    ->in(__DIR__.'/packages/knowledge/tests')
    ->in(__DIR__.'/packages/oshie/src')
    ->in(__DIR__.'/packages/oshie/tests')
    ->in(__DIR__.'/packages/persistence/src')
    ->in(__DIR__.'/packages/persistence/tests')
    ->in(__DIR__.'/packages/persistence-application/src')
    ->in(__DIR__.'/packages/persistence-application/tests')
    ->in(__DIR__.'/packages/persistence-build/src')
    ->in(__DIR__.'/packages/persistence-build/tests')
    ->in(__DIR__.'/packages/php-cgi/src')
    ->in(__DIR__.'/packages/php-cgi/tests')
    ->in(__DIR__.'/packages/routing/src')
    ->in(__DIR__.'/packages/routing/tests')
    ->in(__DIR__.'/packages/security-application/src')
    ->in(__DIR__.'/packages/security-application/tests')
    ->in(__DIR__.'/packages/security-enforcement/src')
    ->in(__DIR__.'/packages/security-enforcement/tests')
    ->in(__DIR__.'/packages/security-http/src')
    ->in(__DIR__.'/packages/security-http/tests')
    ->in(__DIR__.'/packages/security-policy-build/src')
    ->in(__DIR__.'/packages/security-policy-build/tests')
    ->in(__DIR__.'/packages/security-runtime/src')
    ->in(__DIR__.'/packages/security-runtime/tests')
    ->in(__DIR__.'/packages/web-application/src')
    ->in(__DIR__.'/packages/web-application/tests')
    ->in(__DIR__.'/packages/web-runtime/src')
    ->in(__DIR__.'/packages/web-runtime/tests')
    ->in(__DIR__.'/skeletons/data-minimal/bin')
    ->in(__DIR__.'/skeletons/data-minimal/src')
    ->in(__DIR__.'/skeletons/minimal/bin')
    ->in(__DIR__.'/skeletons/minimal/public')
    ->in(__DIR__.'/skeletons/minimal/src')
    ->in(__DIR__.'/skeletons/persistence-minimal/bin')
    ->in(__DIR__.'/skeletons/persistence-minimal/src')
    ->in(__DIR__.'/skeletons/security-minimal/bin')
    ->in(__DIR__.'/skeletons/security-minimal/src')
    ->in(__DIR__.'/tests')
    ->append([
        __DIR__.'/packages/chie/bin/kongono-chie',
        __DIR__.'/packages/console-application/bin/kongo',
        __DIR__.'/packages/hantei/bin/kongono-hantei',
        __DIR__.'/packages/knowledge/bin/kongono-knowledge',
        __DIR__.'/packages/oshie/bin/kongono-oshie',
    ]);

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
