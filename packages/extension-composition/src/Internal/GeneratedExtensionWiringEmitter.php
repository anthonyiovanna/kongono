<?php

declare(strict_types=1);

namespace Kongono\ExtensionComposition\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Emits one canonical ordinary-PHP file with explicit typed constructor edges only. */
#[Api(ApiStatus::Internal)]
final class GeneratedExtensionWiringEmitter
{
    /**
     * @param list<array{class:string,lifecycle:string,decisions:list<array<string, string>>}> $extensions
     */
    public function emit(
        string $namespace,
        string $declarationSetIdentity,
        string $admissionIdentity,
        string $grantPlanIdentity,
        string $implementationSha256,
        array $extensions,
        int $decisionCount,
        int $applicationProviderCount,
        int $operationProviderCount,
    ): string {
        $applicationExtensions = array_values(array_filter($extensions, static fn(array $extension): bool => 'application' === $extension['lifecycle']));
        $operationExtensions = array_values(array_filter($extensions, static fn(array $extension): bool => 'operation' === $extension['lifecycle']));
        $applicationProviders = [];
        $operationProviders = [];
        $retainedApplicationProviders = [];
        foreach ($extensions as $extension) {
            foreach ($extension['decisions'] as $decision) {
                if ('granted' !== $decision['disposition']) {
                    continue;
                }
                $record = ['name' => $decision['provider_name'], 'contract' => $decision['provider']];
                if ('application' === $decision['provider_lifetime']) {
                    $applicationProviders[$decision['provider_name']] = $record;
                    if ('operation' === $extension['lifecycle']) {
                        $retainedApplicationProviders[$decision['provider_name']] = $record;
                    }
                } else {
                    $operationProviders[$decision['provider_name']] = $record;
                }
            }
        }
        uksort($applicationProviders, static fn(string $left, string $right): int => self::providerOrder($left, $right));
        uksort($operationProviders, static fn(string $left, string $right): int => self::providerOrder($left, $right));
        uksort($retainedApplicationProviders, static fn(string $left, string $right): int => self::providerOrder($left, $right));

        $metadata = CanonicalJson::encode([
            'schema' => 'kongono.generated-extension-wiring',
            'version' => 1,
            'declaration_set_identity' => $declarationSetIdentity,
            'admission_identity' => $admissionIdentity,
            'grant_plan_identity' => $grantPlanIdentity,
            'generator_implementation_sha256' => $implementationSha256,
            'generated_namespace' => $namespace,
            'application_class' => $namespace . '\\ExtensionApplicationAssembly',
            'operation_class' => $namespace . '\\ExtensionOperationAssembly',
            'implementation_count' => count($extensions),
            'decision_count' => $decisionCount,
            'application_provider_count' => $applicationProviderCount,
            'operation_provider_count' => $operationProviderCount,
        ]);

        $lines = [
            '<?php',
            '',
            'declare(strict_types=1);',
            '',
            '// ' . $metadata,
            '',
            'namespace ' . $namespace . ';',
            '',
            'use Kongono\ExtensionComposition\Runtime\GeneratedExtensionApplicationBoundary;',
            'use Kongono\ExtensionComposition\Runtime\GeneratedExtensionOperationBoundary;',
            '',
            'final class ExtensionApplicationAssembly implements GeneratedExtensionApplicationBoundary',
            '{',
        ];
        $constructorParameters = [];
        foreach ($retainedApplicationProviders as $provider) {
            $constructorParameters[] = ['name' => $provider['name'], 'type' => $provider['contract']];
        }
        foreach ($applicationExtensions as $index => $extension) {
            $constructorParameters[] = ['name' => 'applicationExtension' . ($index + 1), 'type' => $extension['class']];
        }
        $lines = [...$lines, ...$this->constructor('private', $constructorParameters)];
        $lines[] = '';
        $lines = [...$lines, ...$this->methodHeader('public static function start', $applicationProviders, 'self')];
        foreach ($applicationExtensions as $index => $extension) {
            $variable = '$applicationExtension' . ($index + 1);
            $lines = [...$lines, ...$this->construction($variable, $extension, 'start')];
        }
        $arguments = [];
        foreach ($retainedApplicationProviders as $provider) {
            $arguments[] = [$provider['name'], '$' . $provider['name']];
        }
        foreach ($applicationExtensions as $index => $_extension) {
            $name = 'applicationExtension' . ($index + 1);
            $arguments[] = [$name, '$' . $name];
        }
        $lines = [...$lines, ...$this->returnConstruction('self', $arguments, 2)];
        $lines[] = '    }';
        $lines[] = '';
        $lines = [...$lines, ...$this->hostedDelegate(
            'public static function startHosted',
            'applicationProviders',
            $applicationProviders,
            'self',
            'self::start',
            'Hosted application provider list is invalid.',
            'Hosted application provider count is invalid.',
            'Hosted application provider type is invalid.',
        )];
        foreach ($applicationExtensions as $index => $extension) {
            $ordinal = $index + 1;
            $lines[] = '';
            $lines[] = '    public function applicationExtension' . $ordinal . '(): \\' . $extension['class'];
            $lines[] = '    {';
            $lines[] = '        return $this->applicationExtension' . $ordinal . ';';
            $lines[] = '    }';
        }
        $lines[] = '';
        $lines[] = '    public function applicationExtensions(): array';
        $lines[] = '    {';
        $applicationExtensionValues = [];
        foreach ($applicationExtensions as $index => $_extension) {
            $applicationExtensionValues[] = '$this->applicationExtension' . ($index + 1);
        }
        $lines[] = '        return [' . implode(', ', $applicationExtensionValues) . '];';
        $lines[] = '    }';
        $lines[] = '';
        $lines = [...$lines, ...$this->methodHeader('public function beginOperation', $operationProviders, 'ExtensionOperationAssembly')];
        foreach ($operationExtensions as $index => $extension) {
            $variable = '$operationExtension' . ($index + 1);
            $lines = [...$lines, ...$this->construction($variable, $extension, 'operation')];
        }
        $operationArguments = [];
        foreach ($operationExtensions as $index => $_extension) {
            $name = 'operationExtension' . ($index + 1);
            $operationArguments[] = [$name, '$' . $name];
        }
        $lines = [...$lines, ...$this->returnConstruction('ExtensionOperationAssembly', $operationArguments, 2)];
        $lines[] = '    }';
        $lines[] = '';
        $lines = [...$lines, ...$this->hostedDelegate(
            'public function beginHostedOperation',
            'operationProviders',
            $operationProviders,
            'GeneratedExtensionOperationBoundary',
            '$this->beginOperation',
            'Hosted operation provider list is invalid.',
            'Hosted operation provider count is invalid.',
            'Hosted operation provider type is invalid.',
        )];
        $lines[] = '}';
        $lines[] = '';
        $lines[] = 'final class ExtensionOperationAssembly implements GeneratedExtensionOperationBoundary';
        $lines[] = '{';
        $operationConstructor = [];
        foreach ($operationExtensions as $index => $extension) {
            $operationConstructor[] = ['name' => 'operationExtension' . ($index + 1), 'type' => $extension['class']];
        }
        $lines = [...$lines, ...$this->constructor('public', $operationConstructor)];
        foreach ($operationExtensions as $index => $extension) {
            $ordinal = $index + 1;
            $lines[] = '';
            $lines[] = '    public function operationExtension' . $ordinal . '(): \\' . $extension['class'];
            $lines[] = '    {';
            $lines[] = '        return $this->operationExtension' . $ordinal . ';';
            $lines[] = '    }';
        }
        $lines[] = '';
        $lines[] = '    public function operationExtensions(): array';
        $lines[] = '    {';
        $operationExtensionValues = [];
        foreach ($operationExtensions as $index => $_extension) {
            $operationExtensionValues[] = '$this->operationExtension' . ($index + 1);
        }
        $lines[] = '        return [' . implode(', ', $operationExtensionValues) . '];';
        $lines[] = '    }';
        $lines[] = '}';

        return implode("\n", $lines) . "\n";
    }

