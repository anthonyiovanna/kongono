<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphContract\Tests\Unit\Identity;

use Kongono\ApplicationGraphContract\Exception\InvalidGraphContractException;
use Kongono\ApplicationGraphContract\Identity\NodeIdentity;
use Kongono\ApplicationGraphContract\Identity\ProducerIdentity;
use Kongono\ApplicationGraphContract\Identity\RelationIdentity;
use Kongono\ApplicationGraphContract\Identity\SourceIdentity;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use TypeError;

final class IdentityTest extends TestCase
{
    public function testEachIdentityPreservesTheDigestAndOwnsItsExactPrefix(): void
    {
        $digest = str_repeat('a', 64);

        foreach ([
            [new NodeIdentity($digest), 'kgn:graph:v1:node:'],
            [new RelationIdentity($digest), 'kgn:graph:v1:relation:'],
            [new ProducerIdentity($digest), 'kgn:graph:v1:producer:'],
            [new SourceIdentity($digest), 'kgn:graph:v1:source:'],
        ] as [$identity, $prefix]) {
            self::assertSame($digest, $identity->sha256());
            self::assertSame($prefix . $digest, $identity->value());
        }
    }

    public function testIdentityCategoriesCannotBeInterchangedByTypedApis(): void
    {
        $acceptNode = static fn(NodeIdentity $identity): string => $identity->value();

        $reflection = new ReflectionFunction($acceptNode);

        $this->expectException(TypeError::class);
        $reflection->invoke(new RelationIdentity(str_repeat('b', 64)));
    }

    public function testMalformedDigestsFailWithoutDisclosure(): void
    {
        foreach ([
            str_repeat('A', 64),
            str_repeat('a', 63) . 'G',
            str_repeat('a', 63),
            str_repeat('a', 65),
            ' ' . str_repeat('a', 64),
            str_repeat('é', 32),
            'sha256:' . str_repeat('a', 64),
        ] as $rejected) {
            try {
                new NodeIdentity($rejected);
                self::fail('Malformed digest was accepted.');
            } catch (InvalidGraphContractException $exception) {
                self::assertSame('KGN-GRAPH-1101', $exception->diagnosticId());
                self::assertStringNotContainsString($rejected, $exception->getMessage());
                self::assertStringNotContainsString($rejected, (string) $exception);
                foreach ($exception->diagnosticContext()->fields() as $field) {
                    self::assertNotSame($rejected, $field->value());
                }
            }
        }
    }
}
