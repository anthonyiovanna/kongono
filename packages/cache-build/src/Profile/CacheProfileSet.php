<?php

declare(strict_types=1);

namespace Kongono\CacheBuild\Profile;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheBuild\Exception\InvalidCacheProfileException;
use Kongono\CacheBuild\Internal\BuildGuard;
use Kongono\CacheBuild\Internal\CanonicalJson;

/** Holds one detached complete bounded ordinally canonical profile set. */
#[Api(ApiStatus::Experimental)]
final readonly class CacheProfileSet
{
    /** @var list<CacheProfileDeclaration> */
    private array $profiles;
    private CacheProfileSetIdentity $identity;

    /** @param array<array-key, mixed> $profiles */
    public function __construct(array $profiles)
    {
        if (!array_is_list($profiles)) {
            throw InvalidCacheProfileException::invalid('profiles_not_list', profileCount: count($profiles));
        }
        if (count($profiles) > 256) {
            throw InvalidCacheProfileException::invalid('profile_count', profileCount: count($profiles), limit: 256);
        }
        $byName = [];
        $namespaces = [];
        foreach ($profiles as $position => $profile) {
            if (!$profile instanceof CacheProfileDeclaration) {
                throw InvalidCacheProfileException::invalid('profile_entry', profileCount: count($profiles), profilePosition: $position);
            }
            $name = $profile->name()->value();
            $namespace = $profile->namespace()->value();
            if (isset($byName[$name])) {
                throw InvalidCacheProfileException::invalid('duplicate_profile_name', profilePosition: $position);
            }
            if (isset($namespaces[$namespace])) {
                throw InvalidCacheProfileException::invalid('duplicate_namespace', profilePosition: $position);
            }
            $byName[$name] = $profile;
            $namespaces[$namespace] = true;
        }
        ksort($byName, SORT_STRING);
        $this->profiles = array_values($byName);
        $bytes = CanonicalJson::encode([
            'contract_version' => 1,
            'profiles' => BuildGuard::profileRows($this->profiles),
        ]);
        $this->identity = new CacheProfileSetIdentity(hash('sha256', $bytes));
    }

    /** @return list<CacheProfileDeclaration> */
    public function profiles(): array
    {
        return [...$this->profiles];
    }

    public function profileCount(): int
    {
        return count($this->profiles);
    }

    public function profile(CacheProfileName $name): ?CacheProfileDeclaration
    {
        foreach ($this->profiles as $profile) {
            if ($profile->name()->equals($name)) {
                return $profile;
            }
        }

        return null;
    }

    public function identity(): CacheProfileSetIdentity
    {
        return $this->identity;
    }

    public function equals(self $other): bool
    {
        if (count($this->profiles) !== count($other->profiles)) {
            return false;
        }
        foreach ($this->profiles as $position => $profile) {
            if (!$profile->equals($other->profiles[$position])) {
                return false;
            }
        }

        return true;
    }
}
