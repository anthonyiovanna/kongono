<?php

declare(strict_types=1);

namespace Kongono\DiagnosticPresentation\Developer;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraph\Exception\ExceptionInterface as GraphExceptionInterface;
use Kongono\DependencyInjection\Exception\ExceptionInterface as DependencyInjectionExceptionInterface;
use Kongono\DiagnosticPresentation\Diagnostic\DiagnosticPresentationDiagnostic;
use Kongono\DiagnosticPresentation\Exception\InvalidDiagnosticProjectionException;
use Kongono\Kernel\Exception\ExceptionInterface as KernelExceptionInterface;
use Throwable;

/** Projects one contextual package failure into detached trusted-developer values. */
#[Api(ApiStatus::Experimental)]
final readonly class TrustedDeveloperDiagnosticProjector
{
    /**
     * @throws InvalidDiagnosticProjectionException When projected structure is invalid or source ownership is ambiguous.
     * @throws Throwable A source-accessor failure propagates unchanged.
     */
    public function project(
        KernelExceptionInterface|GraphExceptionInterface|DependencyInjectionExceptionInterface $source,
    ): TrustedDeveloperDiagnostic {
        $isKernel = $source instanceof KernelExceptionInterface;
        $isGraph = $source instanceof GraphExceptionInterface;
        $isDependencyInjection = $source instanceof DependencyInjectionExceptionInterface;

        if ((int) $isKernel + (int) $isGraph + (int) $isDependencyInjection > 1) {
            throw new InvalidDiagnosticProjectionException(
                DiagnosticPresentationDiagnostic::AmbiguousDiagnosticOwner,
                'Projected diagnostic source must implement exactly one supported package exception marker.',
            );
        }

        $owner = match (true) {
            $isKernel => DeveloperDiagnosticOwner::Kernel,
            $isGraph => DeveloperDiagnosticOwner::ApplicationGraph,
            $isDependencyInjection => DeveloperDiagnosticOwner::DependencyInjection,
        };

        $identifier = $source->diagnosticId();
        $title = $source->diagnosticTitle();
        $remediation = $source->diagnosticRemediation();
        $context = $source->diagnosticContext();
        $operation = $context->operation();
        $violatedInvariant = $context->violatedInvariant();
        $sourceFields = $context->fields();
        $fields = [];

        foreach ($sourceFields as $sourceField) {
            $key = $sourceField->key();
            $label = $sourceField->label();
            $kind = $sourceField->kind();
            $value = $sourceField->value();
            $disclosure = $sourceField->disclosure();
            $textWasTruncated = $sourceField->textWasTruncated();
            $fields[] = new TrustedDeveloperDiagnosticField(
                $key,
                $label,
                $kind,
                $value,
                $disclosure,
                $textWasTruncated,
            );
        }

        return new TrustedDeveloperDiagnostic(
            $owner,
            $identifier,
            $title,
            $operation,
            $violatedInvariant,
            $remediation,
            $fields,
        );
    }
}
