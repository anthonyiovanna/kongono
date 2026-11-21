<?php

declare(strict_types=1);

namespace Kongono\Validation;

use Kongono\Core\CoreException;
use Kongono\Validation\Attributes\Email;
use Kongono\Validation\Attributes\In;
use Kongono\Validation\Attributes\Max;
use Kongono\Validation\Attributes\Min;
use Kongono\Validation\Attributes\Regex;
use Kongono\Validation\Attributes\Required;
use Kongono\Validation\Attributes\Url;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;

/** Validates raw request/input arrays into strictly typed PHP 8.5 DTOs. */
final class Validator
{
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
    public function validate(string $dtoClass, array $input): object
    {
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

            [$coerced, $typeError] = $this->coerce($parameter, $raw, $name);
            if (null !== $typeError) {
                $errors[$name][] = $typeError;
                continue;
            }

            $ruleErrors = $this->checkAttributes($attributes, $coerced, $name);
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
    private function coerce(ReflectionParameter $parameter, mixed $value, string $name): array
    {
        $type = $parameter->getType();
        if (!$type instanceof ReflectionNamedType) {
            return [$value, null];
        }

        return match ($type->getName()) {
            'string' => is_string($value)
                ? [trim($value), null]
                : [null, sprintf('The %s field must be a string.', $name)],
            'int' => is_int($value)
                ? [$value, null]
                : (is_string($value) && 1 === preg_match('/\A-?\d+\z/', trim($value))
                    ? [(int) trim($value), null]
                    : [null, sprintf('The %s field must be an integer.', $name)]),
            'float' => is_int($value) || is_float($value)
                ? [(float) $value, null]
                : (is_string($value) && is_numeric(trim($value))
                    ? [(float) trim($value), null]
                    : [null, sprintf('The %s field must be a number.', $name)]),
            'bool' => is_bool($value)
                ? [$value, null]
                : (is_string($value) && in_array(strtolower(trim($value)), ['1', '0', 'true', 'false', 'yes', 'no', 'on', 'off'], true)
                    ? [in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true), null]
                    : [null, sprintf('The %s field must be a boolean.', $name)]),
            'array' => is_array($value)
                ? [$value, null]
                : [null, sprintf('The %s field must be an array.', $name)],
            default => $value instanceof ($type->getName())
                ? [$value, null]
                : throw new CoreException(sprintf('Unsupported DTO field type "%s" for "$%s".', $type->getName(), $name)),
        };
    }

    /**
     * @param list<object> $attributes
     * @return list<string>
     */
    private function checkAttributes(array $attributes, mixed $value, string $name): array
    {
        $errors = [];

        foreach ($attributes as $attribute) {
            if ($attribute instanceof Min) {
                if (is_string($value) && mb_strlen($value) < $attribute->value) {
                    $errors[] = $attribute->message ?? sprintf('The %s field must be at least %s characters.', $name, (string) $attribute->value);
                } elseif ((is_int($value) || is_float($value)) && $value < $attribute->value) {
                    $errors[] = $attribute->message ?? sprintf('The %s field must be at least %s.', $name, (string) $attribute->value);
                }
            } elseif ($attribute instanceof Max) {
                if (is_string($value) && mb_strlen($value) > $attribute->value) {
                    $errors[] = $attribute->message ?? sprintf('The %s field must be at most %s characters.', $name, (string) $attribute->value);
                } elseif ((is_int($value) || is_float($value)) && $value > $attribute->value) {
                    $errors[] = $attribute->message ?? sprintf('The %s field must be at most %s.', $name, (string) $attribute->value);
                }
            } elseif ($attribute instanceof Email) {
                if (!is_string($value) || false === filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = $attribute->message ?? sprintf('The %s field must be a valid email address.', $name);
                }
            } elseif ($attribute instanceof Url) {
                if (!is_string($value) || false === filter_var($value, FILTER_VALIDATE_URL)) {
                    $errors[] = $attribute->message ?? sprintf('The %s field must be a valid URL.', $name);
                }
            } elseif ($attribute instanceof In) {
                if (!in_array($value, $attribute->allowed, true)) {
                    $errors[] = $attribute->message ?? sprintf('The %s field must be one of: %s.', $name, implode(', ', array_map('strval', $attribute->allowed)));
                }
            } elseif ($attribute instanceof Regex) {
                if (!is_string($value) || 1 !== preg_match($attribute->pattern, $value)) {
                    $errors[] = $attribute->message ?? sprintf('The %s field format is invalid.', $name);
                }
            }
        }

        return $errors;
    }
}
