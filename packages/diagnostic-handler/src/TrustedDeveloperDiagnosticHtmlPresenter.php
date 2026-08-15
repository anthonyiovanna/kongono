<?php

declare(strict_types=1);

namespace Kongono\DiagnosticHandler;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraph\Exception\ExceptionInterface as GraphExceptionInterface;
use Kongono\DependencyInjection\Exception\ExceptionInterface as DependencyInjectionExceptionInterface;
use Kongono\DiagnosticActivation\TrustedDeveloperPresentationEligibility;
use Kongono\DiagnosticPresentation\Developer\Html\TrustedDeveloperHtmlRenderer;
use Kongono\DiagnosticPresentation\Developer\TrustedDeveloperDiagnosticProjector;
use Kongono\Kernel\Exception\ExceptionInterface as KernelExceptionInterface;
use Throwable;

/**
 * Coordinates eligibility-gated trusted diagnostic HTML presentation.
 *
 * The caller remains responsible for both permission decisions and all transport
 * authority. A non-null result is presentation output, not proof of authorization.
 */
#[Api(ApiStatus::Experimental)]
final readonly class TrustedDeveloperDiagnosticHtmlPresenter
{
    /**
     * @throws Throwable An eligibility, source-accessor, projection, or rendering failure propagates unchanged.
     */
    public function renderIfEligible(
        TrustedDeveloperPresentationEligibility $eligibility,
        KernelExceptionInterface|GraphExceptionInterface|DependencyInjectionExceptionInterface $source,
    ): ?string {
        if (!$eligibility->allowsTrustedPresentation()) {
            return null;
        }

        $diagnostic = (new TrustedDeveloperDiagnosticProjector())->project($source);

        return (new TrustedDeveloperHtmlRenderer())->render($diagnostic);
    }
}
