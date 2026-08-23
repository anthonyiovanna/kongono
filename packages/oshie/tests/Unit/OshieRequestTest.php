<?php

declare(strict_types=1);

namespace Kongono\Oshie\Tests\Unit;

use Kongono\Oshie\Exception\InvalidOshieRequestException;
use Kongono\Oshie\OshieOperation;
use Kongono\Oshie\OshieRequest;
use PHPUnit\Framework\TestCase;

final class OshieRequestTest extends TestCase
{
    public function testClosedFactoriesRetainOnlyAdmittedValues(): void
    {
        self::assertSame(OshieOperation::Catalog, OshieRequest::catalog()->operation());
        self::assertSame('role-separation', OshieRequest::lesson('role-separation')->lessonId());
        self::assertSame('role-verifier', OshieRequest::exercise('role-verifier')->exerciseId());
        $check = OshieRequest::check('role-verifier', 'hantei');
        self::assertSame('hantei', $check->answerToken());
    }

    public function testMalformedAndUnknownOptionsFailWithoutDisclosure(): void
    {
        foreach ([static fn() => OshieRequest::lesson('Bad Value'), static fn() => OshieRequest::check('role-verifier', 'secret-value')] as $factory) {
            try {
                $factory();
                self::fail('Invalid request was admitted.');
            } catch (InvalidOshieRequestException $exception) {
                self::assertStringNotContainsString('Bad Value', (string) $exception);
                self::assertStringNotContainsString('secret-value', (string) $exception);
            }
        }
    }
}
