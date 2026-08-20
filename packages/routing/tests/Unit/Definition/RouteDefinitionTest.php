<?php

declare(strict_types=1);

namespace Kongono\Routing\Tests\Unit\Definition;

use Kongono\Routing\Definition\RouteDefinition;
use Kongono\Routing\Diagnostic\RoutingDiagnostic;
use Kongono\Routing\Exception\InvalidRouteDefinitionException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RouteDefinitionTest extends TestCase
{
    public function testValidDefinitionPreservesExactValuesAndCanonicalizesMethods(): void
    {
        $methods = ['POST', 'GET'];
        $route = new RouteDefinition('users.show', $methods, '/users/{id}/a%2Fb');
        $methods[0] = 'DELETE';

        self::assertSame('users.show', $route->name());
        self::assertSame(['GET', 'POST'], $route->methods());
        self::assertSame('/users/{id}/a%2Fb', $route->pathTemplate());
        $returned = $route->methods();
        $returned[] = 'PUT';
        self::assertSame(['GET', 'POST'], $route->methods());
    }

    public function testEveryAcceptedUpperBoundaryAndLiteralByteFormIsPreserved(): void
    {
        $name = 'A' . str_repeat('n', 127);
        $methods = [];
        for ($position = 0; $position < 16; ++$position) {
            $methods[] = sprintf('M%02d', $position);
        }
        $segment = str_repeat('a', 127);
        $template = '/' . implode('/', array_fill(0, 16, $segment));
        self::assertSame(2048, strlen($template));
        $route = new RouteDefinition($name, $methods, $template);

        self::assertSame(128, strlen($route->name()));
        self::assertCount(16, $route->methods());
        self::assertSame($template, $route->pathTemplate());

        $allPchar = "AZaz09-._~!$&'()*+,;=:@%2F";
        $parameter = '{_' . str_repeat('p', 63) . '}';
        $complex = new RouteDefinition('a', ['!'], '/' . $allPchar . '/' . $parameter);
        self::assertSame('/' . $allPchar . '/' . $parameter, $complex->pathTemplate());
        self::assertSame(['!'], $complex->methods());
    }

    public function testAcceptedMethodAndTemplateShapeUpperBoundsArePreserved(): void
    {
        $method = str_repeat('M', 32);
        self::assertSame([$method], (new RouteDefinition('method-bound', [$method], '/'))->methods());

        $thirtyTwoSegments = '/' . implode('/', array_fill(0, 32, 'a'));
        self::assertSame($thirtyTwoSegments, (new RouteDefinition('segment-count-bound', ['GET'], $thirtyTwoSegments))->pathTemplate());

        $literalSegment = str_repeat('a', 128);
        self::assertSame('/' . $literalSegment, (new RouteDefinition('segment-length-bound', ['GET'], '/' . $literalSegment))->pathTemplate());
    }

    public function testReferencedMethodEntriesDetachBeforeStorage(): void
    {
        $referencedMethod = 'POST';
        $methods = [&$referencedMethod, 'GET'];
        $route = new RouteDefinition('route', $methods, '/');
        $referencedMethod = 'DELETE';
        $methods[1] = 'PUT';

        self::assertSame(['GET', 'POST'], $route->methods());
    }

    /** @return iterable<string, array{string, array<array-key, mixed>, string, RoutingDiagnostic, string}> */
    public static function invalidDefinitions(): iterable
    {
        yield 'empty name' => ['', ['GET'], '/', RoutingDiagnostic::InvalidRouteName, 'empty'];
        yield 'long name' => [str_repeat('a', 129), ['GET'], '/', RoutingDiagnostic::InvalidRouteName, 'too_long'];
        yield 'name syntax' => ['1route', ['GET'], '/', RoutingDiagnostic::InvalidRouteName, 'syntax'];
        yield 'methods not list' => ['route', [1 => 'GET'], '/', RoutingDiagnostic::InvalidRouteMethods, 'not_list'];
        yield 'methods empty' => ['route', [], '/', RoutingDiagnostic::InvalidRouteMethods, 'empty'];
        yield 'methods too many' => ['route', array_fill(0, 17, 'GET'), '/', RoutingDiagnostic::InvalidRouteMethods, 'too_many'];
        yield 'method not string' => ['route', [1], '/', RoutingDiagnostic::InvalidRouteMethod, 'not_string'];
        yield 'method empty' => ['route', [''], '/', RoutingDiagnostic::InvalidRouteMethod, 'empty'];
        yield 'method too long' => ['route', [str_repeat('A', 33)], '/', RoutingDiagnostic::InvalidRouteMethod, 'too_long'];
        yield 'method syntax' => ['route', ['BAD METHOD'], '/', RoutingDiagnostic::InvalidRouteMethod, 'syntax'];
        yield 'method noncanonical' => ['route', ['Get'], '/', RoutingDiagnostic::InvalidRouteMethod, 'not_canonical'];
        yield 'method duplicate' => ['route', ['GET', 'GET'], '/', RoutingDiagnostic::InvalidRouteMethod, 'duplicate'];
        yield 'template empty' => ['route', ['GET'], '', RoutingDiagnostic::InvalidRouteTemplate, 'empty'];
        yield 'template too long' => ['route', ['GET'], '/' . str_repeat('a', 2048), RoutingDiagnostic::InvalidRouteTemplate, 'too_long'];
        yield 'template leading slash' => ['route', ['GET'], 'users', RoutingDiagnostic::InvalidRouteTemplate, 'missing_leading_slash'];
        yield 'template trailing slash' => ['route', ['GET'], '/users/', RoutingDiagnostic::InvalidRouteTemplate, 'trailing_slash'];
        yield 'template empty segment' => ['route', ['GET'], '/users//new', RoutingDiagnostic::InvalidRouteTemplate, 'empty_segment'];
        yield 'template too many segments' => ['route', ['GET'], '/' . implode('/', array_fill(0, 33, 'a')), RoutingDiagnostic::InvalidRouteTemplate, 'too_many_segments'];
        yield 'template segment too long' => ['route', ['GET'], '/' . str_repeat('a', 129), RoutingDiagnostic::InvalidRouteTemplate, 'segment_too_long'];
        yield 'template invalid literal' => ['route', ['GET'], '/users?query', RoutingDiagnostic::InvalidRouteTemplate, 'invalid_literal'];
        yield 'template invalid parameter' => ['route', ['GET'], '/{bad-name}', RoutingDiagnostic::InvalidRouteTemplate, 'invalid_parameter'];
        yield 'template invalid percent' => ['route', ['GET'], '/%Q0', RoutingDiagnostic::InvalidRouteTemplate, 'invalid_percent_triplet'];
        yield 'duplicate parameter' => ['route', ['GET'], '/{id}/{id}', RoutingDiagnostic::DuplicateRouteParameter, ''];
    }

    /** @param array<array-key, mixed> $methods */
    #[DataProvider('invalidDefinitions')]
    public function testInvalidDefinitionUsesExactDiagnostic(string $name, array $methods, string $template, RoutingDiagnostic $diagnostic, string $violation): void
    {
        try {
            new RouteDefinition($name, $methods, $template);
            self::fail('Expected invalid route definition.');
        } catch (InvalidRouteDefinitionException $exception) {
            self::assertSame($diagnostic->value, $exception->diagnosticId());
            if ('' !== $violation) {
                $fields = $exception->diagnosticContext()->fields();
                self::assertNotEmpty($fields);
                self::assertSame($violation, $fields[count($fields) - 1]->value());
            }
        }
    }

    public function testFailurePriorityIsDeterministic(): void
    {
        try {
            new RouteDefinition('', [], 'invalid');
            self::fail('Expected invalid route definition.');
        } catch (InvalidRouteDefinitionException $exception) {
            self::assertSame(RoutingDiagnostic::InvalidRouteName->value, $exception->diagnosticId());
        }
    }
}
