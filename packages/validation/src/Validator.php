<?php

declare(strict_types=1);

namespace Kongono\Validation;

use BackedEnum;
use Kongono\Core\CoreException;
use Kongono\Validation\Attributes\ArrayOf;
use Kongono\Validation\Attributes\Between;
use Kongono\Validation\Attributes\Confirmed;
use Kongono\Validation\Attributes\Email;
use Kongono\Validation\Attributes\In;
use Kongono\Validation\Attributes\Max;
use Kongono\Validation\Attributes\Min;
use Kongono\Validation\Attributes\Regex;
use Kongono\Validation\Attributes\Required;
use Kongono\Validation\Attributes\RequiredIf;
use Kongono\Validation\Attributes\Url;
use Kongono\Validation\Attributes\Uuid;
use ReflectionClass;
use ReflectionEnum;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * Validates raw request/input arrays into strictly typed PHP 8.5 DTOs
 * (supporting BackedEnums, nested DTOs, typed `#[ArrayOf]` collections,
 * `#[RequiredIf]` conditionals, custom `RuleInterface` attributes, and
 * `ValidatableDtoInterface` cross-field checks).
 */
final class Validator
{
    private const int MAX_NESTING_DEPTH = 16;

    /**
     * Compiled per-DTO validation metadata cached once per persistent worker process.
     *
     * @var array<string, list<array{
     *     name: string,
     *     typeName: ?string,
     *     hasDefault: bool,
     *     default: mixed,
     *     allowsNull: bool,
     *     isRequired: bool,
     *     requiredMessage: ?string,
     *     requiredIf: list<RequiredIf>,
     *     arrayOf: ?ArrayOf,
     *     attributes: list<object>
     * }>>
     */
    private array $schemaCache = [];

    /** @var array<string, string> */
    private array $enumBackingCache = [];

    /**
     * Validate and hydrate an associative array into a typed DTO class.
     *
     * @template T of object
     * @param class-string<T>      $dtoClass
     * @param array<string, mixed> $input
     * @return T
     *
     * @throws ValidationException
     */
    public function validate(string $dtoClass, array $input, int $depth = 0): object
    {
        if ($depth > self::MAX_NESTING_DEPTH) {
            throw new ValidationException(['_root' => ['Nested input exceeded maximum depth limit.']]);
        }

        $schema = $this->schemaCache[$dtoClass] ??= $this->compileSchema($dtoClass);
        if ([] === $schema) {
            $dto = new $dtoClass();
            $this->runDtoHook($dto);

            return $dto;
        }

        $errors = [];
        $arguments = [];

        foreach ($schema as $field) {
            $name = $field['name'];
            $present = array_key_exists($name, $input);
            $raw = $present ? $input[$name] : null;
            $fieldErrors = [];

            if (!$present || null === $raw || (is_string($raw) && '' === trim($raw))) {
                $conditionalError = $this->checkRequiredIf($field['requiredIf'], $name, $input);
                if ($field['isRequired']) {
                    $fieldErrors[] = $field['requiredMessage'] ?? sprintf('The %s field is required.', $name);
                } elseif (null !== $conditionalError) {
                    $fieldErrors[] = $conditionalError;
                } elseif ($field['hasDefault']) {
                    $arguments[] = $field['default'];
                    continue;
                } elseif ($field['allowsNull']) {
                    $arguments[] = null;
                    continue;
                } else {
                    $fieldErrors[] = sprintf('The %s field is required.', $name);
                }
            }

            if ([] !== $fieldErrors) {
                $errors[$name] = $fieldErrors;
                continue;
            }

            try {
                [$coerced, $typeError] = $this->coerceByTypeName($field['typeName'], $raw, $name, $depth);
            } catch (ValidationException $nested) {
                foreach ($nested->errors as $subField => $subMessages) {
                    $errors[$name . '.' . $subField] = $subMessages;
                }
                continue;
            }
            if (null !== $typeError) {
                $errors[$name][] = $typeError;
                continue;
            }

            $ruleErrors = $this->checkAttributes($field['attributes'], $coerced, $name, $input);
            if ([] !== $ruleErrors) {
                $errors[$name] = $ruleErrors;
                continue;
            }

            if ('array' === $field['typeName'] && null !== $field['arrayOf'] && is_array($coerced)) {
                $itemErrors = [];
                $hydratedItems = $this->validateArrayItems($coerced, $field['arrayOf'], $name, $input, $depth, $itemErrors);
                if ([] !== $itemErrors) {
                    foreach ($itemErrors as $itemField => $msgs) {
                        $errors[$itemField] = $msgs;
                    }
                    continue;
                }
                $coerced = $hydratedItems;
            }

            $arguments[] = $coerced;
        }

        if ([] !== $errors) {
            throw new ValidationException($errors);
        }

        $dto = new $dtoClass(...$arguments);
        $this->runDtoHook($dto);

        return $dto;
    }

