<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceBindingBuild\Source;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceBindingBuild\Exception\InvalidExternalServiceBindingSourceException;
use Kongono\ExternalServiceContract\Binding\ServiceBinding;

/** Holds one complete canonical secret-free binding source supplied by the caller. */
#[Api(ApiStatus::Experimental)]
final readonly class ExternalServiceBindingSource
{
    /** @var list<ServiceBinding> */
    private array $bindings;

    /** @param list<ServiceBinding> $bindings */
    public function __construct(
        private ExternalServiceBindingSourceIdentity $identity,
        array $bindings,
    ) {
        self::validateBindings($bindings);
        $this->bindings = [...$bindings];
    }

    public function identity(): ExternalServiceBindingSourceIdentity
    {
        return $this->identity;
    }

    /** @return list<ServiceBinding> */
    public function bindings(): array
    {
        return [...$this->bindings];
    }

    public function equals(self $other): bool
    {
        if (!$this->identity->equals($other->identity) || count($this->bindings) !== count($other->bindings)) {
            return false;
        }
        foreach ($this->bindings as $position => $binding) {
            if (!$binding->equals($other->bindings[$position])) {
                return false;
            }
        }

        return true;
    }

    /** @param array<array-key, mixed> $bindings */
    private static function validateBindings(array $bindings): void
    {
        if (!array_is_list($bindings)) {
            throw InvalidExternalServiceBindingSourceException::invalid('bindings_not_list', 'bindings', count($bindings));
        }
        $count = count($bindings);
        if ($count < 1 || $count > 64) {
            throw InvalidExternalServiceBindingSourceException::invalid('binding_count', 'bindings', $count, limit: 64);
        }

        $previous = null;
        $profiles = [];
        foreach ($bindings as $position => $binding) {
            if (!$binding instanceof ServiceBinding) {
                throw InvalidExternalServiceBindingSourceException::invalid('invalid_binding_entry', 'bindings', $count, $position);
            }
            $identity = $binding->identity()->value();
            if (null !== $previous && 0 <= strcmp($previous, $identity)) {
                throw InvalidExternalServiceBindingSourceException::invalid(
                    $previous === $identity ? 'duplicate_binding_identity' : 'noncanonical_binding_order',
                    'bindings',
                    $count,
                    $position,
                );
            }
            $profile = $binding->profile()->value();
            if (isset($profiles[$profile])) {
                throw InvalidExternalServiceBindingSourceException::invalid('duplicate_profile_identity', 'bindings', $count, $position);
            }
            $profiles[$profile] = true;
            $previous = $identity;
        }
    }
}
