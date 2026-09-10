<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceContract\Tests\Unit\Credential;

use Kongono\ExternalServiceContract\Credential\CredentialReference;
use Kongono\ExternalServiceContract\Credential\CredentialSourceIdentity;
use Kongono\ExternalServiceContract\Exception\InvalidExternalServiceContractException;
use PHPUnit\Framework\TestCase;

final class CredentialReferenceTest extends TestCase
{
    public function testReferenceIsOpaqueBoundedAndComparable(): void
    {
        $source = new CredentialSourceIdentity('source.one');
        $reference = new CredentialReference($source, 'credential/reference.1');
        self::assertSame($source, $reference->source());
        self::assertSame('credential/reference.1', $reference->reference());
        self::assertTrue($reference->equals(new CredentialReference(new CredentialSourceIdentity('source.one'), 'credential/reference.1')));
        self::assertSame(str_repeat('a', 256), (new CredentialReference($source, str_repeat('a', 256)))->reference());
    }

    public function testAdjacentAndSecretShapedInputsFailWithoutDisclosure(): void
    {
        foreach (['', 'Upper', 'a:', 'a@b', 'a?b', 'a#b', 'a%2Fb', str_repeat('a', 257)] as $invalid) {
            try {
                new CredentialReference(new CredentialSourceIdentity('source.one'), $invalid);
                self::fail('Expected credential reference rejection.');
            } catch (InvalidExternalServiceContractException $exception) {
                if ('' !== $invalid) {
                    self::assertStringNotContainsString($invalid, (string) $exception);
                }
            }
        }
    }
}
