<?php

declare(strict_types=1);

namespace Kongono\EventContract\Tests\Unit\Registration;

use Kongono\EventContract\ApplicationEvent;
use Kongono\EventContract\Exception\InvalidEventContractException;
use Kongono\EventContract\Registration\EventRegistration;
use Kongono\EventContract\Tests\Fixture\AbstractEvent;
use Kongono\EventContract\Tests\Fixture\DerivedEvent;
use Kongono\EventContract\Tests\Fixture\EventEnum;
use Kongono\EventContract\Tests\Fixture\EventTrait;
use Kongono\EventContract\Tests\Fixture\FirstEvent;
use Kongono\EventContract\Tests\Fixture\RecordingHandler;
use Kongono\EventContract\Tests\Fixture\SecondEvent;
use PHPUnit\Framework\TestCase;

final class EventRegistrationTest extends TestCase
{
    public function testLoadedExactClassAndInheritedMarkerAreAcceptedWithoutInvocation(): void
    {
        self::assertTrue(class_exists(FirstEvent::class));
        self::assertTrue(class_exists(DerivedEvent::class));
        self::assertTrue(class_exists(SecondEvent::class));
        $handler = new RecordingHandler();

        $first = new EventRegistration(FirstEvent::class, $handler);
        $derived = new EventRegistration(DerivedEvent::class, $handler);
        $privateConstructor = new EventRegistration(SecondEvent::class, $handler);

        self::assertSame(FirstEvent::class, $first->eventType());
        self::assertSame(DerivedEvent::class, $derived->eventType());
        self::assertSame(SecondEvent::class, $privateConstructor->eventType());
        self::assertSame($handler, $first->handler());
        self::assertSame(0, $handler->calls);
        self::assertSame(0, SecondEvent::$constructorCalls);
    }

    public function testOneAndTwoHundredFiftyFiveByteNamesAreAcceptedAndNextByteIsRejected(): void
    {
        require_once __DIR__ . '/../../Fixture/E.php';
        $handler = new RecordingHandler();
        self::assertSame(1, strlen(\E::class));
        self::assertSame(\E::class, (new EventRegistration(\E::class, $handler))->eventType());

        $longType = require __DIR__ . '/../../Fixture/LongNameEvent.php';
        self::assertIsString($longType);
        self::assertSame(255, strlen($longType));
        self::assertSame($longType, (new EventRegistration($longType, $handler))->eventType());
        try {
            new EventRegistration($longType . 'X', $handler);
            self::fail('Expected the event type byte bound to reject 256 bytes.');
        } catch (InvalidEventContractException $failure) {
            self::assertSame('KGN-EVENT-CONTRACT-2214', $failure->diagnosticId());
        }
    }

    public function testMalformedNamesAreRejectedWithoutDisclosingTheInput(): void
    {
        $handler = new RecordingHandler();
        foreach (['', '\\Leading', 'Trailing\\', 'A\\\\B', 'A/B', "A\nB", "A\x80B"] as $name) {
            try {
                new EventRegistration($name, $handler);
                self::fail('Expected malformed event type to be rejected.');
            } catch (InvalidEventContractException $failure) {
                self::assertSame('KGN-EVENT-CONTRACT-2211', $failure->diagnosticId());
                self::assertSame('event_type_grammar', $failure->diagnosticContext()->fields()[0]->value());
            }
        }
    }

    public function testUnknownNameDoesNotInvokeRegisteredAutoloaders(): void
    {
        $autoloadCalls = 0;
        $loader = static function (string $name) use (&$autoloadCalls): void {
            ++$autoloadCalls;
        };
        spl_autoload_register($loader);
        try {
            new EventRegistration('Unavailable\\MissingEventForPhase22B', new RecordingHandler());
            self::fail('Expected an unloaded event class to be rejected.');
        } catch (InvalidEventContractException $failure) {
            self::assertSame('event_type_not_loaded', $failure->diagnosticContext()->fields()[0]->value());
            self::assertSame(0, $autoloadCalls);
        } finally {
            spl_autoload_unregister($loader);
        }
    }

    public function testAliasesAndWrongCaseAreRejectedInsteadOfNormalized(): void
    {
        self::assertTrue(class_exists(FirstEvent::class));
        $alias = 'Kongono\\EventContract\\Tests\\Fixture\\FirstEventAliasPhase22B';
        self::assertTrue(class_alias(FirstEvent::class, $alias, false));
        $handler = new RecordingHandler();
        foreach ([$alias, strtolower(FirstEvent::class)] as $name) {
            try {
                new EventRegistration($name, $handler);
                self::fail('Expected noncanonical class spelling to be rejected.');
            } catch (InvalidEventContractException $failure) {
                self::assertSame('event_type_not_canonical', $failure->diagnosticContext()->fields()[0]->value());
            }
        }
    }

    public function testNonConcreteAndNonEventTypesAreRejected(): void
    {
        self::assertTrue(class_exists(AbstractEvent::class));
        self::assertTrue(enum_exists(EventEnum::class));
        self::assertTrue(trait_exists(EventTrait::class));
        $handler = new RecordingHandler();
        foreach ([AbstractEvent::class, EventEnum::class, EventTrait::class, ApplicationEvent::class, \stdClass::class] as $name) {
            $this->expectInvalid($name, $handler);
        }
        $anonymous = new class implements ApplicationEvent {};
        $this->expectInvalid($anonymous::class, $handler);
        self::assertSame(0, $handler->calls);
    }

    private function expectInvalid(string $name, RecordingHandler $handler): void
    {
        try {
            new EventRegistration($name, $handler);
            self::fail('Expected rejected event class.');
        } catch (InvalidEventContractException $failure) {
            self::assertSame('KGN-EVENT-CONTRACT-2211', $failure->diagnosticId());
        }
    }
}
