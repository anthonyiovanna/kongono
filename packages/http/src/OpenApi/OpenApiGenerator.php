<?php

declare(strict_types=1);

namespace Kongono\Http\OpenApi;

use BackedEnum;
use Closure;
use Kongono\Http\App;
use Kongono\Http\Request;
use Kongono\Http\Routing\Route;
use Kongono\Validation\Attributes\ArrayOf;
use Kongono\Validation\Attributes\Between;
use Kongono\Validation\Attributes\Date;
use Kongono\Validation\Attributes\Email;
use Kongono\Validation\Attributes\In;
use Kongono\Validation\Attributes\Ip;
use Kongono\Validation\Attributes\Max;
use Kongono\Validation\Attributes\Min;
use Kongono\Validation\Attributes\Required;
use Kongono\Validation\Attributes\Url;
use Kongono\Validation\Attributes\Uuid;
use Kongono\Validation\RuleInterface;
use Kongono\Validation\ValidatableDtoInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * Generates a live OpenAPI 3.1.0 specification directly from Kongono routes,
 * PHP 8.5 controller types, and validation attributes — zero Swagger annotations required.
 */
final class OpenApiGenerator
{
    /** @return array<string, mixed> */
    public function generate(App $app, string $version = '1.0.0'): array
    {
        $graph = $app->graph();
        $paths = [];
        $schemas = [];

        foreach ($app->router()->routes() as $route) {
            $openApiPath = (string) preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)(?::[^}]+)?\}/', '{$1}', $route->path);
            foreach ($route->methods as $method) {
                if ('HEAD' === $method) {
                    continue;
                }
                $lower = strtolower($method);
                $operation = $this->buildOperation($route, $schemas, $app, $method);
                $paths[$openApiPath][$lower] = $operation;
            }
        }

        $spec = [
            'openapi' => '3.1.0',
            'info' => [
                'title' => $graph->appName,
                'version' => $version,
            ],
            'paths' => $paths,
        ];

        if ([] !== $schemas) {
            $spec['components'] = ['schemas' => $schemas];
        }

        return $spec;
    }

    public function generateJson(App $app, string $version = '1.0.0', bool $pretty = true): string
    {
        $flags = JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES;
        if ($pretty) {
            $flags |= JSON_PRETTY_PRINT;
        }

        return json_encode($this->generate($app, $version), $flags);
    }

    /**
     * @param array<string, array<string, mixed>> $schemas
     * @return array<string, mixed>
     */
    private function buildOperation(Route $route, array &$schemas, App $app, string $method): array
    {
        $operation = [
            'operationId' => $route->routeName() ?? strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '_', $route->path)),
            'responses' => [
                '200' => ['description' => 'Successful response'],
            ],
        ];

        $parameters = [];
        if (1 <= (int) preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)(?::([^}]+))?\}/', $route->path, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $isInt = isset($m[2]) && '\\d+' === $m[2];
                $parameters[] = [
                    'name' => $m[1],
                    'in' => 'path',
                    'required' => true,
                    'schema' => ['type' => $isInt ? 'integer' : 'string'],
                ];
            }
        }

        $reflection = $route->handler instanceof Closure
            ? new ReflectionFunction($route->handler)
            : new ReflectionMethod($route->handler[0], $route->handler[1]);

        $registeredBindings = $app->container()->registeredBindings();
        $allowsBody = !in_array(strtoupper($method), ['GET', 'HEAD', 'OPTIONS'], true);

        foreach ($reflection->getParameters() as $param) {
            $type = $param->getType();
            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                continue;
            }
            $typeName = $type->getName();
            if (Request::class === $typeName || ServerRequestInterface::class === $typeName || !class_exists($typeName)) {
                continue;
            }
            if ($allowsBody && $this->isInputDto($typeName, $registeredBindings)) {
                $shortName = (new ReflectionClass($typeName))->getShortName();
                $schemas[$shortName] = $this->buildDtoSchema($typeName, $schemas);
                $operation['requestBody'] = [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => ['$ref' => '#/components/schemas/' . $shortName],
                        ],
                    ],
                ];
                $operation['responses']['422'] = ['description' => 'Validation failed'];
            }
        }

        if ([] !== $parameters) {
            $operation['parameters'] = $parameters;
        }

        return $operation;
    }

    /**
     * @param class-string          $className
     * @param array<string, mixed>  $registeredBindings
     * @param array<class-string, true> $visited
     */
    private function isInputDto(string $className, array $registeredBindings, array $visited = [], int $depth = 0): bool
    {
        // Exclude internal classes, framework/security classes (Principal, Session, etc.), and container services
        if ((str_starts_with($className, 'Kongono\\') && !str_contains($className, '\\Tests\\')) || array_key_exists($className, $registeredBindings) || $depth > 16) {
            return false;
        }
        if (isset($visited[$className])) {
            return true;
        }

        $ref = new ReflectionClass($className);
        if (!$ref->isInstantiable() || $ref->isInternal()) {
            return false;
        }
        $ctor = $ref->getConstructor();
        if (null === $ctor || 0 === $ctor->getNumberOfParameters()) {
            return false;
        }

        $visited[$className] = true;
        $hasRequiredOrValidated = $ref->implementsInterface(ValidatableDtoInterface::class);
        foreach ($ctor->getParameters() as $p) {
            $type = $p->getType();
            if (!$type instanceof ReflectionNamedType) {
                return false;
            }
            $isField = false;
            if ($type->isBuiltin()) {
                $isField = true;
            } else {
                $pType = $type->getName();
                if (is_subclass_of($pType, BackedEnum::class)) {
                    $isField = true;
                } elseif (class_exists($pType) && $this->isInputDto($pType, $registeredBindings, $visited, $depth + 1)) {
                    $isField = true;
                }
            }
            if (!$isField) {
                return false;
            }
            if (!$p->isDefaultValueAvailable()) {
                $hasRequiredOrValidated = true;
            } else {
                foreach ($p->getAttributes() as $a) {
                    $attrName = $a->getName();
                    if (str_starts_with($attrName, 'Kongono\\Validation\\Attributes\\') || is_subclass_of($attrName, RuleInterface::class)) {
                        $hasRequiredOrValidated = true;
                        break;
                    }
                }
            }
        }

        return $hasRequiredOrValidated;
    }

    /**
     * @param class-string                        $className
     * @param array<string, array<string, mixed>> $schemas
     * @return array<string, mixed>
     */
    private function buildDtoSchema(string $className, array &$schemas): array
    {
        $ref = new ReflectionClass($className);
        $ctor = $ref->getConstructor();
        $properties = [];
        $required = [];

        if (null !== $ctor) {
            foreach ($ctor->getParameters() as $param) {
                $name = $param->getName();
                $propSchema = $this->buildParameterSchema($ref, $param, $required, $schemas);
                $properties[$name] = $propSchema;
            }
        }

        $schema = [
            'type' => 'object',
            'properties' => $properties,
        ];
        if ([] !== $required) {
            $schema['required'] = $required;
        }

        return $schema;
    }

    /**
     * @param ReflectionClass<object>             $class
     * @param list<string>                        $required
     * @param array<string, array<string, mixed>> $schemas
     * @return array<string, mixed>
     */
    private function buildParameterSchema(ReflectionClass $class, ReflectionParameter $param, array &$required, array &$schemas): array
    {
        $name = $param->getName();
        $type = $param->getType();
        $phpType = $type instanceof ReflectionNamedType ? $type->getName() : 'string';
        $schema = $this->mapTypeToOpenApiSchema($phpType, $schemas);
        $jsonType = is_string($schema['type'] ?? null) ? $schema['type'] : 'string';

        $attrs = [];
        foreach ($param->getAttributes() as $a) {
            if (str_starts_with($a->getName(), 'Kongono\\Validation\\Attributes\\')) {
                $attrs[] = $a->newInstance();
            }
        }
        if (!$param->isPromoted() && $class->hasProperty($name)) {
            foreach ($class->getProperty($name)->getAttributes() as $a) {
                if (str_starts_with($a->getName(), 'Kongono\\Validation\\Attributes\\')) {
                    $attrs[] = $a->newInstance();
                }
            }
        }

        $isRequired = !$param->isDefaultValueAvailable() && !$param->allowsNull();
        foreach ($attrs as $attr) {
            if ($attr instanceof Required) {
                $isRequired = true;
            } elseif ($attr instanceof Min) {
                $key = match ($jsonType) {
                    'string' => 'minLength',
                    'array' => 'minItems',
                    default => 'minimum',
                };
                $schema[$key] = $attr->value;
            } elseif ($attr instanceof Max) {
                $key = match ($jsonType) {
                    'string' => 'maxLength',
                    'array' => 'maxItems',
                    default => 'maximum',
                };
                $schema[$key] = $attr->value;
            } elseif ($attr instanceof Between) {
                if ('string' === $jsonType) {
                    $schema['minLength'] = $attr->min;
                    $schema['maxLength'] = $attr->max;
                } elseif ('array' === $jsonType) {
                    $schema['minItems'] = $attr->min;
                    $schema['maxItems'] = $attr->max;
                } else {
                    $schema['minimum'] = $attr->min;
                    $schema['maximum'] = $attr->max;
                }
            } elseif ($attr instanceof Email) {
                $schema['format'] = 'email';
            } elseif ($attr instanceof Url) {
                $schema['format'] = 'uri';
            } elseif ($attr instanceof Uuid) {
                $schema['format'] = 'uuid';
            } elseif ($attr instanceof Date) {
                $schema['format'] = null !== $attr->format && str_contains($attr->format, 'H') ? 'date-time' : 'date';
            } elseif ($attr instanceof Ip) {
                $schema['format'] = match ($attr->version) {
                    'v4' => 'ipv4',
                    'v6' => 'ipv6',
                    default => 'ip',
                };
            } elseif ($attr instanceof In) {
                $schema['enum'] = $attr->allowed;
            } elseif ($attr instanceof ArrayOf && 'array' === $jsonType) {
                $schema['items'] = $this->mapTypeToOpenApiSchema($attr->type, $schemas);
            }
        }

        if ($isRequired && !in_array($name, $required, true)) {
            $required[] = $name;
        }

        return $schema;
    }

    /**
     * @param array<string, array<string, mixed>> $schemas
     * @return array<string, mixed>
     */
    private function mapTypeToOpenApiSchema(string $phpType, array &$schemas): array
    {
        if (in_array($phpType, ['string', 'int', 'float', 'bool', 'array'], true)) {
            return [
                'type' => match ($phpType) {
                    'int' => 'integer',
                    'float' => 'number',
                    'bool' => 'boolean',
                    'array' => 'array',
                    default => 'string',
                },
            ];
        }

        if (is_subclass_of($phpType, BackedEnum::class)) {
            $cases = array_map(static fn(BackedEnum $c): int|string => $c->value, $phpType::cases());
            $isInt = [] !== $cases && is_int($cases[0]);

            return [
                'type' => $isInt ? 'integer' : 'string',
                'enum' => $cases,
            ];
        }

        if (class_exists($phpType) && !(str_starts_with($phpType, 'Kongono\\') && !str_contains($phpType, '\\Tests\\'))) {
            $short = (new ReflectionClass($phpType))->getShortName();
            if (!isset($schemas[$short])) {
                $schemas[$short] = [];
                $schemas[$short] = $this->buildDtoSchema($phpType, $schemas);
            }

            return ['$ref' => '#/components/schemas/' . $short];
        }

        return ['type' => 'string'];
    }
}