    private function runDtoHook(object $dto): void
    {
        if (!$dto instanceof ValidatableDtoInterface) {
            return;
        }

        $rawErrors = $dto->validate();
        if ([] === $rawErrors) {
            return;
        }

        /** @var array<string, list<string>> $normalized */
        $normalized = [];
        foreach ($rawErrors as $field => $messages) {
            $list = is_array($messages)
                ? array_values(array_filter($messages, 'is_string'))
                : [$messages];
            if ([] !== $list) {
                $normalized[$field] = $list;
            }
        }

        if ([] !== $normalized) {
            throw new ValidationException($normalized);
        }
    }

    /**
     * @param list<RequiredIf>     $conditions
     * @param array<string, mixed> $allInput
     */
    private function checkRequiredIf(array $conditions, string $field, array $allInput): ?string
    {
        foreach ($conditions as $condition) {
            if ($condition->isTriggered($allInput)) {
                return $condition->message ?? sprintf('The %s field is required.', $field);
            }
        }

        return null;
    }

    /**
     * @param array<mixed>                 $items
     * @param array<string, mixed>         $allInput
     * @param array<string, list<string>>  $errors
     * @return array<mixed>
     */
    private function validateArrayItems(
        array $items,
        ArrayOf $arrayOf,
        string $fieldName,
        array $allInput,
        int $depth,
        array &$errors,
    ): array {
        if (count($items) > $arrayOf->maxItems) {
            $errors[$fieldName] = [
                $arrayOf->message ?? sprintf('The %s field must contain at most %d items.', $fieldName, $arrayOf->maxItems),
            ];

            return [];
        }

        $validated = [];
        foreach ($items as $idx => $rawItem) {
            $itemKey = $fieldName . '.' . (string) $idx;
            try {
                [$coercedItem, $itemTypeError] = $this->coerceByTypeName($arrayOf->type, $rawItem, $itemKey, $depth + 1);
            } catch (ValidationException $nested) {
                foreach ($nested->errors as $subField => $subMessages) {
                    $errors[$itemKey . '.' . $subField] = $subMessages;
                }
                continue;
            }

            if (null !== $itemTypeError) {
                $errors[$itemKey][] = $arrayOf->message ?? $itemTypeError;
                continue;
            }

            if ([] !== $arrayOf->rules) {
                $itemRuleErrors = $this->checkAttributes($arrayOf->rules, $coercedItem, $itemKey, $allInput);
                if ([] !== $itemRuleErrors) {
                    $errors[$itemKey] = $itemRuleErrors;
                    continue;
                }
            }

            $validated[$idx] = $coercedItem;
        }

        return $validated;
    }

