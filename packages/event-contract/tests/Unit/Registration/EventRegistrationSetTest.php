<?php

declare(strict_types=1);

namespace Kongono\EventContract\Tests\Unit\Registration;

use Kongono\EventContract\Exception\InvalidEventContractException;
use Kongono\EventContract\Registration\EventRegistration;
use Kongono\EventContract\Registration\EventRegistrationSet;
use Kongono\EventContract\Tests\Fixture\FirstEvent;
use Kongono\EventContract\Tests\Fixture\RecordingHandler;
use Kongono\EventContract\Tests\Fixture\SecondEvent;
use PHPUnit\Framework\TestCase;

final class EventRegistrationSetTest extends TestCase
{
    public function testEmptyListIsValidAndOrderAndArraySlotsAreDetached(): void
    {
        self::assertSame([], (new EventRegistrationSet([]))->registrations());
        self::assertTrue(class_exists(FirstEvent::class));
        $handler = new RecordingHandler();
        $first = new EventRegistration(FirstEvent::class, $handler);
        $second = new EventRegistration(FirstEvent::class, new RecordingHandler());
        $slot = $first;
        $input = [&$slot, $second];
        $set = new EventRegistrationSet($input);
        $slot = $second;
        $input[] = new EventRegistration(FirstEvent::class, new RecordingHandler());
        $returned = $set->registrations();
        $returned[0] = $second;
        self::assertSame(2, $set->count());
        self::assertSame($first, $set->registrations()[0]);
        self::assertSame($second, $set->registrations()[1]);
        self::assertSame(0, $handler->calls);
    }

    public function testSixtyFourDistinctHandlersAreAllowedAndSixtyFiveEntriesFailFirst(): void
    {
        self::assertTrue(class_exists(FirstEvent::class));
        $registrations = [];
        for ($i = 0; $i < 64; ++$i) {
            $registrations[] = new EventRegistration(FirstEvent::class, new RecordingHandler());
        }
        self::assertSame(64, (new EventRegistrationSet($registrations))->count());
        $registrations[] = $registrations[0];
        try {
            new EventRegistrationSet($registrations);
            self::fail('Expected the 65-entry bound to be checked before duplicates.');
        } catch (InvalidEventContractException $failure) {
            self::assertSame('KGN-EVENT-CONTRACT-2214', $failure->diagnosticId());
            self::assertSame('registration_count', $failure->diagnosticContext()->fields()[0]->value());
        }
    }

    public function testDuplicateMeansSameExactTypeAndSameHandlerObject(): void
    {
        self::assertTrue(class_exists(FirstEvent::class));
        self::assertTrue(class_exists(SecondEvent::class));
        $handler = new RecordingHandler();
        $first = new EventRegistration(FirstEvent::class, $handler);
        $otherType = new EventRegistration(SecondEvent::class, $handler);
        $otherObject = new EventRegistration(FirstEvent::class, new RecordingHandler());
        self::assertSame(3, (new EventRegistrationSet([$first, $otherType, $otherObject]))->count());
        try {
            new EventRegistrationSet([$first, $otherType, new EventRegistration(FirstEvent::class, $handler)]);
            self::fail('Expected duplicate exact binding.');
        } catch (InvalidEventContractException $failure) {
            self::assertSame('KGN-EVENT-CONTRACT-2213', $failure->diagnosticId());
            self::assertSame('duplicate_binding', $failure->diagnosticContext()->fields()[0]->value());
        }
        self::assertSame(0, $handler->calls);
    }

    public function testNonListAndInvalidElementAreRejected(): void
    {
        self::assertTrue(class_exists(FirstEvent::class));
        $registration = new EventRegistration(FirstEvent::class, new RecordingHandler());
        foreach ([['named' => $registration], [new \stdClass()]] as $input) {
            try {
                new EventRegistrationSet($input);
                self::fail('Expected invalid registration input.');
            } catch (InvalidEventContractException $failure) {
                self::assertSame('KGN-EVENT-CONTRACT-2212', $failure->diagnosticId());
            }
        }
    }
}
