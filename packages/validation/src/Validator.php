<?php

declare(strict_types=1);

namespace Kongono\Validation;

use BackedEnum;
use Kongono\Core\CoreException;
use Kongono\Validation\Attributes\Between;
use Kongono\Validation\Attributes\Confirmed;
use Kongono\Validation\Attributes\Email;
use Kongono\Validation\Attributes\In;
use Kongono\Validation\Attributes\Max;
use Kongono\Validation\Attributes\Min;
use Kongono\Validation\Attributes\Regex;
use Kongono\Validation\Attributes\Required;
use Kongono\Validation\Attributes\Url;
use Kongono\Validation\Attributes\Uuid;
use ReflectionClass;
use ReflectionEnum;
use ReflectionNamedType;
use ReflectionParameter;

/** Validates raw request/input arrays into strictly typed PHP 8.5 DTOs (including BackedEnums and nested DTOs). */
final class Validator
{
    private const int MAX_NESTING_DEPTH = 16;

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
        $reflection = new ReflectionClass($dtoClass);
        $constructor = $reflection->getConstructor();
        if (null === $constructor) {
            return $reflection->newInstance();
        }

        $errors = [];
        $arguments = [];

        foreach ($constructor->getParameters() as $parameter) {
            $name = $parameter->getName();
            $present = array_key_exists($name, $input);
            $raw = $present ? $input[$name] : null;
            $attributes = $this->collectAttributes($reflection, $parameter);

            $fieldErrors = [];
            $isRequired = $this->hasAttribute($attributes, Required::class);

            if (!$present || null === $raw || (is_string($raw) && '' === trim($raw))) {
                if ($isRequired) {
                    $attr = $this->findAttribute($attributes, Required::class);
                    $fieldErrors[] = (null !== $attr && null !== $attr->message)
                        ? $attr->message
                        : sprintf('The %s field is required.', $name);
                } elseif ($parameter->isDefaultValueAvailable()) {
                    $arguments[] = $parameter->getDefaultValue();
                    continue;
                } elseif ($parameter->allowsNull()) {
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
                [$coerced, $typeError] = $this->coerce($parameter, $raw, $name, $depth);
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

            $ruleErrors = $this->checkAttributes($attributes, $coerced, $name, $input);
            if ([] !== $ruleErrors) {
                $errors[$name] = $ruleErrors;
                continue;
            }

            $arguments[] = $coerced;
        }

        if ([] !== $errors) {
            throw new ValidationException($errors);
        }

        return $reflection->newInstanceArgs($arguments);
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
        if ($class->hasProperty($parameter->getName())) {
            $property = $class->getProperty($parameter->getName());
            foreach ($property->getAttributes() as $attribute) {
                $instance = $attribute->newInstance();
                if (!$this->containsClass($instances, $instance::class)) {
                    $instances[] = $instance;
                }
            }
        }

        return $instances;
    }

    /**
     * @param list<object> $instances
     * @param class-string $class
     */
    private function containsClass(array $instances, string $class): bool
    {
        foreach ($instances as $instance) {
            if ($instance instanceof $class) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<object> $attributes
     * @param class-string $class
     */
    private function hasAttribute(array $attributes, string $class): bool
    {
        return $this->containsClass($attributes, $class);
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
    private function coerce(ReflectionParameter $parameter, mixed $value, string $name, int $depth = 0): array
    {
        $type = $parameter->getType();
        if (!$type instanceof ReflectionNamedType) {
            return [$value, null];
        }

        return match ($type->getName()) {
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
            default => $this->coerceObjectOrEnum($type->getName(), $value, $name, $depth),
        };
    }

    /** @return array{0: mixed, 1: ?string} */
    private function coerceObjectOrEnum(string $typeName, mixed $value, string $name, int $depth = 0): array
    {
        if (is_subclass_of($typeName, BackedEnum::class)) {
            if ($value instanceof $typeName) {
                return [$value, null];
            }

            $enumRef = new ReflectionEnum($typeName);
            $backingType = $enumRef->getBackingType();
            $backingName = $backingType instanceof ReflectionNamedType ? $backingType->getName() : 'string';

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
            if ($attribute instanceof Min) {
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
