<?php

declare(strict_types=1);

namespace Kongono\Web\Runtime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraph\Exception\ExceptionInterface as GraphExceptionInterface;
use Kongono\DependencyInjection\Exception\ExceptionInterface as DependencyInjectionExceptionInterface;
use Kongono\DiagnosticActivation\AdapterControlledDeliveryPermission;
use Kongono\DiagnosticActivation\TrustedDeveloperBootstrapPermission;
use Kongono\DiagnosticActivation\TrustedDeveloperPresentationEligibility;
use Kongono\DiagnosticHandler\TrustedDeveloperDiagnosticHtmlPresenter;
use Kongono\Kernel\Exception\ExceptionInterface as KernelExceptionInterface;
use Kongono\Web\Application\WebApplication;
use Kongono\Web\Runtime\Contract\WebDeliveryInterface;
use Kongono\Web\Runtime\Exception\DeliveryCleanupException;
use Kongono\Web\Runtime\Exception\RetiredWebRuntimeException;
use Kongono\Web\Runtime\Internal\FailureResponseFactory;
use Throwable;

/**
 * Coordinates one explicit outer web delivery without opening a Kernel scope.
 *
 * All delivery-specific values remain in the current call frame. Trusted output
 * is considered only for supported application failures after both independent
 * permissions are supplied; every other handled failure uses the fixed model.
 */
#[Api(ApiStatus::Experimental)]
final class WebRuntimeAdapter
{
    private readonly WebApplication $application;
    private readonly TrustedDeveloperBootstrapPermission $bootstrapPermission;
    private readonly TrustedDeveloperDiagnosticHtmlPresenter $presenter;
    private readonly FailureResponseFactory $failureResponses;
    private bool $retired = false;

    public function __construct(
        WebApplication $application,
        TrustedDeveloperBootstrapPermission $bootstrapPermission,
    ) {
        $this->application = $application;
        $this->bootstrapPermission = $bootstrapPermission;
        $this->presenter = new TrustedDeveloperDiagnosticHtmlPresenter();
        $this->failureResponses = new FailureResponseFactory();
    }

    private function __clone(): void {}

    public function handle(WebDeliveryInterface $delivery): void
    {
        if ($this->retired) {
            throw new RetiredWebRuntimeException();
        }

        if ($this->applicationIsRetired()) {
            $this->retired = true;

            throw new RetiredWebRuntimeException();
        }

        $primaryFailure = null;

        try {
            $response = null;

            if (TrustedDeveloperBootstrapPermission::Denied === $this->bootstrapPermission) {
                $deliveryPermission = AdapterControlledDeliveryPermission::Denied;
            } else {
                try {
                    $deliveryPermission = $delivery->evaluateTrustedPresentationPermission();
                } catch (Throwable) {
                    $response = $this->failureResponses->generic();
                    $deliveryPermission = AdapterControlledDeliveryPermission::Denied;
                }
            }

            if (null === $response) {
                try {
                    $request = $delivery->request();
                } catch (Throwable) {
                    $response = $this->failureResponses->generic();
                }
            }

            if (null === $response) {
                try {
                    $response = $this->application->handle($request);
                } catch (Throwable $applicationFailure) {
                    if ($this->applicationIsRetired()) {
                        $this->retired = true;
                    }

                    if (
                        $applicationFailure instanceof KernelExceptionInterface
                        || $applicationFailure instanceof GraphExceptionInterface
                        || $applicationFailure instanceof DependencyInjectionExceptionInterface
                    ) {
                        try {
                            $eligibility = new TrustedDeveloperPresentationEligibility(
                                $this->bootstrapPermission,
                                $deliveryPermission,
                            );
                            $document = $this->presenter->renderIfEligible($eligibility, $applicationFailure);
                            $response = null === $document
                                ? $this->failureResponses->generic()
                                : $this->failureResponses->trusted($document);
                        } catch (Throwable) {
                            $response = $this->failureResponses->generic();
                        }
                    } else {
                        $response = $this->failureResponses->generic();
                    }
                }
            }

            $delivery->send($response);
        } catch (Throwable $failure) {
            $this->retired = true;
            $primaryFailure = $failure;
        }

        try {
            $delivery->close();
        } catch (Throwable $cleanupFailure) {
            $this->retired = true;

            if (null !== $primaryFailure) {
                throw new DeliveryCleanupException($primaryFailure, $cleanupFailure);
            }

            throw $cleanupFailure;
        }

        if (null !== $primaryFailure) {
            throw $primaryFailure;
        }
    }

    public function isRetired(): bool
    {
        return $this->retired;
    }

    /**
     * The wrapped application can retire during a handle call.
     *
     * @phpstan-impure
     */
    private function applicationIsRetired(): bool
    {
        return $this->application->isRetired();
    }
}