    /**
     * @param list<array{name:string,type:string}> $parameters
     * @return list<string>
     */
    private function constructor(string $visibility, array $parameters): array
    {
        if ([] === $parameters) {
            return ['    ' . $visibility . ' function __construct() {}'];
        }
        $lines = ['    ' . $visibility . ' function __construct('];
        foreach ($parameters as $parameter) {
            $lines[] = '        private readonly \\' . $parameter['type'] . ' $' . $parameter['name'] . ',';
        }
        $lines[] = '    ) {}';
        return $lines;
    }

    /**
     * @param array<string, array{name:string,contract:string}> $providers
     * @return list<string>
     */
    private function methodHeader(string $method, array $providers, string $return): array
    {
        if ([] === $providers) {
            return ['    ' . $method . '(): ' . $return, '    {'];
        }
        $lines = ['    ' . $method . '('];
        foreach ($providers as $provider) {
            $lines[] = '        \\' . $provider['contract'] . ' $' . $provider['name'] . ',';
        }
        $lines[] = '    ): ' . $return;
        $lines[] = '    {';
        return $lines;
    }

    /**
     * @param array{class:string,lifecycle:string,decisions:list<array<string, string>>} $extension
     * @return list<string>
     */
    private function construction(string $variable, array $extension, string $scope): array
    {
        $arguments = [];
        foreach ($extension['decisions'] as $decision) {
            if ('denied' === $decision['disposition']) {
                continue;
            }
            $expression = '$' . $decision['provider_name'];
            if ('operation' === $scope && 'application' === $decision['provider_lifetime']) {
                $expression = '$this->' . $decision['provider_name'];
            }
            $arguments[] = [$decision['parameter'], $expression];
        }
        if ([] === $arguments) {
            return ['        ' . $variable . ' = new \\' . $extension['class'] . '();'];
        }
        $lines = ['        ' . $variable . ' = new \\' . $extension['class'] . '('];
        foreach ($arguments as [$name, $expression]) {
            $lines[] = '            ' . $name . ': ' . $expression . ',';
        }
        $lines[] = '        );';
        return $lines;
    }

