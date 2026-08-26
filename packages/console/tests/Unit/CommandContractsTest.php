<?php

declare(strict_types=1);

namespace Kongono\Console\Tests\Unit;

use Kongono\Console\CommandCancellation;
use Kongono\Console\CommandCleanup;
use Kongono\Console\CommandHandler;
use Kongono\Console\CommandLifecycleState;
use Kongono\Console\CommandOperation;
use Kongono\Console\CommandResultData;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

final class CommandContractsTest extends TestCase
{
    public function testLifecycleEnumsAndInterfaceSignaturesAreExact(): void
    {
        self::assertSame(['active', 'retired'], array_column(CommandLifecycleState::cases(), 'value'));
        self::assertSame(['isRequested'], $this->publicMethods(CommandCancellation::class));
        self::assertSame(['invocation', 'cancellation', 'state', 'defer', 'retire'], $this->publicMethods(CommandOperation::class));
        self::assertSame(['release'], $this->publicMethods(CommandCleanup::class));
        self::assertSame(['execute'], $this->publicMethods(CommandHandler::class));
        self::assertSame([], $this->publicMethods(CommandResultData::class));

        $execute = new ReflectionMethod(CommandHandler::class, 'execute');
        self::assertSame(CommandOperation::class, (string) $execute->getParameters()[0]->getType());
        self::assertSame('Kongono\\Console\\CommandResult', (string) $execute->getReturnType());
        self::assertSame('bool', (string) (new ReflectionMethod(CommandCancellation::class, 'isRequested'))->getReturnType());
        $defer = new ReflectionMethod(CommandOperation::class, 'defer');
        self::assertSame(CommandCleanup::class, (string) $defer->getParameters()[0]->getType());
        self::assertSame('void', (string) $defer->getReturnType());
        self::assertSame('void', (string) (new ReflectionMethod(CommandOperation::class, 'retire'))->getReturnType());
    }

    /**
     * @param class-string $class
     * @return list<string>
     */
    private function publicMethods(string $class): array
    {
        $methods = array_map(static fn(ReflectionMethod $method): string => $method->getName(), (new ReflectionClass($class))->getMethods(ReflectionMethod::IS_PUBLIC));
        return $methods;
    }
}
