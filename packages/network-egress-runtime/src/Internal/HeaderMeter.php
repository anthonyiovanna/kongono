<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressRuntime\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressContract\Budget\EgressBudget;
use Kongono\NetworkEgressRuntime\Exception\InvalidEgressRuntimeException;
use Psr\Http\Message\MessageInterface;

/** Measures header field-value lines without retaining names or values. */
#[Api(ApiStatus::Internal)]
final class HeaderMeter
{
    public static function enforce(MessageInterface $message, EgressBudget $budget): void
    {
        $count = 0;
        $bytes = 0;
        foreach ($message->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                ++$count;
                $bytes += strlen($name) + 2 + strlen($value) + 2;
                if ($count > $budget->maximumHeaderCount() || $bytes > $budget->maximumAggregateHeaderBytes()) {
                    throw InvalidEgressRuntimeException::invalid('header_budget');
                }
            }
        }
    }

    private function __construct() {}
}