    /**
     * @param class-string $dtoClass
     * @return list<array{
     *     name: string,
     *     typeName: ?string,
     *     hasDefault: bool,
     *     default: mixed,
     *     allowsNull: bool,
     *     isRequired: bool,
     *     requiredMessage: ?string,
     *     requiredIf: list<RequiredIf>,
     *     arrayOf: ?ArrayOf,
     *     attributes: list<object>
     * }>
     */
    private function compileSchema(string $dtoClass): array
    {
        $reflection = new ReflectionClass($dtoClass);
        $constructor = $reflection->getConstructor();
        if (null === $constructor) {
            return [];
        }

        $schema = [];
        foreach ($constructor->getParameters() as $parameter) {
            $attributes = $this->collectAttributes($reflection, $parameter);
            $requiredAttr = $this->findAttribute($attributes, Required::class);
            $arrayOfAttr = $this->findAttribute($attributes, ArrayOf::class);
            $requiredIfAttrs = [];
            foreach ($attributes as $attr) {
                if ($attr instanceof RequiredIf) {
                    $requiredIfAttrs[] = $attr;
                }
            }
            $type = $parameter->getType();
            $hasDefault = $parameter->isDefaultValueAvailable();

            $schema[] = [
                'name' => $parameter->getName(),
                'typeName' => $type instanceof ReflectionNamedType ? $type->getName() : null,
                'hasDefault' => $hasDefault,
                'default' => $hasDefault ? $parameter->getDefaultValue() : null,
                'allowsNull' => $parameter->allowsNull(),
                'isRequired' => null !== $requiredAttr,
                'requiredMessage' => $requiredAttr?->message,
                'requiredIf' => $requiredIfAttrs,
                'arrayOf' => $arrayOfAttr,
                'attributes' => $attributes,
            ];
        }

        return $schema;
    }

    /**
     * @param ReflectionClass<object> $class
     * @return list<object>
     */
    private function collectAttributes(ReflectionClass $class, ReflectionParameter $parameter): array
    {
        $instances = [];
        foreach ($parameter->getAttributes() as $attribute) {
            $instances[] = $attribute->newInstance();
        }
        // Promoted constructor parameters already carry property attributes on $parameter;
        // only inspect $property separately for non-promoted properties to avoid duplicating repeatable attributes.
        if (!$parameter->isPromoted() && $class->hasProperty($parameter->getName())) {
            $property = $class->getProperty($parameter->getName());
            foreach ($property->getAttributes() as $attribute) {
                $instances[] = $attribute->newInstance();
            }
        }

        return $instances;
    }

    /**
     * @template T of object
     * @param list<object>    $attributes
     * @param class-string<T> $class
     * @return ?T
     */
    private function findAttribute(array $attributes, string $class): ?object
    {
        foreach ($attributes as $attribute) {
            if ($attribute instanceof $class) {
                return $attribute;
            }
        }

        return null;
    }