    /**
     * @param list<array{0:string,1:string}> $arguments
     * @return list<string>
     */
    private function returnConstruction(string $class, array $arguments, int $indent): array
    {
        $prefix = str_repeat('    ', $indent);
        if ([] === $arguments) {
            return [$prefix . 'return new ' . $class . '();'];
        }
        $lines = [$prefix . 'return new ' . $class . '('];
        foreach ($arguments as [$name, $expression]) {
            $lines[] = $prefix . '    ' . $name . ': ' . $expression . ',';
        }
        $lines[] = $prefix . ');';
        return $lines;
    }

    /**
     * @param array<string, array{name:string,contract:string}> $providers
     * @return list<string>
     */
    private function hostedDelegate(
        string $method,
        string $listName,
        array $providers,
        string $return,
        string $delegate,
        string $listMessage,
        string $countMessage,
        string $typeMessage,
    ): array {
        $lines = [
            '    ' . $method . '(array $' . $listName . '): ' . $return,
            '    {',
            '        if (!array_is_list($' . $listName . ')) {',
            "            throw new \\InvalidArgumentException('" . $listMessage . "');",
            '        }',
            '        if (' . count($providers) . ' !== count($' . $listName . ')) {',
            "            throw new \\InvalidArgumentException('" . $countMessage . "');",
            '        }',
        ];
        $arguments = [];
        foreach (array_values($providers) as $index => $provider) {
            $lines[] = '        if (!$' . $listName . '[' . $index . '] instanceof \\' . $provider['contract'] . ') {';
            $lines[] = "            throw new \\InvalidArgumentException('" . $typeMessage . "');";
            $lines[] = '        }';
            $arguments[] = [$provider['name'], '$' . $listName . '[' . $index . ']'];
        }
        if ([] === $arguments) {
            $lines[] = '        return ' . $delegate . '();';
        } else {
            $lines[] = '        return ' . $delegate . '(';
            foreach ($arguments as [$name, $expression]) {
                $lines[] = '            ' . $name . ': ' . $expression . ',';
            }
            $lines[] = '        );';
        }
        $lines[] = '    }';

        return $lines;
    }

    private static function providerOrder(string $left, string $right): int
    {
        return (int) preg_replace('/\D+/', '', $left) <=> (int) preg_replace('/\D+/', '', $right);
    }
}
