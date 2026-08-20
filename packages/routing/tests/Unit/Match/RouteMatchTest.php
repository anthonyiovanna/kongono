<?php

declare(strict_types=1);

namespace Kongono\Routing\Tests\Unit\Match;

use Kongono\Routing\Definition\RouteDefinition;
use Kongono\Routing\Diagnostic\RoutingDiagnostic;
use Kongono\Routing\Exception\InvalidRouteDefinitionException;
use Kongono\Routing\Match\RouteMatch;
use PHPUnit\Framework\TestCase;

final class RouteMatchTest extends TestCase
{
    public function testParametersAreValidatedDetachedAndOrderedByTemplate(): void
    {
        $route = new RouteDefinition('route', ['GET'], '/{first}/{second}');
        $parameters = ['second' => 'two%2Fvalue', 'first' => 'one'];
        $match = new RouteMatch($route, $parameters);
        $parameters['first'] = 'changed';

        self::assertSame($route, $match->route());
        self::assertSame(['first' => 'one', 'second' => 'two%2Fvalue'], $match->parameters());
        $returned = $match->parameters();
        $returned['first'] = 'changed';
        self::assertSame('one', $match->parameters()['first']);
    }

    public function testExactKeySetIsRequired(): void
    {
        $route = new RouteDefinition('route', ['GET'], '/{id}');
        foreach ([[], ['other' => 'value'], ['id' => 'value', 'extra' => 'value'], [0 => 'value']] as $parameters) {
            $this->assertInvalidMatch($route, $parameters, 'key_mismatch');
        }
    }

    public function testParameterValuesMustBeNonemptyBoundedEncodedSegments(): void
    {
        $route = new RouteDefinition('route', ['GET'], '/{id}');
        foreach ([['id' => 1], ['id' => ''], ['id' => str_repeat('a', 1025)], ['id' => 'bad/path'], ['id' => '%Q0'], ['id' => "\xFF"]] as $parameters) {
            $this->assertInvalidMatch($route, $parameters, 'invalid_value');
        }
    }

    public function testReferencedParameterValueDetachesBeforeStorage(): void
    {
        $value = 'encoded%2Fvalue';
        $parameters = ['id' => &$value];
        $match = new RouteMatch(new RouteDefinition('route', ['GET'], '/{id}'), $parameters);
        $value = 'changed';

        self::assertSame(['id' => 'encoded%2Fvalue'], $match->parameters());
    }

    public function testOneThousandTwentyFourByteParameterValueIsAcceptedDetachedAndPreserved(): void
    {
        $expected = str_repeat('a', 1024);
        $value = $expected;
        $parameters = ['id' => &$value];
        $match = new RouteMatch(new RouteDefinition('route', ['GET'], '/{id}'), $parameters);
        $value = 'changed';
        $parameters['id'] = 'replaced';

        self::assertSame(['id' => $expected], $match->parameters());
        self::assertSame(1024, strlen($match->parameters()['id']));
    }

    /** @param array<array-key, mixed> $parameters */
    private function assertInvalidMatch(RouteDefinition $route, array $parameters, string $violation): void
    {
        try {
            new RouteMatch($route, $parameters);
            self::fail('Expected invalid route match.');
        } catch (InvalidRouteDefinitionException $exception) {
            self::assertSame(RoutingDiagnostic::InvalidRouteMatch->value, $exception->diagnosticId());
            self::assertSame(count($parameters), $exception->diagnosticContext()->fields()[0]->value());
            self::assertSame($violation, $exception->diagnosticContext()->fields()[1]->value());
        }
    }
}
