<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceCredentialRuntime\Tests\Fixture;

use Closure;
use Kongono\ExternalServiceContract\Credential\CredentialMaterialConsumer;
use Throwable;

final class RecordingCredentialMaterialConsumer implements CredentialMaterialConsumer
{
    /** @var list<string> */
    public array $received = [];

    /** @param null|Closure(string): void $observer */
    public function __construct(private ?Throwable $failure = null, private ?Closure $observer = null) {}

    public function consume(#[\SensitiveParameter] string $material): void
    {
        if (null !== $this->observer) {
            ($this->observer)('consumer.consume');
        }
        $this->received[] = $material;
        if (null !== $this->failure) {
            throw $this->failure;
        }
    }
}
