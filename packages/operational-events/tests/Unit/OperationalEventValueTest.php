<?php

declare(strict_types=1);

namespace Kongono\OperationalEvents\Tests\Unit;

use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Observability\AttributeKey;
use Kongono\Observability\AttributeSet;
use Kongono\Observability\AttributeValue;
use Kongono\Observability\AttributeValueKind;
use Kongono\Observability\ObservabilityContext;
use Kongono\Observability\ObservationStatus;
use Kongono\Observability\SignalAttribute;
use Kongono\Observability\SignalIdentity;
use Kongono\OperationalEvents\OperationalEvent;
use Kongono\OperationalEvents\OperationalEventDefinition;
use Kongono\OperationalEvents\OperationalEventField;
use Kongono\OperationalEvents\OperationalEventSchema;
use Kongono\OperationalEvents\OperationalEventSeverity;
use PHPUnit\Framework\TestCase;

final class OperationalEventValueTest extends TestCase
{
    public function testEventRetainsExactObjectsAndDerivesDisclosureFromSuppliedFields(): void
    {
        $safe = new OperationalEventField(new AttributeKey('app.mode'), AttributeValueKind::Text, DiagnosticDisclosure::Safe, true, [AttributeValue::text('ready')]);
        $protected = new OperationalEventField(new AttributeKey('app.zone'), AttributeValueKind::Text, DiagnosticDisclosure::Protected, false, [AttributeValue::text('private')]);
        $definition = new OperationalEventDefinition(new SignalIdentity('operations.ready', 1), OperationalEventSeverity::Info, new OperationalEventSchema([$protected, $safe]));
        $context = ObservabilityContext::uncorrelated();
        $safeAttributes = new AttributeSet([new SignalAttribute(new AttributeKey('app.mode'), AttributeValue::text('ready'))]);
        $safeEvent = new OperationalEvent($context, $definition, ObservationStatus::Ok, $safeAttributes);
        self::assertSame($context, $safeEvent->context());
        self::assertSame($definition, $safeEvent->definition());
        self::assertSame(ObservationStatus::Ok, $safeEvent->status());
        self::assertSame($safeAttributes, $safeEvent->attributes());
        self::assertSame(DiagnosticDisclosure::Safe, $safeEvent->disclosure());

        $protectedEvent = new OperationalEvent($context, $definition, ObservationStatus::Error, new AttributeSet([
            new SignalAttribute(new AttributeKey('app.mode'), AttributeValue::text('ready')),
            new SignalAttribute(new AttributeKey('app.zone'), AttributeValue::text('private')),
        ]));
        self::assertSame(DiagnosticDisclosure::Protected, $protectedEvent->disclosure());
    }
}
