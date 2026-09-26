<?php

declare(strict_types=1);

namespace Kongono\StorageBuild\Profile;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageBuild\Exception\InvalidStorageProfileException;
use Kongono\StorageBuild\Internal\BuildGuard;
use Kongono\StorageBuild\Internal\CanonicalJson;

/** Holds one detached complete bounded ordinally canonical profile set. */
#[Api(ApiStatus::Experimental)]
final readonly class StorageProfileSet
{
    /** @var list<StorageProfileDeclaration> */
    private array $profiles;
    private StorageProfileSetIdentity $identity;

    /** @param array<array-key, mixed> $profiles */
    public function __construct(array $profiles)
    {
        if (!array_is_list($profiles)) {
            throw InvalidStorageProfileException::invalid('profiles_not_list', profileCount: count($profiles));
        }
        if (count($profiles) < 1 || count($profiles) > 256) {
            throw InvalidStorageProfileException::invalid('profile_count', profileCount: count($profiles), limit: 256);
        }
        $byName = [];
        $namespaces = [];
        foreach ($profiles as $position => $profile) {
            if (!$profile instanceof StorageProfileDeclaration) {
                throw InvalidStorageProfileException::invalid('profile_entry', profileCount: count($profiles), profilePosition: $position);
            }
            $name = $profile->name()->value();
            $namespace = $profile->namespace()->value();
            if (isset($byName[$name])) {
                throw InvalidStorageProfileException::invalid('duplicate_profile_name', profilePosition: $position);
            }
            if (isset($namespaces[$namespace])) {
                throw InvalidStorageProfileException::invalid('duplicate_namespace', profilePosition: $position);
            }
            $byName[$name] = $profile;
            $namespaces[$namespace] = true;
        }
        ksort($byName, SORT_STRING);
        $this->profiles = array_values($byName);
        $bytes = BuildGuard::SOURCE_PROFILE . "\0" . CanonicalJson::encode([
            'contract_version' => 1,
            'profiles' => BuildGuard::profileRows($this->profiles),
        ]);
        $this->identity = new StorageProfileSetIdentity(hash('sha256', $bytes));
    }

    /** @return list<StorageProfileDeclaration> */
    public function profiles(): array
    {
        return [...$this->profiles];
    }

    public function profileCount(): int
    {
        return count($this->profiles);
    }

    public function profile(StorageProfileName $name): ?StorageProfileDeclaration
    {
        foreach ($this->profiles as $profile) {
            if ($profile->name()->equals($name)) {
                return $profile;
            }
        }

        return null;
    }

    public function identity(): StorageProfileSetIdentity
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