    /** @return array{0: mixed, 1: ?string} */
    private function coerceByTypeName(?string $typeName, mixed $value, string $name, int $depth = 0): array
    {
        if (null === $typeName) {
            return [$value, null];
        }

        return match ($typeName) {
            'string' => is_string($value)
                ? [trim($value), null]
                : [null, sprintf('The %s field must be a string.', $name)],
            'int' => $this->coerceInt($value, $name),
            'float' => $this->coerceFloat($value, $name),
            'bool' => is_bool($value)
                ? [$value, null]
                : (is_string($value) && in_array(strtolower(trim($value)), ['1', '0', 'true', 'false', 'yes', 'no', 'on', 'off'], true)
                    ? [in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true), null]
                    : [null, sprintf('The %s field must be a boolean.', $name)]),
            'array' => is_array($value)
                ? [$value, null]
                : [null, sprintf('The %s field must be an array.', $name)],
            default => $this->coerceObjectOrEnum($typeName, $value, $name, $depth),
        };
    }

    /** @return array{0: mixed, 1: ?string} */
    private function coerceObjectOrEnum(string $typeName, mixed $value, string $name, int $depth = 0): array
    {
        if (is_subclass_of($typeName, BackedEnum::class)) {
            if ($value instanceof $typeName) {
                return [$value, null];
            }

            $backingName = $this->enumBackingCache[$typeName] ??= (static function () use ($typeName): string {
                $backingType = (new ReflectionEnum($typeName))->getBackingType();

                return $backingType instanceof ReflectionNamedType ? $backingType->getName() : 'string';
            })();

            $candidate = match ($backingName) {
                'int' => is_int($value) ? $value : (is_string($value) && 1 === preg_match('/\A-?\d+\z/', trim($value)) ? (int) trim($value) : null),
                default => is_string($value) ? trim($value) : null,
            };

            if (null !== $candidate) {
                $case = $typeName::tryFrom($candidate);
                if (null !== $case) {
                    return [$case, null];
                }
            }

            $allowed = array_map(static fn(BackedEnum $c): string => (string) $c->value, $typeName::cases());

            return [null, sprintf('The %s field must be one of: %s.', $name, implode(', ', $allowed))];
        }

        if (enum_exists($typeName)) {
            throw new CoreException(sprintf('Unsupported non-backed UnitEnum DTO field type "%s" for "$%s". Use a BackedEnum.', $typeName, $name));
        }

        if (class_exists($typeName)) {
            if (str_starts_with($typeName, 'Kongono\\') && !str_contains($typeName, '\\Tests\\')) {
                throw new CoreException(sprintf('Refusing to hydrate framework class "%s" from untrusted input for "$%s".', $typeName, $name));
            }
            if ($value instanceof $typeName) {
                return [$value, null];
            }
            if (is_array($value)) {
                /** @var array<string, mixed> $nestedInput */
                $nestedInput = [];
                foreach ($value as $k => $v) {
                    $nestedInput[(string) $k] = $v;
                }

                return [$this->validate($typeName, $nestedInput, $depth + 1), null];
            }

            return [null, sprintf('The %s field must be an object.', $name)];
        }

        throw new CoreException(sprintf('Unsupported DTO field type "%s" for "$%s".', $typeName, $name));
    }

    /**
     * @param list<object>         $attributes
     * @param array<string, mixed> $allInput
     * @return list<string>
     */
    private function checkAttributes(array $attributes, mixed $value, string $name, array $allInput): array
    {
        $errors = [];

        foreach ($attributes as $attribute) {
            if ($attribute instanceof RuleInterface) {
                $error = $attribute->validate($value, $name, $allInput);
                if (null !== $error) {
                    $errors[] = $error;
                }
            } elseif ($attribute instanceof Min) {
                if (is_string($value)) {
                    if (mb_strlen($value) < $attribute->value) {
                        $errors[] = $attribute->message ?? sprintf('The %s field must be at least %s characters.', $name, (string) $attribute->value);
                    }
                } elseif (is_int($value) || is_float($value)) {
                    if ((is_float($value) && !is_finite($value)) || $value < $attribute->value) {
                        $errors[] = $attribute->message ?? sprintf('The %s field must be at least %s.', $name, (string) $attribute->value);
                    }
                } elseif (is_array($value)) {
                    if (count($value) < $attribute->value) {
                        $errors[] = $attribute->message ?? sprintf('The %s field must contain at least %s items.', $name, (string) $attribute->value);
                    }
                } else {
                    $errors[] = $attribute->message ?? sprintf('The %s field must be at least %s.', $name, (string) $attribute->value);
                }
            } elseif ($attribute instanceof Max) {
                if (is_string($value)) {
                    if (mb_strlen($value) > $attribute->value) {
                        $errors[] = $attribute->message ?? sprintf('The %s field must be at most %s characters.', $name, (string) $attribute->value);
                    }
                } elseif (is_int($value) || is_float($value)) {
                    if ((is_float($value) && !is_finite($value)) || $value > $attribute->value) {
                        $errors[] = $attribute->message ?? sprintf('The %s field must be at most %s.', $name, (string) $attribute->value);
                    }
                } elseif (is_array($value)) {
                    if (count($value) > $attribute->value) {
                        $errors[] = $attribute->message ?? sprintf('The %s field must contain at most %s items.', $name, (string) $attribute->value);
                    }
                } else {
                    $errors[] = $attribute->message ?? sprintf('The %s field must be at most %s.', $name, (string) $attribute->value);
                }
            } elseif ($attribute instanceof Between) {
                if (is_string($value)) {
                    $len = mb_strlen($value);
                    if ($len < $attribute->min || $len > $attribute->max) {
                        $errors[] = $attribute->message ?? sprintf('The %s field must be between %s and %s characters.', $name, (string) $attribute->min, (string) $attribute->max);
                    }
                } elseif (is_int($value) || is_float($value)) {
                    if ((is_float($value) && !is_finite($value)) || $value < $attribute->min || $value > $attribute->max) {
                        $errors[] = $attribute->message ?? sprintf('The %s field must be between %s and %s.', $name, (string) $attribute->min, (string) $attribute->max);
                    }
                } elseif (is_array($value)) {
                    $count = count($value);
                    if ($count < $attribute->min || $count > $attribute->max) {
                        $errors[] = $attribute->message ?? sprintf('The %s field must contain between %s and %s items.', $name, (string) $attribute->min, (string) $attribute->max);
                    }
                } else {
                    $errors[] = $attribute->message ?? sprintf('The %s field must be between %s and %s.', $name, (string) $attribute->min, (string) $attribute->max);
                }
            } elseif ($attribute instanceof Email) {
                if (!is_string($value) || false === filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = $attribute->message ?? sprintf('The %s field must be a valid email address.', $name);
                }
            } elseif ($attribute instanceof Url) {
                $validUrl = false;
                if (is_string($value) && false === strpbrk($value, "\r\n\0") && false !== filter_var($value, FILTER_VALIDATE_URL)) {
                    $parsed = parse_url($value);
                    $scheme = is_array($parsed) && is_string($parsed['scheme'] ?? null) ? strtolower($parsed['scheme']) : '';
                    $host = is_array($parsed) && is_string($parsed['host'] ?? null) ? $parsed['host'] : '';
                    $allowed = array_map('strtolower', $attribute->allowedSchemes);
                    $validUrl = '' !== $host && in_array($scheme, $allowed, true);
                }
                if (!$validUrl) {
                    $errors[] = $attribute->message ?? sprintf('The %s field must be a valid URL.', $name);
                }
            } elseif ($attribute instanceof Uuid) {
                if (!is_string($value) || 1 !== preg_match('/\A[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/i', $value)) {
                    $errors[] = $attribute->message ?? sprintf('The %s field must be a valid UUID.', $name);
                }
            } elseif ($attribute instanceof In) {
                if (!in_array($value, $attribute->allowed, true)) {
                    $errors[] = $attribute->message ?? sprintf('The %s field must be one of: %s.', $name, implode(', ', array_map('strval', $attribute->allowed)));
                }
            } elseif ($attribute instanceof Regex) {
                if (!is_string($value) || 1 !== preg_match($attribute->pattern, $value)) {
                    $errors[] = $attribute->message ?? sprintf('The %s field format is invalid.', $name);
                }
            } elseif ($attribute instanceof Confirmed) {
                $other = $allInput[$attribute->otherField] ?? null;
                $normalizedOther = is_string($other) ? trim($other) : $other;
                if ($value !== $normalizedOther) {
                    $errors[] = $attribute->message ?? sprintf('The %s field must match %s.', $name, $attribute->otherField);
                }
            }
        }

        return $errors;
    }

    /** @return array{0: mixed, 1: ?string} */
    private function coerceInt(mixed $value, string $name): array
    {
        if (is_int($value)) {
            return [$value, null];
        }
        if (is_string($value)) {
            $trimmed = trim($value);
            if (1 === preg_match('/\A-?\d+\z/', $trimmed)) {
                $validated = filter_var($trimmed, FILTER_VALIDATE_INT);
                if (false !== $validated) {
                    return [$validated, null];
                }
            }
        }

        return [null, sprintf('The %s field must be an integer.', $name)];
    }

    /** @return array{0: mixed, 1: ?string} */
    private function coerceFloat(mixed $value, string $name): array
    {
        if (is_int($value)) {
            return [(float) $value, null];
        }
        if (is_float($value)) {
            return is_finite($value)
                ? [$value, null]
                : [null, sprintf('The %s field must be a finite number.', $name)];
        }
        if (is_string($value) && is_numeric(trim($value))) {
            $floatVal = (float) trim($value);

            return is_finite($floatVal)
                ? [$floatVal, null]
                : [null, sprintf('The %s field must be a finite number.', $name)];
        }

        return [null, sprintf('The %s field must be a number.', $name)];
    }
}
