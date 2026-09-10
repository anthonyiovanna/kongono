<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceBindingBuild\Tests\Unit\Source;

use Kongono\ExternalServiceBindingBuild\Exception\InvalidExternalServiceBindingSourceException;
use Kongono\ExternalServiceBindingBuild\Source\ExternalServiceBindingSource;
use Kongono\ExternalServiceBindingBuild\Source\ExternalServiceBindingSourceIdentity;
use Kongono\ExternalServiceBindingBuild\Tests\Support\ExternalServiceBindingSourceFactory;
use PHPUnit\Framework\TestCase;

final class ExternalServiceBindingSourceTest extends TestCase
{
    public function testSourceIsDetachedOrderedAndDeeplyComparable(): void
    {
        $bindings = [
            ExternalServiceBindingSourceFactory::binding('alpha.binding', 'alpha.profile'),
            ExternalServiceBindingSourceFactory::binding('beta.binding', 'beta.profile', 'beta.example.test'),
        ];
        $source = new ExternalServiceBindingSource(new ExternalServiceBindingSourceIdentity('application.bindings'), $bindings);
        $bindings[] = ExternalServiceBindingSourceFactory::binding('gamma.binding', 'gamma.profile', 'gamma.example.test');
        $returned = $source->bindings();
        array_pop($returned);
        self::assertCount(2, $source->bindings());
        self::assertTrue($source->equals(ExternalServiceBindingSourceFactory::create(bindings: [
            ExternalServiceBindingSourceFactory::binding('alpha.binding', 'alpha.profile'),
            ExternalServiceBindingSourceFactory::binding('beta.binding', 'beta.profile', 'beta.example.test'),
        ])));
        self::assertFalse($source->equals(ExternalServiceBindingSourceFactory::create('other.bindings', $source->bindings())));
    }

    public function testNonListCountsWrongTypesOrderAndDuplicatesAreRejected(): void
    {
        $invalid = [
            [0 => ExternalServiceBindingSourceFactory::binding(), 2 => ExternalServiceBindingSourceFactory::binding('beta.binding', 'beta.profile')],
            [],
            array_fill(0, 65, ExternalServiceBindingSourceFactory::binding()),
            ['wrong'],
            [ExternalServiceBindingSourceFactory::binding('beta.binding', 'beta.profile'), ExternalServiceBindingSourceFactory::binding('alpha.binding', 'alpha.profile')],
            [ExternalServiceBindingSourceFactory::binding(), ExternalServiceBindingSourceFactory::binding()],
            [ExternalServiceBindingSourceFactory::binding(), ExternalServiceBindingSourceFactory::binding('beta.binding', 'alpha.profile')],
        ];
        $reflection = new \ReflectionClass(ExternalServiceBindingSource::class);
        foreach ($invalid as $bindings) {
            try {
                $reflection->newInstanceArgs([new ExternalServiceBindingSourceIdentity('application.bindings'), $bindings]);
                self::fail('Expected invalid source rejection.');
            } catch (InvalidExternalServiceBindingSourceException) {
                self::addToAssertionCount(1);
            }
        }
    }

    public function testEngineTypeErrorPropagates(): void
    {
        $reflection = new \ReflectionClass(ExternalServiceBindingSource::class);
        $this->expectException(\TypeError::class);
        $reflection->newInstanceArgs([[], []]);
    }
}
