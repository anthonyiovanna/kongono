<?php

declare(strict_types=1);

namespace Kongono\Tests\Architecture;

use Attribute;
use FilesystemIterator;
use InvalidArgumentException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraph\Diagnostic\GraphDiagnostic;
use Kongono\ApplicationGraph\Exception\ExceptionInterface as GraphExceptionInterface;
use Kongono\ApplicationGraph\Exception\GraphException;
use Kongono\ApplicationGraph\Exception\InvalidGraphException;
use Kongono\Chie\Chie;
use Kongono\Chie\ChieCitation;
use Kongono\Chie\ChieClaim;
use Kongono\Chie\ChieClaimClass;
use Kongono\Chie\ChieOperation;
use Kongono\Chie\ChieRequest;
use Kongono\Chie\ChieResponse;
use Kongono\Chie\ChieStatus;
use Kongono\Chie\ChieTopic;
use Kongono\Chie\Command\ChieCommand;
use Kongono\Chie\Diagnostic\ChieDiagnostic;
use Kongono\Chie\DiagnosticObservation;
use Kongono\Chie\Exception\ArtifactStaleException;
use Kongono\Chie\Exception\ArtifactUnavailableException;
use Kongono\Chie\Exception\ChieException;
use Kongono\Chie\Exception\ExceptionInterface as ChieExceptionInterface;
use Kongono\Chie\Exception\InvalidArtifactException as InvalidChieArtifactException;
use Kongono\Chie\Exception\InvalidChieProposalException;
use Kongono\Chie\Exception\InvalidChieRequestException;
use Kongono\Chie\Exception\InvalidChieValueException;
use Kongono\Chie\Exception\ProposalSpecUnavailableException;
use Kongono\Chie\Exception\ProposalTargetStaleException;
use Kongono\Chie\Internal\ArtifactFileReader;
use Kongono\Chie\Internal\CanonicalJson as ChieCanonicalJson;
use Kongono\Chie\Internal\DiagnosticLocator;
use Kongono\Chie\Internal\ProposalBuilder;
use Kongono\Chie\Internal\ProposalSpecReader;
use Kongono\Chie\Internal\SectionExtractor;
use Kongono\Chie\Internal\TopicCatalog;
use Kongono\Chie\Internal\UnifiedDiffBuilder;
use Kongono\Chie\Proposal\ChiePatchHandoff;
use Kongono\Chie\Proposal\ChiePatchProposal;
use Kongono\Chie\Proposal\ChiePatchProvenance;
use Kongono\Chie\Proposal\ChieProposalAuthority;
use Kongono\Chie\Proposal\ChieProposalRequest;
use Kongono\Chie\Proposal\ChieProposalStatus;
use Kongono\Chie\Proposal\SafeCandidateText;
use Kongono\Composition\Build\CompositionArtifact;
use Kongono\Composition\Build\CompositionArtifactBuilder;
use Kongono\Composition\Build\CompositionArtifactPublisher;
use Kongono\Composition\Build\CompositionArtifactValidator;
use Kongono\Composition\Diagnostic\CompositionDiagnostic;
use Kongono\Composition\Exception\ApplicationWorkflowException;
use Kongono\Composition\Exception\CompositionException;
use Kongono\Composition\Exception\CompositionLifecycleException;
use Kongono\Composition\Exception\CompositionPublicationException;
use Kongono\Composition\Exception\ExceptionInterface as CompositionExceptionInterface;
use Kongono\Composition\Exception\GeneratedWiringLoadException;
use Kongono\Composition\Exception\GeneratedWiringPublicationException;
use Kongono\Composition\Exception\InvalidCompositionArtifactException;
use Kongono\Composition\Exception\InvalidCompositionSourceException;
use Kongono\Composition\Exception\InvalidGeneratedWiringException;
use Kongono\Composition\Exception\RetiredCompositionBoundaryException;
use Kongono\Composition\Exception\StaleCompositionArtifactException;
use Kongono\Composition\Generated\GeneratedWiringArtifact;
use Kongono\Composition\Generated\GeneratedWiringGenerator;
use Kongono\Composition\Generated\GeneratedWiringPublisher;
use Kongono\Composition\Generated\GeneratedWiringValidator;
use Kongono\Composition\Internal\CanonicalJson as CompositionCanonicalJson;
use Kongono\Composition\Internal\CompositionModelCompiler;
use Kongono\Composition\Internal\GeneratedWiringEmitter;
use Kongono\Composition\Internal\GeneratedWiringImplementation;
use Kongono\Composition\Internal\PathGuard as CompositionPathGuard;
use Kongono\Composition\Internal\StrictJsonDecoder;
use Kongono\Composition\Runtime\GeneratedApplicationBoundary;
use Kongono\Composition\Runtime\GeneratedApplicationState;
use Kongono\Composition\Runtime\GeneratedOperationBoundary;
use Kongono\Composition\Runtime\GeneratedWiringLoader;
use Kongono\Composition\Runtime\LifecycleFailureComposer;
use Kongono\Composition\Runtime\LoadedGeneratedWiring;
use Kongono\Composition\Workflow\ApplicationBuildWorkflow;
use Kongono\Composition\Workflow\CurrentApplicationBuild;
use Kongono\DependencyInjection\Contract\ResolverInterface;
use Kongono\DependencyInjection\Definition\ServiceDefinition;
use Kongono\DependencyInjection\DependencyResolver;
use Kongono\DependencyInjection\Diagnostic\DependencyInjectionDiagnostic;
use Kongono\DependencyInjection\Exception\DependencyInjectionException;
use Kongono\DependencyInjection\Exception\ExceptionInterface as DependencyInjectionExceptionInterface;
use Kongono\DependencyInjection\Exception\InvalidDefinitionException;
use Kongono\DependencyInjection\Exception\UnknownServiceException;
use Kongono\DiagnosticActivation\AdapterControlledDeliveryPermission;
use Kongono\DiagnosticActivation\TrustedDeveloperBootstrapPermission;
use Kongono\DiagnosticActivation\TrustedDeveloperPresentationEligibility;
use Kongono\DiagnosticHandler\TrustedDeveloperDiagnosticHtmlPresenter;
use Kongono\DiagnosticPresentation\Developer\DeveloperDiagnosticOwner;
use Kongono\DiagnosticPresentation\Developer\Html\TrustedDeveloperHtmlRenderer;
use Kongono\DiagnosticPresentation\Developer\TrustedDeveloperDiagnostic;
use Kongono\DiagnosticPresentation\Developer\TrustedDeveloperDiagnosticField;
use Kongono\DiagnosticPresentation\Developer\TrustedDeveloperDiagnosticProjector;
use Kongono\DiagnosticPresentation\Diagnostic\DiagnosticPresentationDiagnostic;
use Kongono\DiagnosticPresentation\Exception\DiagnosticPresentationException;
use Kongono\DiagnosticPresentation\Exception\ExceptionInterface as DiagnosticPresentationExceptionInterface;
use Kongono\DiagnosticPresentation\Exception\InvalidDiagnosticProjectionException;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticField;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;
use Kongono\Diagnostics\Diagnostic\DiagnosticsDiagnostic;
use Kongono\Diagnostics\Exception\DiagnosticsException;
use Kongono\Diagnostics\Exception\ExceptionInterface as DiagnosticsExceptionInterface;
use Kongono\Diagnostics\Exception\InvalidDiagnosticValueException;
use Kongono\Hantei\Command\HanteiCommand;
use Kongono\Hantei\Diagnostic\HanteiDiagnostic;
use Kongono\Hantei\Exception\ExceptionInterface as HanteiExceptionInterface;
use Kongono\Hantei\Exception\HanteiException;
use Kongono\Hantei\Exception\InvalidHanteiRequestException;
use Kongono\Hantei\Exception\InvalidHanteiValueException;
use Kongono\Hantei\Hantei;
use Kongono\Hantei\HanteiCheckEvidence;
use Kongono\Hantei\HanteiCheckOutcome;
use Kongono\Hantei\HanteiReport;
use Kongono\Hantei\HanteiVerdict;
use Kongono\Hantei\Internal\CanonicalJson as HanteiCanonicalJson;
use Kongono\Hantei\Internal\FrameworkQualityProfile;
use Kongono\Hantei\Internal\FrameworkSubject;
use Kongono\Hantei\Internal\ProcessResult;
use Kongono\Hantei\Internal\ProcessRunner;
use Kongono\Hantei\Internal\VerdictEvaluator;
use Kongono\Http\Message\Diagnostic\HttpMessageDiagnostic;
use Kongono\Http\Message\Exception\ExceptionInterface as HttpMessageExceptionInterface;
use Kongono\Http\Message\Exception\HttpMessageRuntimeException;
use Kongono\Http\Message\Exception\InvalidMessageArgumentException;
use Kongono\Http\Server\Diagnostic\HttpServerDiagnostic;
use Kongono\Http\Server\Exception\ExceptionInterface as HttpServerExceptionInterface;
use Kongono\Http\Server\Exception\InvalidHttpServerDefinitionException;
use Kongono\Http\Server\Internal\MiddlewareLayer;
use Kongono\Http\Server\MiddlewarePipeline;
use Kongono\Http\Server\RoutingRequestHandler;
use Kongono\Kernel\Contract\KernelInterface;
use Kongono\Kernel\Diagnostic\LifecycleDiagnostic;
use Kongono\Kernel\Exception\ExceptionInterface as KernelExceptionInterface;
use Kongono\Kernel\Exception\KernelException;
use Kongono\Kernel\Exception\LifecycleException;
use Kongono\Knowledge\Artifact\FrameworkKnowledgeArtifact;
use Kongono\Knowledge\Artifact\FrameworkKnowledgeArtifactGenerator;
use Kongono\Knowledge\Artifact\FrameworkKnowledgeArtifactPublisher;
use Kongono\Knowledge\Artifact\FrameworkKnowledgeArtifactValidator;
use Kongono\Knowledge\Command\KnowledgeCommand;
use Kongono\Knowledge\Diagnostic\KnowledgeDiagnostic;
use Kongono\Knowledge\Exception\ExceptionInterface as KnowledgeExceptionInterface;
use Kongono\Knowledge\Exception\InvalidKnowledgeArtifactException;
use Kongono\Knowledge\Exception\InvalidKnowledgeSourceException;
use Kongono\Knowledge\Exception\KnowledgeException;
use Kongono\Knowledge\Exception\KnowledgePublicationException;
use Kongono\Knowledge\Exception\StaleKnowledgeArtifactException;
use Kongono\Knowledge\Internal\CanonicalJson;
use Kongono\Knowledge\Internal\PathGuard;
use Kongono\Knowledge\Source\KnowledgeSource;
use Kongono\Knowledge\Source\KnowledgeSourceAuthority;
use Kongono\Knowledge\Source\KnowledgeSourceDefinition;
use Kongono\Knowledge\Source\KnowledgeSourceInventory;
use Kongono\Oshie\Command\OshieCommand;
use Kongono\Oshie\Diagnostic\OshieDiagnostic;
use Kongono\Oshie\Exception\ArtifactStaleException as OshieArtifactStaleException;
use Kongono\Oshie\Exception\ArtifactUnavailableException as OshieArtifactUnavailableException;
use Kongono\Oshie\Exception\ExceptionInterface as OshieExceptionInterface;
use Kongono\Oshie\Exception\InvalidArtifactException as InvalidOshieArtifactException;
use Kongono\Oshie\Exception\InvalidOshieRequestException;
use Kongono\Oshie\Exception\InvalidOshieValueException;
use Kongono\Oshie\Exception\OshieException;
use Kongono\Oshie\Internal\ArtifactFileReader as OshieArtifactFileReader;
use Kongono\Oshie\Internal\CanonicalJson as OshieCanonicalJson;
use Kongono\Oshie\Internal\CurriculumCatalog;
use Kongono\Oshie\Internal\SectionExtractor as OshieSectionExtractor;
use Kongono\Oshie\Oshie;
use Kongono\Oshie\OshieAnswerOutcome;
use Kongono\Oshie\OshieAttempt;
use Kongono\Oshie\OshieCitation;
use Kongono\Oshie\OshieExercise;
use Kongono\Oshie\OshieLesson;
use Kongono\Oshie\OshieLessonSummary;
use Kongono\Oshie\OshieOperation;
use Kongono\Oshie\OshieRequest;
use Kongono\Oshie\OshieResponse;
use Kongono\Oshie\OshieStatus;
use Kongono\PhpCgi\Bootstrap\CgiOperatorBootstrap;
use Kongono\PhpCgi\Bootstrap\CgiOperatorProfile;
use Kongono\PhpCgi\Delivery\CgiWebDelivery;
use Kongono\PhpCgi\Diagnostic\PhpCgiDiagnostic;
use Kongono\PhpCgi\Emission\CgiResponseEmitter;
use Kongono\PhpCgi\Entry\CgiFrontController;
use Kongono\PhpCgi\Entry\CgiGeneratedFrontController;
use Kongono\PhpCgi\Entry\CgiWebEntry;
use Kongono\PhpCgi\Exception\CgiDeliveryCleanupException;
use Kongono\PhpCgi\Exception\CgiEntryShutdownException;
use Kongono\PhpCgi\Exception\CgiOperatorBootstrapException;
use Kongono\PhpCgi\Exception\CgiRequestReadException;
use Kongono\PhpCgi\Exception\CgiResponseEmissionException;
use Kongono\PhpCgi\Exception\ExceptionInterface as PhpCgiExceptionInterface;
use Kongono\PhpCgi\Exception\InvalidCgiRequestException;
use Kongono\PhpCgi\Exception\InvalidCgiResponseException;
use Kongono\PhpCgi\Inbound\CgiServerRequestConverter;
use Kongono\PhpCgi\Internal\CgiCookieParser;
use Kongono\PhpCgi\Internal\CgiEmissionTarget;
use Kongono\PhpCgi\Internal\CgiHeaderProjector;
use Kongono\PhpCgi\Internal\CgiNativeEmissionTarget;
use Kongono\PhpCgi\Internal\CgiOperatorBootstrapValidation;
use Kongono\PhpCgi\Internal\CgiQueryParser;
use Kongono\PhpCgi\Internal\CgiRequestValidation;
use Kongono\PhpCgi\Internal\CgiResponseEmission;
use Kongono\PhpCgi\Internal\CgiStagedResponse;
use Kongono\PhpCgi\Internal\CgiTrustedPresentationPermissionEvaluator;
use Kongono\Routing\Definition\RouteDefinition;
use Kongono\Routing\Definition\RouteTable;
use Kongono\Routing\Diagnostic\RoutingDiagnostic;
use Kongono\Routing\Exception\ExceptionInterface as RoutingExceptionInterface;
use Kongono\Routing\Exception\InvalidRouteDefinitionException;
use Kongono\Routing\Exception\InvalidRouteRequestException;
use Kongono\Routing\Internal\RouteSyntax;
use Kongono\Routing\Match\RouteMatch;
use Kongono\Routing\Matcher\RouteMatcher;
use Kongono\Routing\Matcher\RouteMatcherInterface;
use Kongono\Web\Application\Diagnostic\WebApplicationDiagnostic;
use Kongono\Web\Application\Exception\ExceptionInterface as WebApplicationExceptionInterface;
use Kongono\Web\Application\Exception\OperationCleanupException;
use Kongono\Web\Application\Exception\RetiredWebApplicationException;
use Kongono\Web\Application\Exception\WebApplicationException;
use Kongono\Web\Application\WebApplication;
use Kongono\Web\Runtime\Contract\WebDeliveryInterface;
use Kongono\Web\Runtime\Diagnostic\WebRuntimeDiagnostic;
use Kongono\Web\Runtime\Exception\DeliveryCleanupException;
use Kongono\Web\Runtime\Exception\ExceptionInterface as WebRuntimeExceptionInterface;
use Kongono\Web\Runtime\Exception\InvalidGeneratedWebRuntimeException;
use Kongono\Web\Runtime\Exception\RetiredWebRuntimeException;
use Kongono\Web\Runtime\Exception\WebRuntimeException;
use Kongono\Web\Runtime\GeneratedWebRuntimeApplication;
use Kongono\Web\Runtime\GeneratedWebRuntimeBootstrap;
use Kongono\Web\Runtime\GeneratedWebRuntimeRoot;
use Kongono\Web\Runtime\Internal\FailureResponseFactory;
use Kongono\Web\Runtime\WebRuntimeAdapter;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use SplFileInfo;
use Throwable;

final class ApiStatusConventionTest extends TestCase
{
    /** @var array<string, ApiStatus> */
    private const EXPECTED_STATUS = [
        GraphDiagnostic::class => ApiStatus::Internal,
        'Kongono\\ApplicationGraph\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\ApplicationGraph\\Exception\\GraphException' => ApiStatus::Internal,
        'Kongono\\ApplicationGraph\\Exception\\InvalidGraphException' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraph\\Model\\ApplicationGraph' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraph\\Model\\Node' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraph\\Model\\NodeKey' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraph\\Model\\NodeKind' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraph\\Model\\Relation' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraph\\Model\\RelationKind' => ApiStatus::Experimental,
        Chie::class => ApiStatus::Experimental,
        ChieCitation::class => ApiStatus::Experimental,
        ChieClaim::class => ApiStatus::Experimental,
        ChieClaimClass::class => ApiStatus::Experimental,
        ChieOperation::class => ApiStatus::Experimental,
        ChieRequest::class => ApiStatus::Experimental,
        ChieResponse::class => ApiStatus::Experimental,
        ChieStatus::class => ApiStatus::Experimental,
        ChieTopic::class => ApiStatus::Experimental,
        DiagnosticObservation::class => ApiStatus::Experimental,
        ChieCommand::class => ApiStatus::Internal,
        ChieDiagnostic::class => ApiStatus::Internal,
        ChieExceptionInterface::class => ApiStatus::Public,
        ArtifactStaleException::class => ApiStatus::Experimental,
        ArtifactUnavailableException::class => ApiStatus::Experimental,
        ChieException::class => ApiStatus::Internal,
        InvalidChieArtifactException::class => ApiStatus::Experimental,
        InvalidChieRequestException::class => ApiStatus::Experimental,
        InvalidChieValueException::class => ApiStatus::Internal,
        ArtifactFileReader::class => ApiStatus::Internal,
        ChieCanonicalJson::class => ApiStatus::Internal,
        DiagnosticLocator::class => ApiStatus::Internal,
        SectionExtractor::class => ApiStatus::Internal,
        TopicCatalog::class => ApiStatus::Internal,
        ChieProposalAuthority::class => ApiStatus::Experimental,
        ChieProposalRequest::class => ApiStatus::Experimental,
        ChiePatchProposal::class => ApiStatus::Experimental,
        ChiePatchProvenance::class => ApiStatus::Experimental,
        ChiePatchHandoff::class => ApiStatus::Experimental,
        ChieProposalStatus::class => ApiStatus::Experimental,
        SafeCandidateText::class => ApiStatus::Experimental,
        InvalidChieProposalException::class => ApiStatus::Experimental,
        ProposalSpecUnavailableException::class => ApiStatus::Experimental,
        ProposalTargetStaleException::class => ApiStatus::Experimental,
        ProposalSpecReader::class => ApiStatus::Internal,
        UnifiedDiffBuilder::class => ApiStatus::Internal,
        ProposalBuilder::class => ApiStatus::Internal,
        CompositionArtifact::class => ApiStatus::Experimental,
        CompositionArtifactBuilder::class => ApiStatus::Experimental,
        CompositionArtifactPublisher::class => ApiStatus::Experimental,
        CompositionArtifactValidator::class => ApiStatus::Experimental,
        ApplicationBuildWorkflow::class => ApiStatus::Experimental,
        CurrentApplicationBuild::class => ApiStatus::Experimental,
        ApplicationWorkflowException::class => ApiStatus::Experimental,
        CompositionDiagnostic::class => ApiStatus::Internal,
        CompositionExceptionInterface::class => ApiStatus::Public,
        CompositionException::class => ApiStatus::Internal,
        CompositionLifecycleException::class => ApiStatus::Public,
        InvalidCompositionSourceException::class => ApiStatus::Public,
        InvalidCompositionArtifactException::class => ApiStatus::Public,
        StaleCompositionArtifactException::class => ApiStatus::Public,
        CompositionPublicationException::class => ApiStatus::Public,
        InvalidGeneratedWiringException::class => ApiStatus::Public,
        GeneratedWiringPublicationException::class => ApiStatus::Public,
        GeneratedWiringLoadException::class => ApiStatus::Experimental,
        RetiredCompositionBoundaryException::class => ApiStatus::Public,
        GeneratedWiringArtifact::class => ApiStatus::Experimental,
        GeneratedWiringGenerator::class => ApiStatus::Experimental,
        GeneratedWiringPublisher::class => ApiStatus::Experimental,
        GeneratedWiringValidator::class => ApiStatus::Experimental,
        CompositionCanonicalJson::class => ApiStatus::Internal,
        CompositionModelCompiler::class => ApiStatus::Internal,
        GeneratedWiringEmitter::class => ApiStatus::Internal,
        GeneratedWiringImplementation::class => ApiStatus::Internal,
        CompositionPathGuard::class => ApiStatus::Internal,
        StrictJsonDecoder::class => ApiStatus::Internal,
        GeneratedApplicationBoundary::class => ApiStatus::Experimental,
        GeneratedWiringLoader::class => ApiStatus::Experimental,
        LoadedGeneratedWiring::class => ApiStatus::Experimental,
        GeneratedApplicationState::class => ApiStatus::Internal,
        GeneratedOperationBoundary::class => ApiStatus::Experimental,
        LifecycleFailureComposer::class => ApiStatus::Internal,
        ResolverInterface::class => ApiStatus::Experimental,
        ServiceDefinition::class => ApiStatus::Experimental,
        DependencyResolver::class => ApiStatus::Experimental,
        DependencyInjectionDiagnostic::class => ApiStatus::Internal,
        DependencyInjectionExceptionInterface::class => ApiStatus::Public,
        DependencyInjectionException::class => ApiStatus::Internal,
        InvalidDefinitionException::class => ApiStatus::Experimental,
        UnknownServiceException::class => ApiStatus::Experimental,
        AdapterControlledDeliveryPermission::class => ApiStatus::Experimental,
        TrustedDeveloperBootstrapPermission::class => ApiStatus::Experimental,
        TrustedDeveloperPresentationEligibility::class => ApiStatus::Experimental,
        TrustedDeveloperDiagnosticHtmlPresenter::class => ApiStatus::Experimental,
        DeveloperDiagnosticOwner::class => ApiStatus::Experimental,
        TrustedDeveloperDiagnostic::class => ApiStatus::Experimental,
        TrustedDeveloperDiagnosticField::class => ApiStatus::Experimental,
        TrustedDeveloperDiagnosticProjector::class => ApiStatus::Experimental,
        TrustedDeveloperHtmlRenderer::class => ApiStatus::Experimental,
        DiagnosticPresentationDiagnostic::class => ApiStatus::Internal,
        DiagnosticPresentationException::class => ApiStatus::Internal,
        DiagnosticPresentationExceptionInterface::class => ApiStatus::Public,
        InvalidDiagnosticProjectionException::class => ApiStatus::Experimental,
        DiagnosticContext::class => ApiStatus::Public,
        DiagnosticContextSchema::class => ApiStatus::Public,
        DiagnosticDisclosure::class => ApiStatus::Public,
        DiagnosticField::class => ApiStatus::Public,
        DiagnosticFieldSchema::class => ApiStatus::Public,
        DiagnosticValueKind::class => ApiStatus::Public,
        DiagnosticsDiagnostic::class => ApiStatus::Internal,
        DiagnosticsException::class => ApiStatus::Internal,
        DiagnosticsExceptionInterface::class => ApiStatus::Public,
        InvalidDiagnosticValueException::class => ApiStatus::Experimental,
        Hantei::class => ApiStatus::Experimental,
        HanteiCheckEvidence::class => ApiStatus::Experimental,
        HanteiCheckOutcome::class => ApiStatus::Experimental,
        HanteiReport::class => ApiStatus::Experimental,
        HanteiVerdict::class => ApiStatus::Experimental,
        InvalidHanteiRequestException::class => ApiStatus::Experimental,
        HanteiExceptionInterface::class => ApiStatus::Public,
        HanteiCommand::class => ApiStatus::Internal,
        HanteiDiagnostic::class => ApiStatus::Internal,
        HanteiException::class => ApiStatus::Internal,
        InvalidHanteiValueException::class => ApiStatus::Internal,
        HanteiCanonicalJson::class => ApiStatus::Internal,
        FrameworkQualityProfile::class => ApiStatus::Internal,
        FrameworkSubject::class => ApiStatus::Internal,
        ProcessResult::class => ApiStatus::Internal,
        ProcessRunner::class => ApiStatus::Internal,
        VerdictEvaluator::class => ApiStatus::Internal,
        Api::class => ApiStatus::Public,
        ApiStatus::class => ApiStatus::Public,
        LifecycleDiagnostic::class => ApiStatus::Internal,
        'Kongono\\Kernel\\Contract\\KernelInterface' => ApiStatus::Experimental,
        'Kongono\\Kernel\\Contract\\KernelLifecycleInterface' => ApiStatus::Experimental,
        'Kongono\\Kernel\\Contract\\OperationLifecycleInterface' => ApiStatus::Experimental,
        'Kongono\\Kernel\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\Kernel\\Exception\\KernelException' => ApiStatus::Internal,
        'Kongono\\Kernel\\Exception\\LifecycleException' => ApiStatus::Experimental,
        'Kongono\\Kernel\\Kernel' => ApiStatus::Experimental,
        'Kongono\\Kernel\\Lifecycle\\KernelState' => ApiStatus::Experimental,
        'Kongono\\Kernel\\Lifecycle\\OperationScope' => ApiStatus::Experimental,
        FrameworkKnowledgeArtifact::class => ApiStatus::Experimental,
        FrameworkKnowledgeArtifactGenerator::class => ApiStatus::Experimental,
        FrameworkKnowledgeArtifactPublisher::class => ApiStatus::Experimental,
        FrameworkKnowledgeArtifactValidator::class => ApiStatus::Experimental,
        KnowledgeCommand::class => ApiStatus::Internal,
        KnowledgeDiagnostic::class => ApiStatus::Internal,
        KnowledgeExceptionInterface::class => ApiStatus::Public,
        InvalidKnowledgeArtifactException::class => ApiStatus::Experimental,
        InvalidKnowledgeSourceException::class => ApiStatus::Experimental,
        KnowledgeException::class => ApiStatus::Internal,
        KnowledgePublicationException::class => ApiStatus::Experimental,
        StaleKnowledgeArtifactException::class => ApiStatus::Experimental,
        CanonicalJson::class => ApiStatus::Internal,
        PathGuard::class => ApiStatus::Internal,
        KnowledgeSource::class => ApiStatus::Experimental,
        KnowledgeSourceAuthority::class => ApiStatus::Experimental,
        KnowledgeSourceDefinition::class => ApiStatus::Internal,
        KnowledgeSourceInventory::class => ApiStatus::Internal,
        Oshie::class => ApiStatus::Experimental,
        OshieCitation::class => ApiStatus::Experimental,
        OshieLessonSummary::class => ApiStatus::Experimental,
        OshieLesson::class => ApiStatus::Experimental,
        OshieExercise::class => ApiStatus::Experimental,
        OshieAttempt::class => ApiStatus::Experimental,
        OshieOperation::class => ApiStatus::Experimental,
        OshieStatus::class => ApiStatus::Experimental,
        OshieAnswerOutcome::class => ApiStatus::Experimental,
        OshieRequest::class => ApiStatus::Experimental,
        OshieResponse::class => ApiStatus::Experimental,
        OshieArtifactStaleException::class => ApiStatus::Experimental,
        OshieArtifactUnavailableException::class => ApiStatus::Experimental,
        InvalidOshieArtifactException::class => ApiStatus::Experimental,
        InvalidOshieRequestException::class => ApiStatus::Experimental,
        OshieExceptionInterface::class => ApiStatus::Public,
        OshieCommand::class => ApiStatus::Internal,
        OshieDiagnostic::class => ApiStatus::Internal,
        OshieException::class => ApiStatus::Internal,
        InvalidOshieValueException::class => ApiStatus::Internal,
        OshieArtifactFileReader::class => ApiStatus::Internal,
        OshieCanonicalJson::class => ApiStatus::Internal,
        CurriculumCatalog::class => ApiStatus::Internal,
        OshieSectionExtractor::class => ApiStatus::Internal,
        CgiOperatorBootstrap::class => ApiStatus::Experimental,
        CgiOperatorProfile::class => ApiStatus::Experimental,
        CgiWebDelivery::class => ApiStatus::Internal,
        PhpCgiDiagnostic::class => ApiStatus::Internal,
        PhpCgiExceptionInterface::class => ApiStatus::Experimental,
        CgiOperatorBootstrapException::class => ApiStatus::Experimental,
        CgiRequestReadException::class => ApiStatus::Experimental,
        InvalidCgiRequestException::class => ApiStatus::Experimental,
        CgiResponseEmissionException::class => ApiStatus::Experimental,
        InvalidCgiResponseException::class => ApiStatus::Experimental,
        CgiServerRequestConverter::class => ApiStatus::Experimental,
        CgiResponseEmitter::class => ApiStatus::Experimental,
        CgiWebEntry::class => ApiStatus::Experimental,
        CgiFrontController::class => ApiStatus::Experimental,
        CgiGeneratedFrontController::class => ApiStatus::Experimental,
        CgiDeliveryCleanupException::class => ApiStatus::Experimental,
        CgiEntryShutdownException::class => ApiStatus::Experimental,
        CgiCookieParser::class => ApiStatus::Internal,
        CgiEmissionTarget::class => ApiStatus::Internal,
        CgiHeaderProjector::class => ApiStatus::Internal,
        CgiNativeEmissionTarget::class => ApiStatus::Internal,
        CgiOperatorBootstrapValidation::class => ApiStatus::Internal,
        CgiQueryParser::class => ApiStatus::Internal,
        CgiRequestValidation::class => ApiStatus::Internal,
        CgiResponseEmission::class => ApiStatus::Internal,
        CgiStagedResponse::class => ApiStatus::Internal,
        CgiTrustedPresentationPermissionEvaluator::class => ApiStatus::Internal,
        HttpMessageDiagnostic::class => ApiStatus::Internal,
        'Kongono\\Http\\Message\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\Http\\Message\\Exception\\HttpMessageRuntimeException' => ApiStatus::Experimental,
        'Kongono\\Http\\Message\\Exception\\InvalidMessageArgumentException' => ApiStatus::Experimental,
        'Kongono\\Http\\Message\\Factory\\ResponseFactory' => ApiStatus::Experimental,
        'Kongono\\Http\\Message\\Factory\\ServerRequestFactory' => ApiStatus::Experimental,
        'Kongono\\Http\\Message\\Factory\\StreamFactory' => ApiStatus::Experimental,
        'Kongono\\Http\\Message\\Factory\\UploadedFileFactory' => ApiStatus::Experimental,
        'Kongono\\Http\\Message\\Factory\\UriFactory' => ApiStatus::Experimental,
        'Kongono\\Http\\Message\\Internal\\HeaderCollection' => ApiStatus::Internal,
        'Kongono\\Http\\Message\\Internal\\HttpMessageValidation' => ApiStatus::Internal,
        'Kongono\\Http\\Message\\Message\\AbstractMessage' => ApiStatus::Internal,
        'Kongono\\Http\\Message\\Message\\Response' => ApiStatus::Experimental,
        'Kongono\\Http\\Message\\Message\\ServerRequest' => ApiStatus::Experimental,
        'Kongono\\Http\\Message\\Message\\Stream' => ApiStatus::Experimental,
        'Kongono\\Http\\Message\\Message\\UploadedFile' => ApiStatus::Experimental,
        'Kongono\\Http\\Message\\Message\\Uri' => ApiStatus::Experimental,
        HttpServerDiagnostic::class => ApiStatus::Internal,
        HttpServerExceptionInterface::class => ApiStatus::Public,
        InvalidHttpServerDefinitionException::class => ApiStatus::Experimental,
        MiddlewareLayer::class => ApiStatus::Internal,
        MiddlewarePipeline::class => ApiStatus::Experimental,
        RoutingRequestHandler::class => ApiStatus::Experimental,
        RoutingDiagnostic::class => ApiStatus::Internal,
        RoutingExceptionInterface::class => ApiStatus::Public,
        InvalidRouteDefinitionException::class => ApiStatus::Experimental,
        InvalidRouteRequestException::class => ApiStatus::Experimental,
        RouteDefinition::class => ApiStatus::Experimental,
        RouteTable::class => ApiStatus::Experimental,
        RouteMatch::class => ApiStatus::Experimental,
        RouteMatcherInterface::class => ApiStatus::Experimental,
        RouteMatcher::class => ApiStatus::Experimental,
        RouteSyntax::class => ApiStatus::Internal,
        WebApplicationDiagnostic::class => ApiStatus::Internal,
        WebApplicationExceptionInterface::class => ApiStatus::Public,
        WebApplicationException::class => ApiStatus::Internal,
        OperationCleanupException::class => ApiStatus::Experimental,
        RetiredWebApplicationException::class => ApiStatus::Experimental,
        WebApplication::class => ApiStatus::Experimental,
        WebDeliveryInterface::class => ApiStatus::Public,
        WebRuntimeDiagnostic::class => ApiStatus::Internal,
        WebRuntimeExceptionInterface::class => ApiStatus::Public,
        WebRuntimeException::class => ApiStatus::Internal,
        RetiredWebRuntimeException::class => ApiStatus::Experimental,
        InvalidGeneratedWebRuntimeException::class => ApiStatus::Experimental,
        DeliveryCleanupException::class => ApiStatus::Experimental,
        FailureResponseFactory::class => ApiStatus::Internal,
        WebRuntimeAdapter::class => ApiStatus::Experimental,
        GeneratedWebRuntimeApplication::class => ApiStatus::Experimental,
        GeneratedWebRuntimeBootstrap::class => ApiStatus::Experimental,
        GeneratedWebRuntimeRoot::class => ApiStatus::Experimental,
    ];

    /** @var list<class-string> */
    private const BOOTSTRAP_TYPES = [
        Api::class,
        ApiStatus::class,
    ];

    public function testProductionDeclarationsExactlyMatchAuthorizedInventoryAndAreAutoloadable(): void
    {
        $types = $this->discoverProductionTypes();
        self::assertCount(262, $types);

        self::assertFalse(class_exists('Kongono\\Kernel\\Api\\Api'));
        self::assertFalse(enum_exists('Kongono\\Kernel\\Api\\ApiStatus'));

        foreach ($types as $type) {
            self::assertSame($type, $this->autoloadedClassLike($type));
        }

        $expected = array_keys(self::EXPECTED_STATUS);
        sort($expected);

        self::assertSame($expected, $types);
    }

    public function testCompositionBuildApiAndDiagnosticsMatchThePhase5DBoundary(): void
    {
        self::assertSame(
            ['__construct', 'artifactSha256', 'builderImplementationSha256', 'canonicalBytes', 'modelSha256', 'sourceSetSha256', 'sourceSha256'],
            $this->declaredPublicMethodNames(new ReflectionClass(CompositionArtifact::class)),
        );
        self::assertSame(['build'], $this->declaredPublicMethodNames(new ReflectionClass(CompositionArtifactBuilder::class)));
        self::assertSame(['publish'], $this->declaredPublicMethodNames(new ReflectionClass(CompositionArtifactPublisher::class)));
        self::assertSame(['validateCurrent', 'validateStructure'], $this->declaredPublicMethodNames(new ReflectionClass(CompositionArtifactValidator::class)));
        self::assertSame(['__construct', 'applicationClass', 'canonicalBytes', 'compositionArtifactSha256', 'generatedNamespace', 'generatorImplementationSha256', 'modelSha256', 'operationClass', 'wiringSha256'], $this->declaredPublicMethodNames(new ReflectionClass(GeneratedWiringArtifact::class)));
        self::assertSame(['generate'], $this->declaredPublicMethodNames(new ReflectionClass(GeneratedWiringGenerator::class)));
        self::assertSame(['publish'], $this->declaredPublicMethodNames(new ReflectionClass(GeneratedWiringPublisher::class)));
        self::assertSame(['validateCurrent', 'validateStructure'], $this->declaredPublicMethodNames(new ReflectionClass(GeneratedWiringValidator::class)));
        self::assertSame(['beginOperation', 'isRetired', 'shutdown'], $this->declaredPublicMethodNames(new ReflectionClass(GeneratedApplicationBoundary::class)));
        self::assertSame(['close', 'isClosed'], $this->declaredPublicMethodNames(new ReflectionClass(GeneratedOperationBoundary::class)));
        self::assertSame(['loadCurrent'], $this->declaredPublicMethodNames(new ReflectionClass(GeneratedWiringLoader::class)));
        self::assertSame(['__construct', 'applicationClass', 'isStarted', 'operationClass', 'start', 'wiringSha256'], $this->declaredPublicMethodNames(new ReflectionClass(LoadedGeneratedWiring::class)));
        self::assertSame(['build', 'check'], $this->declaredPublicMethodNames(new ReflectionClass(ApplicationBuildWorkflow::class)));
        self::assertSame(['__construct', 'artifactSha256', 'compositionArtifactBytes', 'compositionArtifactPath', 'generatedPhpPath', 'generatedWiringSha256'], $this->declaredPublicMethodNames(new ReflectionClass(CurrentApplicationBuild::class)));

        $generatedArtifact = new ReflectionClass(GeneratedWiringArtifact::class);
        self::assertTrue($generatedArtifact->isFinal());
        self::assertTrue($generatedArtifact->isReadOnly());
        self::assertSame(['applicationClass', 'canonicalBytes', 'compositionArtifactSha256', 'generatedNamespace', 'generatorImplementationSha256', 'modelSha256', 'operationClass', 'wiringSha256'], $this->declaredPropertyNames($generatedArtifact));
        $artifactConstructor = $generatedArtifact->getConstructor();
        self::assertNotNull($artifactConstructor);
        self::assertSame(['string', 'string', 'string', 'string', 'string', 'string', 'string', 'string'], array_map(static fn(\ReflectionParameter $parameter): string => (string) $parameter->getType(), $artifactConstructor->getParameters()));

        $generatorMethod = (new ReflectionClass(GeneratedWiringGenerator::class))->getMethod('generate');
        self::assertSame(['string', 'string'], array_map(static fn(\ReflectionParameter $parameter): string => (string) $parameter->getType(), $generatorMethod->getParameters()));
        self::assertSame(GeneratedWiringArtifact::class, (string) $generatorMethod->getReturnType());
        $validatorCurrent = (new ReflectionClass(GeneratedWiringValidator::class))->getMethod('validateCurrent');
        self::assertSame(['string', 'string', 'string'], array_map(static fn(\ReflectionParameter $parameter): string => (string) $parameter->getType(), $validatorCurrent->getParameters()));
        self::assertSame(GeneratedWiringArtifact::class, (string) $validatorCurrent->getReturnType());
        $publisherMethod = (new ReflectionClass(GeneratedWiringPublisher::class))->getMethod('publish');
        self::assertSame([GeneratedWiringArtifact::class, 'string'], array_map(static fn(\ReflectionParameter $parameter): string => (string) $parameter->getType(), $publisherMethod->getParameters()));
        self::assertSame('string', (string) $publisherMethod->getReturnType());

        $applicationBoundary = new ReflectionClass(GeneratedApplicationBoundary::class);
        $operationBoundary = new ReflectionClass(GeneratedOperationBoundary::class);
        self::assertTrue($applicationBoundary->isInterface());
        self::assertTrue($operationBoundary->isInterface());
        self::assertSame(GeneratedOperationBoundary::class, (string) $applicationBoundary->getMethod('beginOperation')->getReturnType());
        self::assertSame(['?Throwable'], array_map(static fn(\ReflectionParameter $parameter): string => (string) $parameter->getType(), $applicationBoundary->getMethod('shutdown')->getParameters()));
        self::assertSame(['?Throwable'], array_map(static fn(\ReflectionParameter $parameter): string => (string) $parameter->getType(), $operationBoundary->getMethod('close')->getParameters()));
        self::assertSame([
            'KGN-COMPOSITION-1001',
            'KGN-COMPOSITION-1002',
            'KGN-COMPOSITION-1003',
            'KGN-COMPOSITION-1004',
            'KGN-COMPOSITION-1005',
            'KGN-COMPOSITION-1006',
            'KGN-COMPOSITION-1007',
            'KGN-COMPOSITION-1008',
            'KGN-COMPOSITION-1009',
            'KGN-COMPOSITION-1010',
            'KGN-COMPOSITION-1011',
            'KGN-COMPOSITION-1012',
            'KGN-COMPOSITION-1013',
            'KGN-COMPOSITION-1014',
            'KGN-COMPOSITION-1015',
            'KGN-COMPOSITION-1016',
            'KGN-COMPOSITION-1017',
            'KGN-COMPOSITION-1018',
        ], array_map(static fn(CompositionDiagnostic $diagnostic): string => $diagnostic->value, CompositionDiagnostic::cases()));

        foreach ([
            InvalidCompositionSourceException::class,
            InvalidCompositionArtifactException::class,
            StaleCompositionArtifactException::class,
            CompositionPublicationException::class,
            InvalidGeneratedWiringException::class,
            GeneratedWiringPublicationException::class,
            RetiredCompositionBoundaryException::class,
            GeneratedWiringLoadException::class,
        ] as $exception) {
            $reflection = new ReflectionClass($exception);
            self::assertTrue($reflection->isFinal());
            self::assertTrue($reflection->implementsInterface(CompositionExceptionInterface::class));
            $parent = $reflection->getParentClass();
            self::assertNotFalse($parent);
            self::assertSame(CompositionException::class, $parent->getName());
        }

        $lifecycle = new ReflectionClass(CompositionLifecycleException::class);
        self::assertTrue($lifecycle->isFinal());
        self::assertTrue($lifecycle->implementsInterface(CompositionExceptionInterface::class));
        self::assertSame(['boundary', 'cleanupFailures', 'diagnosticContext', 'primaryFailure'], $this->declaredPropertyNames($lifecycle));
        self::assertSame(['__construct', '__toString', 'boundary', 'cleanupFailures', 'diagnosticContext', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle', 'primaryFailure'], $this->declaredPublicMethodNames($lifecycle));

        $workflowFailure = new ReflectionClass(ApplicationWorkflowException::class);
        self::assertTrue($workflowFailure->isFinal());
        self::assertTrue($workflowFailure->implementsInterface(CompositionExceptionInterface::class));
        self::assertSame(['__construct', '__toString', 'diagnosticContext', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle'], $this->declaredPublicMethodNames($workflowFailure));
    }

    public function testEveryNonBootstrapTypeHasExactlyOneExpectedApiStatus(): void
    {
        foreach ($this->discoverProductionTypes() as $type) {
            $reflection = new ReflectionClass($this->autoloadedClassLike($type));
            $attributes = $reflection->getAttributes(Api::class);

            if (in_array($type, self::BOOTSTRAP_TYPES, true)) {
                self::assertCount(0, $attributes, sprintf('%s is a documented bootstrap exception.', $type));
                self::assertSame(ApiStatus::Public, self::EXPECTED_STATUS[$type]);

                continue;
            }

            self::assertCount(1, $attributes, sprintf('%s must have exactly one API status.', $type));
            $attribute = $attributes[0]->newInstance();
            self::assertInstanceOf(Api::class, $attribute);
            self::assertInstanceOf(ApiStatus::class, $attribute->status);
            self::assertSame(self::EXPECTED_STATUS[$type], $attribute->status);
        }
    }

    public function testApiStatusDefinesOnlyTheAuthorizedCasesAndValues(): void
    {
        $actual = [];

        foreach (ApiStatus::cases() as $status) {
            $actual[$status->name] = $status->value;
        }

        self::assertSame([
            'Internal' => 'internal',
            'Experimental' => 'experimental',
            'Public' => 'public',
        ], $actual);
    }

    public function testApiAttributeIsNonRepeatableAndTargetsClassLikeDeclarations(): void
    {
        $reflection = new ReflectionClass(Api::class);
        $attributes = $reflection->getAttributes(Attribute::class);

        self::assertCount(1, $attributes);
        $declaration = $attributes[0]->newInstance();
        self::assertInstanceOf(Attribute::class, $declaration);
        self::assertTrue($reflection->isFinal());
        self::assertTrue($reflection->isReadOnly());
        self::assertSame(Attribute::TARGET_CLASS, $declaration->flags);
        self::assertSame(0, $declaration->flags & Attribute::IS_REPEATABLE);
    }

    public function testPackageExceptionMarkersArePairwiseDistinctAndConcreteFailuresStaySeparated(): void
    {
        $markers = [
            KernelExceptionInterface::class,
            GraphExceptionInterface::class,
            DependencyInjectionExceptionInterface::class,
            DiagnosticPresentationExceptionInterface::class,
            DiagnosticsExceptionInterface::class,
            HttpMessageExceptionInterface::class,
            HttpServerExceptionInterface::class,
            RoutingExceptionInterface::class,
            WebApplicationExceptionInterface::class,
            WebRuntimeExceptionInterface::class,
            PhpCgiExceptionInterface::class,
            KnowledgeExceptionInterface::class,
            ChieExceptionInterface::class,
            CompositionExceptionInterface::class,
        ];

        foreach ($markers as $position => $marker) {
            foreach (array_slice($markers, $position + 1) as $otherMarker) {
                self::assertMarkersDistinct($marker, $otherMarker);
            }
        }

        foreach ([
            [LifecycleException::class, KernelExceptionInterface::class],
            [InvalidGraphException::class, GraphExceptionInterface::class],
            [InvalidDefinitionException::class, DependencyInjectionExceptionInterface::class],
            [UnknownServiceException::class, DependencyInjectionExceptionInterface::class],
            [InvalidDiagnosticProjectionException::class, DiagnosticPresentationExceptionInterface::class],
            [InvalidDiagnosticValueException::class, DiagnosticsExceptionInterface::class],
            [InvalidMessageArgumentException::class, HttpMessageExceptionInterface::class],
            [HttpMessageRuntimeException::class, HttpMessageExceptionInterface::class],
            [InvalidRouteDefinitionException::class, RoutingExceptionInterface::class],
            [InvalidRouteRequestException::class, RoutingExceptionInterface::class],
            [InvalidHttpServerDefinitionException::class, HttpServerExceptionInterface::class],
            [OperationCleanupException::class, WebApplicationExceptionInterface::class],
            [RetiredWebApplicationException::class, WebApplicationExceptionInterface::class],
            [RetiredWebRuntimeException::class, WebRuntimeExceptionInterface::class],
            [DeliveryCleanupException::class, WebRuntimeExceptionInterface::class],
            [InvalidCgiRequestException::class, PhpCgiExceptionInterface::class],
            [CgiOperatorBootstrapException::class, PhpCgiExceptionInterface::class],
            [CgiRequestReadException::class, PhpCgiExceptionInterface::class],
            [InvalidCgiResponseException::class, PhpCgiExceptionInterface::class],
            [CgiResponseEmissionException::class, PhpCgiExceptionInterface::class],
            [CgiDeliveryCleanupException::class, PhpCgiExceptionInterface::class],
            [CgiEntryShutdownException::class, PhpCgiExceptionInterface::class],
            [InvalidKnowledgeArtifactException::class, KnowledgeExceptionInterface::class],
            [InvalidKnowledgeSourceException::class, KnowledgeExceptionInterface::class],
            [KnowledgePublicationException::class, KnowledgeExceptionInterface::class],
            [StaleKnowledgeArtifactException::class, KnowledgeExceptionInterface::class],
            [ArtifactUnavailableException::class, ChieExceptionInterface::class],
            [ArtifactStaleException::class, ChieExceptionInterface::class],
            [InvalidChieArtifactException::class, ChieExceptionInterface::class],
            [InvalidChieRequestException::class, ChieExceptionInterface::class],
            [InvalidChieProposalException::class, ChieExceptionInterface::class],
            [ProposalSpecUnavailableException::class, ChieExceptionInterface::class],
            [ProposalTargetStaleException::class, ChieExceptionInterface::class],
            [InvalidCompositionSourceException::class, CompositionExceptionInterface::class],
            [InvalidCompositionArtifactException::class, CompositionExceptionInterface::class],
            [StaleCompositionArtifactException::class, CompositionExceptionInterface::class],
            [CompositionPublicationException::class, CompositionExceptionInterface::class],
            [InvalidGeneratedWiringException::class, CompositionExceptionInterface::class],
            [GeneratedWiringPublicationException::class, CompositionExceptionInterface::class],
            [RetiredCompositionBoundaryException::class, CompositionExceptionInterface::class],
            [CompositionLifecycleException::class, CompositionExceptionInterface::class],
            [ApplicationWorkflowException::class, CompositionExceptionInterface::class],
        ] as [$failure, $expectedMarker]) {
            self::assertExceptionMarkerMembership($failure, $expectedMarker, array_values(array_diff($markers, [$expectedMarker])));
        }
    }

    public function testDiagnosticCatalogsAndPackageExceptionShapesAreExact(): void
    {
        $identifiers = array_map(
            static fn(\BackedEnum $diagnostic): string => $diagnostic->value,
            [
                ...LifecycleDiagnostic::cases(),
                ...GraphDiagnostic::cases(),
                ...DependencyInjectionDiagnostic::cases(),
                ...DiagnosticPresentationDiagnostic::cases(),
                ...DiagnosticsDiagnostic::cases(),
                ...HttpMessageDiagnostic::cases(),
                ...HttpServerDiagnostic::cases(),
                ...RoutingDiagnostic::cases(),
                ...WebApplicationDiagnostic::cases(),
                ...WebRuntimeDiagnostic::cases(),
                ...PhpCgiDiagnostic::cases(),
                ...KnowledgeDiagnostic::cases(),
                ...ChieDiagnostic::cases(),
                ...HanteiDiagnostic::cases(),
                ...CompositionDiagnostic::cases(),
            ],
        );

        self::assertCount(188, $identifiers);
        self::assertCount(188, array_unique($identifiers));
        self::assertCount(6, array_filter($identifiers, static fn(string $identifier): bool => 1 === preg_match('/^KGN-KERNEL-1\\d{3}$/D', $identifier)));
        self::assertCount(11, array_filter($identifiers, static fn(string $identifier): bool => 1 === preg_match('/^KGN-GRAPH-1\\d{3}$/D', $identifier)));
        self::assertCount(25, array_filter($identifiers, static fn(string $identifier): bool => 1 === preg_match('/^KGN-DI-1\\d{3}$/D', $identifier)));
        self::assertCount(15, array_filter($identifiers, static fn(string $identifier): bool => 1 === preg_match('/^KGN-PRESENTATION-1\\d{3}$/D', $identifier)));
        self::assertCount(11, array_filter($identifiers, static fn(string $identifier): bool => 1 === preg_match('/^KGN-DIAGNOSTICS-1\\d{3}$/D', $identifier)));
        self::assertCount(28, array_filter($identifiers, static fn(string $identifier): bool => 1 === preg_match('/^KGN-HTTP-1\\d{3}$/D', $identifier)));
        self::assertCount(3, array_filter($identifiers, static fn(string $identifier): bool => 1 === preg_match('/^KGN-HTTP-SERVER-1\\d{3}$/D', $identifier)));
        self::assertCount(10, array_filter($identifiers, static fn(string $identifier): bool => 1 === preg_match('/^KGN-ROUTING-1\\d{3}$/D', $identifier)));
        self::assertCount(2, array_filter($identifiers, static fn(string $identifier): bool => 1 === preg_match('/^KGN-WEB-APPLICATION-1\\d{3}$/D', $identifier)));
        self::assertCount(3, array_filter($identifiers, static fn(string $identifier): bool => 1 === preg_match('/^KGN-WEB-RUNTIME-1\\d{3}$/D', $identifier)));
        self::assertCount(19, array_filter($identifiers, static fn(string $identifier): bool => 1 === preg_match('/^KGN-PHP-CGI-1\\d{3}$/D', $identifier)));
        self::assertCount(11, array_filter($identifiers, static fn(string $identifier): bool => 1 === preg_match('/^KGN-KNOWLEDGE-1\\d{3}$/D', $identifier)));
        self::assertCount(18, array_filter($identifiers, static fn(string $identifier): bool => 1 === preg_match('/^KGN-CHIE-1\\d{3}$/D', $identifier)));
        self::assertCount(18, array_filter($identifiers, static fn(string $identifier): bool => 1 === preg_match('/^KGN-COMPOSITION-1\\d{3}$/D', $identifier)));

        $productionHttpIdentifiers = array_map(
            static fn(\BackedEnum $diagnostic): string => $diagnostic->value,
            [
                ...HttpMessageDiagnostic::cases(),
                ...HttpServerDiagnostic::cases(),
                ...WebApplicationDiagnostic::cases(),
                ...WebRuntimeDiagnostic::cases(),
                ...PhpCgiDiagnostic::cases(),
            ],
        );
        self::assertCount(55, $productionHttpIdentifiers);
        self::assertCount(55, array_unique($productionHttpIdentifiers));

        foreach ([
            KernelExceptionInterface::class,
            GraphExceptionInterface::class,
            DependencyInjectionExceptionInterface::class,
            DiagnosticPresentationExceptionInterface::class,
            DiagnosticsExceptionInterface::class,
            HttpMessageExceptionInterface::class,
            HttpServerExceptionInterface::class,
            RoutingExceptionInterface::class,
            WebApplicationExceptionInterface::class,
            WebRuntimeExceptionInterface::class,
            PhpCgiExceptionInterface::class,
            KnowledgeExceptionInterface::class,
            ChieExceptionInterface::class,
            CompositionExceptionInterface::class,
        ] as $marker) {
            $reflection = new ReflectionClass($marker);
            $methods = array_values(array_filter(
                $reflection->getMethods(),
                static fn(ReflectionMethod $method): bool => $marker === $method->getDeclaringClass()->getName(),
            ));
            $methodNames = array_map(static fn(ReflectionMethod $method): string => $method->getName(), $methods);
            sort($methodNames);

            self::assertSame([Throwable::class, \Stringable::class], $reflection->getInterfaceNames());
            $expectedMethods = in_array($marker, [KernelExceptionInterface::class, GraphExceptionInterface::class, DependencyInjectionExceptionInterface::class, HttpMessageExceptionInterface::class, HttpServerExceptionInterface::class, RoutingExceptionInterface::class, WebApplicationExceptionInterface::class, WebRuntimeExceptionInterface::class, PhpCgiExceptionInterface::class, KnowledgeExceptionInterface::class, ChieExceptionInterface::class, CompositionExceptionInterface::class], true)
                ? ['diagnosticContext', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle']
                : ['diagnosticId', 'diagnosticRemediation', 'diagnosticTitle'];
            self::assertSame($expectedMethods, $methodNames);

            foreach ($methods as $method) {
                $expectedReturnType = in_array($marker, [KernelExceptionInterface::class, GraphExceptionInterface::class, DependencyInjectionExceptionInterface::class, HttpMessageExceptionInterface::class, HttpServerExceptionInterface::class, RoutingExceptionInterface::class, WebApplicationExceptionInterface::class, WebRuntimeExceptionInterface::class, PhpCgiExceptionInterface::class, KnowledgeExceptionInterface::class, ChieExceptionInterface::class, CompositionExceptionInterface::class], true)
                    && 'diagnosticContext' === $method->getName()
                    ? DiagnosticContext::class
                    : 'string';
                self::assertSame($expectedReturnType, (string) $method->getReturnType());
                self::assertSame([], $method->getParameters());
            }
        }

        foreach ([
            [KernelException::class, LifecycleDiagnostic::class],
            [GraphException::class, GraphDiagnostic::class],
            [DependencyInjectionException::class, DependencyInjectionDiagnostic::class],
            [DiagnosticPresentationException::class, DiagnosticPresentationDiagnostic::class],
            [DiagnosticsException::class, DiagnosticsDiagnostic::class],
        ] as [$base, $diagnostic]) {
            $reflection = new ReflectionClass($base);
            $properties = array_values(array_filter(
                $reflection->getProperties(),
                static fn(\ReflectionProperty $property): bool => $base === $property->getDeclaringClass()->getName(),
            ));
            $methods = array_values(array_filter(
                $reflection->getMethods(),
                static fn(ReflectionMethod $method): bool => $base === $method->getDeclaringClass()->getName(),
            ));
            $methodNames = array_map(static fn(ReflectionMethod $method): string => $method->getName(), $methods);
            sort($methodNames);

            self::assertTrue($reflection->isAbstract());
            usort($properties, static fn(\ReflectionProperty $left, \ReflectionProperty $right): int => strcmp($left->getName(), $right->getName()));

            if (in_array($base, [KernelException::class, GraphException::class, DependencyInjectionException::class], true)) {
                self::assertSame(['diagnostic', 'diagnosticContext'], array_map(
                    static fn(\ReflectionProperty $property): string => $property->getName(),
                    $properties,
                ));
                self::assertSame([$diagnostic, DiagnosticContext::class], array_map(
                    static fn(\ReflectionProperty $property): string => (string) $property->getType(),
                    $properties,
                ));
                self::assertSame(['__construct', 'diagnosticContext', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle'], $methodNames);
            } else {
                self::assertCount(1, $properties);
                self::assertSame('diagnostic', $properties[0]->getName());
                self::assertSame($diagnostic, (string) $properties[0]->getType());
                self::assertSame(['__construct', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle'], $methodNames);
            }

            foreach ($properties as $property) {
                self::assertTrue($property->isPrivate());
                self::assertTrue($property->isReadOnly());
                self::assertFalse($property->isStatic());
            }

            foreach ($methods as $method) {
                self::assertTrue($method->isFinal());
                self::assertTrue($method->isPublic());
            }

            $parameters = $reflection->getConstructor()?->getParameters();
            self::assertNotNull($parameters);
            self::assertCount(in_array($base, [KernelException::class, GraphException::class, DependencyInjectionException::class], true) ? 4 : 3, $parameters);
            self::assertSame($diagnostic, (string) $parameters[0]->getType());
            self::assertSame('string', (string) $parameters[1]->getType());

            $previousParameter = $parameters[2];

            if (in_array($base, [KernelException::class, GraphException::class, DependencyInjectionException::class], true)) {
                self::assertSame('array', (string) $parameters[2]->getType());
                $previousParameter = $parameters[3];
            }

            self::assertSame('?Throwable', (string) $previousParameter->getType());
            self::assertTrue($previousParameter->isDefaultValueAvailable());
            self::assertNull($previousParameter->getDefaultValue());
        }

        $knowledgeBase = new ReflectionClass(KnowledgeException::class);
        self::assertTrue($knowledgeBase->isAbstract());
        self::assertSame(['diagnostic', 'diagnosticContext'], $this->declaredPropertyNames($knowledgeBase));
        self::assertSame(['__construct', '__toString', 'diagnosticContext', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle'], $this->declaredPublicMethodNames($knowledgeBase));
        $knowledgeConstructor = $knowledgeBase->getConstructor();
        self::assertNotNull($knowledgeConstructor);
        self::assertTrue($knowledgeConstructor->isFinal());
        self::assertSame([KnowledgeDiagnostic::class, 'string', '?Throwable'], array_map(static fn(\ReflectionParameter $parameter): string => (string) $parameter->getType(), $knowledgeConstructor->getParameters()));

        $chieBase = new ReflectionClass(ChieException::class);
        self::assertTrue($chieBase->isAbstract());
        self::assertSame(['diagnostic', 'diagnosticContext'], $this->declaredPropertyNames($chieBase));
        self::assertSame(['__construct', '__toString', 'diagnosticContext', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle'], $this->declaredPublicMethodNames($chieBase));
        $chieConstructor = $chieBase->getConstructor();
        self::assertNotNull($chieConstructor);
        self::assertTrue($chieConstructor->isFinal());
        self::assertSame([ChieDiagnostic::class, 'string', '?Throwable'], array_map(static fn(\ReflectionParameter $parameter): string => (string) $parameter->getType(), $chieConstructor->getParameters()));

        $compositionBase = new ReflectionClass(CompositionException::class);
        self::assertTrue($compositionBase->isAbstract());
        self::assertSame(['diagnostic', 'diagnosticContext'], $this->declaredPropertyNames($compositionBase));
        self::assertSame(['__construct', '__toString', 'diagnosticContext', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle'], $this->declaredPublicMethodNames($compositionBase));
        $compositionConstructor = $compositionBase->getConstructor();
        self::assertNotNull($compositionConstructor);
        self::assertFalse($compositionConstructor->isFinal());
        self::assertSame([CompositionDiagnostic::class, 'string', '?Throwable'], array_map(static fn(\ReflectionParameter $parameter): string => (string) $parameter->getType(), $compositionConstructor->getParameters()));

        $chieValueFailure = new ReflectionClass(InvalidChieValueException::class);
        $chieValueParent = $chieValueFailure->getParentClass();
        self::assertNotFalse($chieValueParent);
        self::assertTrue($chieValueFailure->isFinal());
        self::assertSame(InvalidArgumentException::class, $chieValueParent->getName());
        self::assertSame([], $this->declaredPropertyNames($chieValueFailure));
        foreach ([
            KernelExceptionInterface::class,
            GraphExceptionInterface::class,
            DependencyInjectionExceptionInterface::class,
            DiagnosticPresentationExceptionInterface::class,
            DiagnosticsExceptionInterface::class,
            HttpMessageExceptionInterface::class,
            HttpServerExceptionInterface::class,
            RoutingExceptionInterface::class,
            WebApplicationExceptionInterface::class,
            WebRuntimeExceptionInterface::class,
            PhpCgiExceptionInterface::class,
            KnowledgeExceptionInterface::class,
            ChieExceptionInterface::class,
            CompositionExceptionInterface::class,
        ] as $marker) {
            self::assertFalse($chieValueFailure->implementsInterface($marker));
        }
        $chieValueConstructor = $chieValueFailure->getConstructor();
        self::assertNotNull($chieValueConstructor);
        self::assertTrue($chieValueConstructor->isPrivate());
        $chieValueParameters = $chieValueConstructor->getParameters();
        self::assertCount(1, $chieValueParameters);
        self::assertSame('message', $chieValueParameters[0]->getName());
        self::assertSame('string', (string) $chieValueParameters[0]->getType());
        self::assertFalse($chieValueParameters[0]->isDefaultValueAvailable());
        self::assertSame(['__toString', 'candidateText', 'citation', 'claim', 'diagnosticViolation', 'errorEnvelope', 'exceptionDiagnostic', 'proposal', 'response'], $this->declaredPublicMethodNames($chieValueFailure));
        foreach (['citation', 'claim', 'response', 'diagnosticViolation', 'exceptionDiagnostic', 'errorEnvelope', 'candidateText', 'proposal'] as $factory) {
            $method = $chieValueFailure->getMethod($factory);
            self::assertTrue($method->isPublic());
            self::assertTrue($method->isStatic());
            self::assertSame([], $method->getParameters());
            self::assertSame(InvalidChieValueException::class, (string) $method->getReturnType());
        }
        $chieValueString = $chieValueFailure->getMethod('__toString');
        self::assertTrue($chieValueString->isFinal());
        self::assertTrue($chieValueString->isPublic());
        self::assertFalse($chieValueString->isStatic());
        self::assertSame([], $chieValueString->getParameters());
        self::assertSame('string', (string) $chieValueString->getReturnType());

        foreach ([InvalidMessageArgumentException::class, HttpMessageRuntimeException::class] as $exceptionType) {
            $reflection = new ReflectionClass($exceptionType);
            self::assertTrue($reflection->isFinal());
            self::assertTrue($reflection->implementsInterface(HttpMessageExceptionInterface::class));
            $properties = array_values(array_filter($reflection->getProperties(), static fn(\ReflectionProperty $property): bool => $exceptionType === $property->getDeclaringClass()->getName()));
            usort($properties, static fn(\ReflectionProperty $left, \ReflectionProperty $right): int => strcmp($left->getName(), $right->getName()));
            self::assertSame(['diagnostic', 'diagnosticContext'], array_map(static fn(\ReflectionProperty $property): string => $property->getName(), $properties));
            foreach ($properties as $property) {
                self::assertTrue($property->isPrivate());
                self::assertTrue($property->isReadOnly());
            }
            $parameters = $reflection->getConstructor()?->getParameters();
            self::assertNotNull($parameters);
            self::assertSame([HttpMessageDiagnostic::class, 'array', '?Throwable'], array_map(static fn($parameter): string => (string) $parameter->getType(), $parameters));
        }

        foreach ([InvalidRouteDefinitionException::class, InvalidRouteRequestException::class] as $exceptionType) {
            $reflection = new ReflectionClass($exceptionType);
            self::assertTrue($reflection->isFinal());
            self::assertTrue($reflection->implementsInterface(RoutingExceptionInterface::class));
            $properties = array_values(array_filter($reflection->getProperties(), static fn(\ReflectionProperty $property): bool => $exceptionType === $property->getDeclaringClass()->getName()));
            usort($properties, static fn(\ReflectionProperty $left, \ReflectionProperty $right): int => strcmp($left->getName(), $right->getName()));
            self::assertSame(['diagnostic', 'diagnosticContext'], array_map(static fn(\ReflectionProperty $property): string => $property->getName(), $properties));
            foreach ($properties as $property) {
                self::assertTrue($property->isPrivate());
                self::assertTrue($property->isReadOnly());
            }
            $parameters = $reflection->getConstructor()?->getParameters();
            self::assertNotNull($parameters);
            self::assertSame([RoutingDiagnostic::class, 'array', '?Throwable'], array_map(static fn($parameter): string => (string) $parameter->getType(), $parameters));
        }
        $routeDefinitionParent = (new ReflectionClass(InvalidRouteDefinitionException::class))->getParentClass();
        self::assertNotFalse($routeDefinitionParent);
        self::assertSame(InvalidArgumentException::class, $routeDefinitionParent->getName());
        $routeRequestParent = (new ReflectionClass(InvalidRouteRequestException::class))->getParentClass();
        self::assertNotFalse($routeRequestParent);
        self::assertSame(RuntimeException::class, $routeRequestParent->getName());

        $httpServerException = new ReflectionClass(InvalidHttpServerDefinitionException::class);
        self::assertTrue($httpServerException->isFinal());
        self::assertTrue($httpServerException->implementsInterface(HttpServerExceptionInterface::class));
        $httpServerParent = $httpServerException->getParentClass();
        self::assertNotFalse($httpServerParent);
        self::assertSame(InvalidArgumentException::class, $httpServerParent->getName());
        self::assertSame(['diagnostic', 'diagnosticContext'], $this->declaredPropertyNames($httpServerException));
        self::assertSame([HttpServerDiagnostic::class, 'array', '?Throwable'], array_map(
            static fn($parameter): string => (string) $parameter->getType(),
            $httpServerException->getConstructor()?->getParameters() ?? [],
        ));

        $httpServerSchemas = [
            'TooManyMiddleware' => ['Build middleware pipeline', 'A middleware pipeline may contain at most 128 components', ['middleware_count', 'maximum_middleware']],
            'InvalidRouteHandlerKeys' => ['Build terminal route handler', 'Handler keys must equal the complete declared route-name set', ['route_count', 'handler_count', 'missing_count', 'unexpected_count']],
            'InvalidRouteHandlerValue' => ['Build terminal route handler', 'Every mapped route value must implement RequestHandlerInterface', ['handler_position', 'violation']],
        ];
        foreach (HttpServerDiagnostic::cases() as $diagnostic) {
            [$operation, $invariant, $fields] = $httpServerSchemas[$diagnostic->name];
            self::assertSame($operation, $diagnostic->contextOperation());
            self::assertSame($invariant, $diagnostic->contextViolatedInvariant());
            self::assertSame($fields, array_map(static fn($field): string => $field->key(), $diagnostic->contextFields()));
        }
        self::assertSame('not_request_handler', HttpServerDiagnostic::InvalidRouteHandlerValue->context([
            'handler_position' => 0,
            'violation' => 'not_request_handler',
        ])->fields()[1]->value());

        $webApplicationBase = new ReflectionClass(WebApplicationException::class);
        self::assertTrue($webApplicationBase->isAbstract());
        $webApplicationBaseParent = $webApplicationBase->getParentClass();
        self::assertNotFalse($webApplicationBaseParent);
        self::assertSame(RuntimeException::class, $webApplicationBaseParent->getName());
        self::assertSame(['diagnostic', 'diagnosticContext'], $this->declaredPropertyNames($webApplicationBase));
        $webApplicationConstructor = $webApplicationBase->getConstructor();
        self::assertNotNull($webApplicationConstructor);
        self::assertTrue($webApplicationConstructor->isProtected());
        self::assertSame([WebApplicationDiagnostic::class, '?Throwable'], array_map(
            static fn($parameter): string => (string) $parameter->getType(),
            $webApplicationConstructor->getParameters(),
        ));
        foreach (['diagnosticContext', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle'] as $method) {
            self::assertTrue($webApplicationBase->getMethod($method)->isFinal());
        }

        $operationCleanup = new ReflectionClass(OperationCleanupException::class);
        self::assertTrue($operationCleanup->isFinal());
        self::assertSame(['cleanupFailure', 'primaryFailure'], $this->declaredPropertyNames($operationCleanup));
        self::assertSame(['__construct', 'cleanupFailure', 'primaryFailure'], $this->declaredPublicMethodNames($operationCleanup));
        self::assertSame(['Throwable', 'Throwable'], array_map(
            static fn($parameter): string => (string) $parameter->getType(),
            $operationCleanup->getConstructor()?->getParameters() ?? [],
        ));

        $retiredApplication = new ReflectionClass(RetiredWebApplicationException::class);
        self::assertTrue($retiredApplication->isFinal());
        self::assertSame([], $this->declaredPropertyNames($retiredApplication));
        self::assertSame(['__construct'], $this->declaredPublicMethodNames($retiredApplication));
        self::assertSame([], $retiredApplication->getConstructor()?->getParameters());

        $webApplicationSchemas = [
            'ApplicationRetired' => ['Use web application lifecycle', 'A retired application must not accept handling or shutdown work'],
            'OperationAndCleanupFailed' => ['Complete web application operation', 'Primary and cleanup failures must both remain observable'],
        ];
        foreach (WebApplicationDiagnostic::cases() as $diagnostic) {
            [$operation, $invariant] = $webApplicationSchemas[$diagnostic->name];
            self::assertSame($operation, $diagnostic->contextOperation());
            self::assertSame($invariant, $diagnostic->contextViolatedInvariant());
            self::assertSame([], $diagnostic->contextFields());
            self::assertSame([], $diagnostic->context()->fields());
        }

        $graphManifest = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/packages/application-graph/composer.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        self::assertIsArray($graphManifest);
        self::assertArrayHasKey('require', $graphManifest);
        self::assertIsArray($graphManifest['require']);
        self::assertSame([
            'kongono/api-metadata' => '*',
            'kongono/diagnostics' => '*',
            'php' => '>=8.5.0 <8.6.0',
        ], $graphManifest['require']);

        $kernelManifest = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/packages/kernel/composer.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        self::assertIsArray($kernelManifest);
        self::assertArrayHasKey('require', $kernelManifest);
        self::assertIsArray($kernelManifest['require']);
        self::assertSame([
            'kongono/api-metadata' => '*',
            'kongono/diagnostics' => '*',
            'php' => '>=8.5.0 <8.6.0',
        ], $kernelManifest['require']);

        $dependencyInjectionManifest = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/packages/dependency-injection/composer.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        self::assertIsArray($dependencyInjectionManifest);
        self::assertArrayHasKey('require', $dependencyInjectionManifest);
        self::assertIsArray($dependencyInjectionManifest['require']);
        self::assertSame([
            'kongono/api-metadata' => '*',
            'kongono/diagnostics' => '*',
            'php' => '>=8.5.0 <8.6.0',
        ], $dependencyInjectionManifest['require']);

        $diagnosticsManifest = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/packages/diagnostics/composer.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        self::assertIsArray($diagnosticsManifest);
        self::assertArrayHasKey('require', $diagnosticsManifest);
        self::assertIsArray($diagnosticsManifest['require']);
        self::assertSame([
            'kongono/api-metadata' => '*',
            'php' => '>=8.5.0 <8.6.0',
        ], $diagnosticsManifest['require']);

        $diagnosticActivationManifest = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/packages/diagnostic-activation/composer.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        self::assertIsArray($diagnosticActivationManifest);
        self::assertArrayHasKey('require', $diagnosticActivationManifest);
        self::assertIsArray($diagnosticActivationManifest['require']);
        self::assertSame([
            'kongono/api-metadata' => '*',
            'php' => '>=8.5.0 <8.6.0',
        ], $diagnosticActivationManifest['require']);
        self::assertArrayNotHasKey('kongono/diagnostic-handler', $diagnosticActivationManifest['require']);

        $diagnosticHandlerManifest = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/packages/diagnostic-handler/composer.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        self::assertIsArray($diagnosticHandlerManifest);
        self::assertArrayHasKey('require', $diagnosticHandlerManifest);
        self::assertIsArray($diagnosticHandlerManifest['require']);
        self::assertSame([
            'kongono/api-metadata' => '*',
            'kongono/application-graph' => '*',
            'kongono/dependency-injection' => '*',
            'kongono/diagnostic-activation' => '*',
            'kongono/diagnostic-presentation' => '*',
            'kongono/kernel' => '*',
            'php' => '>=8.5.0 <8.6.0',
        ], $diagnosticHandlerManifest['require']);
        self::assertArrayNotHasKey('kongono/diagnostics', $diagnosticHandlerManifest['require']);

        $diagnosticPresentationManifest = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/packages/diagnostic-presentation/composer.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        self::assertIsArray($diagnosticPresentationManifest);
        self::assertArrayHasKey('require', $diagnosticPresentationManifest);
        self::assertIsArray($diagnosticPresentationManifest['require']);
        self::assertSame([
            'kongono/api-metadata' => '*',
            'kongono/application-graph' => '*',
            'kongono/dependency-injection' => '*',
            'kongono/diagnostics' => '*',
            'kongono/kernel' => '*',
            'php' => '>=8.5.0 <8.6.0',
        ], $diagnosticPresentationManifest['require']);
        self::assertArrayNotHasKey('kongono/diagnostic-handler', $diagnosticPresentationManifest['require']);

        $httpMessageManifest = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/packages/http-message/composer.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        self::assertIsArray($httpMessageManifest);
        self::assertArrayHasKey('require', $httpMessageManifest);
        self::assertSame([
            'kongono/api-metadata' => '*',
            'kongono/diagnostics' => '*',
            'php' => '>=8.5.0 <8.6.0',
            'psr/http-factory' => '^1.1',
            'psr/http-message' => '^2.0',
        ], $httpMessageManifest['require']);

        $routingManifest = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/packages/routing/composer.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        self::assertIsArray($routingManifest);
        self::assertArrayHasKey('require', $routingManifest);
        self::assertSame([
            'kongono/api-metadata' => '*',
            'kongono/diagnostics' => '*',
            'php' => '>=8.5.0 <8.6.0',
            'psr/http-message' => '^2.0',
        ], $routingManifest['require']);
        self::assertArrayNotHasKey('kongono/http-message', $routingManifest['require']);

        $httpServerManifest = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/packages/http-server/composer.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        self::assertIsArray($httpServerManifest);
        self::assertArrayHasKey('require', $httpServerManifest);
        self::assertSame([
            'kongono/api-metadata' => '*',
            'kongono/diagnostics' => '*',
            'kongono/routing' => '*',
            'php' => '>=8.5.0 <8.6.0',
            'psr/http-message' => '^2.0',
            'psr/http-server-handler' => '^1.0',
            'psr/http-server-middleware' => '^1.0',
        ], $httpServerManifest['require']);
        foreach (['kongono/http-message', 'kongono/kernel', 'kongono/application-graph', 'kongono/dependency-injection', 'kongono/diagnostic-activation', 'kongono/diagnostic-presentation', 'kongono/diagnostic-handler'] as $forbiddenDependency) {
            self::assertArrayNotHasKey($forbiddenDependency, $httpServerManifest['require']);
        }

        $compositionManifest = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/packages/composition/composer.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        self::assertIsArray($compositionManifest);
        self::assertSame('kongono/composition', $compositionManifest['name']);
        self::assertSame('Deterministic composition artifacts, generated typed wiring, and lifecycle support for Kongono.', $compositionManifest['description']);
        self::assertSame('library', $compositionManifest['type']);
        self::assertSame([
            'ext-hash' => '*',
            'ext-json' => '*',
            'kongono/api-metadata' => '*',
            'kongono/diagnostics' => '*',
            'php' => '>=8.5.0 <8.6.0',
        ], $compositionManifest['require']);
        self::assertIsArray($compositionManifest['autoload']);
        self::assertIsArray($compositionManifest['autoload']['psr-4']);
        self::assertIsArray($compositionManifest['autoload-dev']);
        self::assertIsArray($compositionManifest['autoload-dev']['psr-4']);
        self::assertSame(['Kongono\\Composition\\' => 'src/'], $compositionManifest['autoload']['psr-4']);
        self::assertSame(['Kongono\\Composition\\Tests\\' => 'tests/'], $compositionManifest['autoload-dev']['psr-4']);
        self::assertArrayNotHasKey('bin', $compositionManifest);

        $compositionRoot = dirname(__DIR__, 2) . '/packages/composition';
        self::assertDirectoryDoesNotExist($compositionRoot . '/bin');
        self::assertDirectoryDoesNotExist($compositionRoot . '/resources');
        $compositionCounts = [];
        foreach (['src' => 35, 'tests' => 32] as $directory => $expectedCount) {
            $count = 0;
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($compositionRoot . '/' . $directory, FilesystemIterator::SKIP_DOTS)) as $compositionFile) {
                if (!$compositionFile instanceof SplFileInfo) {
                    throw new RuntimeException('Composition inventory entry is not a file descriptor.');
                }
                if ($compositionFile->isFile() && 'php' === $compositionFile->getExtension()) {
                    ++$count;
                }
                self::assertFalse(str_starts_with($compositionFile->getFilename(), 'kongono-wiring-v1-'));
            }
            self::assertSame($expectedCount, $count);
            $compositionCounts[$directory] = $count;
        }
        self::assertSame(['src' => 35, 'tests' => 32], $compositionCounts);

        $webApplicationManifest = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/packages/web-application/composer.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        self::assertIsArray($webApplicationManifest);
        self::assertSame('kongono/web-application', $webApplicationManifest['name']);
        self::assertSame('Explicit web application composition and Kernel operation ownership for Kongono.', $webApplicationManifest['description']);
        self::assertSame('library', $webApplicationManifest['type']);
        self::assertSame([
            'kongono/api-metadata' => '*',
            'kongono/diagnostics' => '*',
            'kongono/http-server' => '*',
            'kongono/kernel' => '*',
            'kongono/routing' => '*',
            'php' => '>=8.5.0 <8.6.0',
            'psr/http-message' => '^2.0',
            'psr/http-server-handler' => '^1.0',
            'psr/http-server-middleware' => '^1.0',
        ], $webApplicationManifest['require']);
        self::assertIsArray($webApplicationManifest['autoload']);
        self::assertIsArray($webApplicationManifest['autoload']['psr-4']);
        self::assertIsArray($webApplicationManifest['autoload-dev']);
        self::assertIsArray($webApplicationManifest['autoload-dev']['psr-4']);
        self::assertSame(['Kongono\\Web\\Application\\' => 'src/'], $webApplicationManifest['autoload']['psr-4']);
        self::assertSame(['Kongono\\Web\\Application\\Tests\\' => 'tests/'], $webApplicationManifest['autoload-dev']['psr-4']);
        foreach (['kongono/http-message', 'kongono/application-graph', 'kongono/dependency-injection', 'kongono/diagnostic-activation', 'kongono/diagnostic-presentation', 'kongono/diagnostic-handler'] as $forbiddenDependency) {
            self::assertArrayNotHasKey($forbiddenDependency, $webApplicationManifest['require']);
        }

        self::assertSame(['close', 'evaluateTrustedPresentationPermission', 'request', 'send'], $this->declaredPublicMethodNames(new ReflectionClass(WebDeliveryInterface::class)));
        self::assertSame(['__toString', 'diagnosticContext', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle'], $this->declaredPublicMethodNames(new ReflectionClass(WebRuntimeException::class)));
        self::assertSame(['__construct'], $this->declaredPublicMethodNames(new ReflectionClass(RetiredWebRuntimeException::class)));
        self::assertSame(['__construct'], $this->declaredPublicMethodNames(new ReflectionClass(InvalidGeneratedWebRuntimeException::class)));
        self::assertSame(['__construct', 'cleanupFailure', 'primaryFailure'], $this->declaredPublicMethodNames(new ReflectionClass(DeliveryCleanupException::class)));
        self::assertSame(['__construct', 'generic', 'trusted'], $this->declaredPublicMethodNames(new ReflectionClass(FailureResponseFactory::class)));
        self::assertSame(['__construct', 'handle', 'isRetired'], $this->declaredPublicMethodNames(new ReflectionClass(WebRuntimeAdapter::class)));
        self::assertSame(['__construct', 'permission'], $this->declaredPublicMethodNames(new ReflectionClass(GeneratedWebRuntimeBootstrap::class)));
        self::assertSame(['__construct', 'isRetired', 'runtime'], $this->declaredPublicMethodNames(new ReflectionClass(GeneratedWebRuntimeRoot::class)));
        self::assertSame(['isRetired', 'runtime', 'shutdown', 'start'], $this->declaredPublicMethodNames(new ReflectionClass(GeneratedWebRuntimeApplication::class)));

        $rootManifest = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/composer.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        self::assertIsArray($rootManifest);
        self::assertArrayHasKey('require', $rootManifest);
        self::assertIsArray($rootManifest['require']);
        self::assertSame([
            'kongono/api-metadata' => '@dev',
            'kongono/application-graph' => '@dev',
            'kongono/chie' => '@dev',
            'kongono/composition' => '@dev',
            'kongono/dependency-injection' => '@dev',
            'kongono/diagnostic-activation' => '@dev',
            'kongono/diagnostic-handler' => '@dev',
            'kongono/diagnostic-presentation' => '@dev',
            'kongono/diagnostics' => '@dev',
            'kongono/hantei' => '@dev',
            'kongono/http-message' => '@dev',
            'kongono/http-server' => '@dev',
            'kongono/kernel' => '@dev',
            'kongono/knowledge' => '@dev',
            'kongono/oshie' => '@dev',
            'kongono/php-cgi' => '@dev',
            'kongono/routing' => '@dev',
            'kongono/web-application' => '@dev',
            'kongono/web-runtime' => '@dev',
            'php' => '>=8.5.0 <8.6.0',
        ], $rootManifest['require']);

        $diagnosticsSourceFiles = glob(dirname(__DIR__, 2) . '/packages/diagnostics/src/*/*.php');
        self::assertIsArray($diagnosticsSourceFiles);

        foreach ($diagnosticsSourceFiles as $diagnosticsSourceFile) {
            $source = file_get_contents($diagnosticsSourceFile);
            self::assertIsString($source);
            self::assertStringNotContainsString('Kongono\\ApplicationGraph', $source);
            self::assertStringNotContainsString('Kongono\\DependencyInjection', $source);
            self::assertStringNotContainsString('Kongono\\DiagnosticPresentation', $source);
            self::assertStringNotContainsString('Kongono\\Kernel', $source);
        }

        self::assertIsArray($rootManifest['autoload-dev']);
        self::assertIsArray($rootManifest['autoload-dev']['psr-4']);
        self::assertSame('packages/http-message/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\Http\\Message\\Tests\\']);
        self::assertSame('packages/http-server/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\Http\\Server\\Tests\\']);
        self::assertSame('packages/chie/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\Chie\\Tests\\']);
        self::assertSame('packages/composition/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\Composition\\Tests\\']);
        self::assertSame('packages/knowledge/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\Knowledge\\Tests\\']);
        self::assertSame('packages/oshie/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\Oshie\\Tests\\']);
        self::assertSame('packages/hantei/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\Hantei\\Tests\\']);
        self::assertSame('packages/routing/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\Routing\\Tests\\']);
        self::assertSame('packages/web-application/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\Web\\Application\\Tests\\']);
        self::assertSame('packages/web-runtime/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\Web\\Runtime\\Tests\\']);
        self::assertSame('packages/php-cgi/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\PhpCgi\\Tests\\']);
        self::assertSame('skeletons/minimal/src/', $rootManifest['autoload-dev']['psr-4']['App\\']);
        self::assertIsArray($rootManifest['scripts']);
        self::assertIsArray($rootManifest['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/http-message validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/http-server validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/chie validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/composition validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/knowledge validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/oshie validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/hantei validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/routing validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/php-cgi validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        $validatePackages = array_values($rootManifest['scripts']['validate:packages']);
        self::assertNotSame([], $validatePackages);
        self::assertSame('composer --working-dir=packages/web-runtime validate --no-check-publish', $validatePackages[count($validatePackages) - 1]);
        self::assertSame('composer --working-dir=skeletons/minimal validate --no-check-publish', $rootManifest['scripts']['validate:skeleton']);
        self::assertIsArray($rootManifest['scripts']['quality']);
        self::assertSame('@validate:skeleton', $rootManifest['scripts']['quality'][2]);

        $allPackageManifests = glob(dirname(__DIR__, 2) . '/packages/*/composer.json');
        self::assertIsArray($allPackageManifests);
        self::assertCount(19, $allPackageManifests);
        self::assertSame('>=8.5.0 <8.6.0', $rootManifest['require']['php']);
        foreach ($allPackageManifests as $packageManifestPath) {
            $manifest = json_decode((string) file_get_contents($packageManifestPath), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($manifest);
            self::assertIsArray($manifest['require']);
            self::assertSame('>=8.5.0 <8.6.0', $manifest['require']['php']);
        }
        foreach ($allPackageManifests as $packageManifestPath) {
            if (str_ends_with(str_replace('\\', '/', $packageManifestPath), '/web-application/composer.json')
                || str_ends_with(str_replace('\\', '/', $packageManifestPath), '/web-runtime/composer.json')
                || str_ends_with(str_replace('\\', '/', $packageManifestPath), '/php-cgi/composer.json')
                || str_ends_with(str_replace('\\', '/', $packageManifestPath), '/chie/composer.json')
                || str_ends_with(str_replace('\\', '/', $packageManifestPath), '/hantei/composer.json')
                || str_ends_with(str_replace('\\', '/', $packageManifestPath), '/oshie/composer.json')) {
                continue;
            }
            $manifest = json_decode((string) file_get_contents($packageManifestPath), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($manifest);
            self::assertIsArray($manifest['require']);
            self::assertArrayNotHasKey('kongono/web-application', $manifest['require']);
            self::assertArrayNotHasKey('kongono/web-runtime', $manifest['require']);
            self::assertArrayNotHasKey('kongono/knowledge', $manifest['require']);
        }

        $knowledgeManifest = json_decode((string) file_get_contents(dirname(__DIR__, 2) . '/packages/knowledge/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($knowledgeManifest);
        self::assertIsArray($knowledgeManifest['autoload']);
        self::assertIsArray($knowledgeManifest['autoload']['psr-4']);
        self::assertIsArray($knowledgeManifest['autoload-dev']);
        self::assertIsArray($knowledgeManifest['autoload-dev']['psr-4']);
        self::assertSame(['ext-hash' => '*', 'ext-json' => '*', 'kongono/api-metadata' => '*', 'kongono/diagnostics' => '*', 'php' => '>=8.5.0 <8.6.0'], $knowledgeManifest['require']);
        self::assertSame(['Kongono\\Knowledge\\' => 'src/'], $knowledgeManifest['autoload']['psr-4']);
        self::assertSame(['Kongono\\Knowledge\\Tests\\' => 'tests/'], $knowledgeManifest['autoload-dev']['psr-4']);
        self::assertSame(['bin/kongono-knowledge'], $knowledgeManifest['bin']);
        self::assertSame(31, KnowledgeSourceInventory::REQUIRED_SOURCE_RECORDS);

        $chieManifest = json_decode((string) file_get_contents(dirname(__DIR__, 2) . '/packages/chie/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($chieManifest);
        self::assertIsArray($chieManifest['require']);
        self::assertIsArray($chieManifest['autoload']);
        self::assertIsArray($chieManifest['autoload']['psr-4']);
        self::assertIsArray($chieManifest['autoload-dev']);
        self::assertIsArray($chieManifest['autoload-dev']['psr-4']);
        self::assertSame(['ext-hash' => '*', 'ext-json' => '*', 'kongono/api-metadata' => '*', 'kongono/diagnostics' => '*', 'kongono/knowledge' => '*', 'php' => '>=8.5.0 <8.6.0'], $chieManifest['require']);
        self::assertSame(['Kongono\\Chie\\' => 'src/'], $chieManifest['autoload']['psr-4']);
        self::assertSame(['Kongono\\Chie\\Tests\\' => 'tests/'], $chieManifest['autoload-dev']['psr-4']);
        self::assertSame(['bin/kongono-chie'], $chieManifest['bin']);
        foreach ($allPackageManifests as $packageManifestPath) {
            if (str_ends_with(str_replace('\\', '/', $packageManifestPath), '/chie/composer.json')) {
                continue;
            }
            $manifest = json_decode((string) file_get_contents($packageManifestPath), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($manifest);
            self::assertIsArray($manifest['require']);
            self::assertArrayNotHasKey('kongono/chie', $manifest['require']);
        }

        $hanteiManifest = json_decode((string) file_get_contents(dirname(__DIR__, 2) . '/packages/hantei/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($hanteiManifest);
        self::assertIsArray($hanteiManifest['require']);
        self::assertIsArray($hanteiManifest['autoload']);
        self::assertIsArray($hanteiManifest['autoload']['psr-4']);
        self::assertIsArray($hanteiManifest['autoload-dev']);
        self::assertIsArray($hanteiManifest['autoload-dev']['psr-4']);
        self::assertSame([
            'ext-hash' => '*',
            'ext-json' => '*',
            'kongono/api-metadata' => '*',
            'kongono/diagnostics' => '*',
            'kongono/knowledge' => '*',
            'php' => '>=8.5.0 <8.6.0',
        ], $hanteiManifest['require']);
        self::assertSame(['Kongono\\Hantei\\' => 'src/'], $hanteiManifest['autoload']['psr-4']);
        self::assertSame(['Kongono\\Hantei\\Tests\\' => 'tests/'], $hanteiManifest['autoload-dev']['psr-4']);
        self::assertSame(['bin/kongono-hantei'], $hanteiManifest['bin']);
        self::assertArrayNotHasKey('kongono/chie', $hanteiManifest['require']);
        self::assertSame('@php packages/hantei/bin/kongono-hantei verify .', $rootManifest['scripts']['hantei:verify']);
        self::assertIsArray($rootManifest['scripts']['quality']);
        self::assertNotContains('@hantei:verify', $rootManifest['scripts']['quality']);
        $oshieManifest = json_decode((string) file_get_contents(dirname(__DIR__, 2) . '/packages/oshie/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($oshieManifest);
        self::assertIsArray($oshieManifest['require']);
        self::assertIsArray($oshieManifest['autoload']);
        self::assertIsArray($oshieManifest['autoload']['psr-4']);
        self::assertIsArray($oshieManifest['autoload-dev']);
        self::assertIsArray($oshieManifest['autoload-dev']['psr-4']);
        self::assertSame(['ext-hash' => '*', 'ext-json' => '*', 'kongono/api-metadata' => '*', 'kongono/diagnostics' => '*', 'kongono/knowledge' => '*', 'php' => '>=8.5.0 <8.6.0'], $oshieManifest['require']);
        self::assertSame(['Kongono\\Oshie\\' => 'src/'], $oshieManifest['autoload']['psr-4']);
        self::assertSame(['Kongono\\Oshie\\Tests\\' => 'tests/'], $oshieManifest['autoload-dev']['psr-4']);
        self::assertSame(['bin/kongono-oshie'], $oshieManifest['bin']);
        self::assertArrayNotHasKey('kongono/chie', $oshieManifest['require']);
        self::assertArrayNotHasKey('kongono/hantei', $oshieManifest['require']);
        self::assertSame('@php packages/oshie/bin/kongono-oshie', $rootManifest['scripts']['oshie']);
        self::assertNotContains('@oshie', $rootManifest['scripts']['quality']);
        self::assertSame(['KGN-OSHIE-1001', 'KGN-OSHIE-1002', 'KGN-OSHIE-1003', 'KGN-OSHIE-1004', 'KGN-OSHIE-1005', 'KGN-OSHIE-1006', 'KGN-OSHIE-1007', 'KGN-OSHIE-1008', 'KGN-OSHIE-1009'], array_map(static fn(OshieDiagnostic $diagnostic): string => $diagnostic->value, OshieDiagnostic::cases()));
        self::assertContains(OshieExceptionInterface::class, (new ReflectionClass(OshieException::class))->getInterfaceNames());
        self::assertNotContains(OshieExceptionInterface::class, (new ReflectionClass(InvalidOshieValueException::class))->getInterfaceNames());
        self::assertSame([
            'KGN-HANTEI-1001',
            'KGN-HANTEI-1002',
            'KGN-HANTEI-1003',
            'KGN-HANTEI-1004',
            'KGN-HANTEI-1005',
            'KGN-HANTEI-1006',
            'KGN-HANTEI-1007',
            'KGN-HANTEI-1008',
        ], array_map(static fn(HanteiDiagnostic $diagnostic): string => $diagnostic->value, HanteiDiagnostic::cases()));
        self::assertContains(HanteiExceptionInterface::class, (new ReflectionClass(HanteiException::class))->getInterfaceNames());
        self::assertNotContains(HanteiExceptionInterface::class, (new ReflectionClass(InvalidHanteiValueException::class))->getInterfaceNames());

        $httpFiles = glob(dirname(__DIR__, 2) . '/packages/http-message/src/*/*.php');
        self::assertIsArray($httpFiles);
        foreach ($httpFiles as $httpFile) {
            $source = file_get_contents($httpFile);
            self::assertIsString($source);
            foreach (['Kongono\\ApplicationGraph', 'Kongono\\DependencyInjection', 'Kongono\\DiagnosticActivation', 'Kongono\\DiagnosticHandler', 'Kongono\\DiagnosticPresentation', 'Kongono\\Kernel'] as $forbidden) {
                self::assertStringNotContainsString($forbidden, $source);
            }
            foreach (['use Psr\\Http\\Message\\RequestFactoryInterface;', 'Psr\\Http\\Server', 'Psr\\Http\\Client', 'Symfony', 'Guzzle', 'Laminas', 'Nyholm', 'Slim'] as $forbidden) {
                self::assertStringNotContainsString($forbidden, $source);
            }
        }

        $routingFiles = glob(dirname(__DIR__, 2) . '/packages/routing/src/*/*.php');
        self::assertIsArray($routingFiles);
        self::assertCount(10, $routingFiles);
        foreach ($routingFiles as $routingFile) {
            $source = file_get_contents($routingFile);
            self::assertIsString($source);
            foreach (['Kongono\\ApplicationGraph', 'Kongono\\DependencyInjection', 'Kongono\\DiagnosticActivation', 'Kongono\\DiagnosticHandler', 'Kongono\\DiagnosticPresentation', 'Kongono\\Http\\Message', 'Kongono\\Kernel', 'Psr\\Http\\Server'] as $forbidden) {
                self::assertStringNotContainsString($forbidden, $source);
            }
            foreach (['$_SERVER', '$_GET', '$_POST', '$_COOKIE', '$_FILES', 'getHeaders(', 'getBody(', 'getCookieParams(', 'getQueryParams(', 'getServerParams(', 'getParsedBody(', 'getUploadedFiles(', 'getAttributes(', 'getAttribute(', 'getRequestTarget('] as $forbidden) {
                self::assertStringNotContainsString($forbidden, $source);
            }
        }

        $httpServerRootFiles = glob(dirname(__DIR__, 2) . '/packages/http-server/src/*.php');
        $httpServerNestedFiles = glob(dirname(__DIR__, 2) . '/packages/http-server/src/*/*.php');
        self::assertIsArray($httpServerRootFiles);
        self::assertIsArray($httpServerNestedFiles);
        $httpServerFiles = [...$httpServerRootFiles, ...$httpServerNestedFiles];
        self::assertCount(6, $httpServerFiles);
        foreach ($httpServerFiles as $httpServerFile) {
            $source = file_get_contents($httpServerFile);
            self::assertIsString($source);
            foreach (['Kongono\\ApplicationGraph', 'Kongono\\DependencyInjection', 'Kongono\\DiagnosticActivation', 'Kongono\\DiagnosticHandler', 'Kongono\\DiagnosticPresentation', 'Kongono\\Http\\Message', 'Kongono\\Kernel'] as $forbidden) {
                self::assertStringNotContainsString($forbidden, $source);
            }
            foreach (['$_SERVER', '$_GET', '$_POST', '$_COOKIE', '$_FILES', 'getHeaders(', 'getBody(', 'getCookieParams(', 'getQueryParams(', 'getServerParams(', 'getParsedBody(', 'getUploadedFiles(', 'getAttributes(', 'getRequestTarget(', 'class-string', 'callable', 'ReflectionClass', 'container'] as $forbidden) {
                self::assertStringNotContainsString($forbidden, $source);
            }
        }

        $webApplicationRootFiles = glob(dirname(__DIR__, 2) . '/packages/web-application/src/*.php');
        $webApplicationNestedFiles = glob(dirname(__DIR__, 2) . '/packages/web-application/src/*/*.php');
        self::assertIsArray($webApplicationRootFiles);
        self::assertIsArray($webApplicationNestedFiles);
        $webApplicationFiles = [...$webApplicationRootFiles, ...$webApplicationNestedFiles];
        self::assertCount(6, $webApplicationFiles);
        foreach ($webApplicationFiles as $webApplicationFile) {
            $source = file_get_contents($webApplicationFile);
            self::assertIsString($source);
            foreach (['Kongono\\ApplicationGraph', 'Kongono\\DependencyInjection', 'Kongono\\DiagnosticActivation', 'Kongono\\DiagnosticHandler', 'Kongono\\DiagnosticPresentation', 'Kongono\\Http\\Message'] as $forbidden) {
                self::assertStringNotContainsString($forbidden, $source);
            }
            foreach (['$GLOBALS', '$_SERVER', '$_ENV', '$_GET', '$_POST', '$_COOKIE', '$_FILES', 'getenv(', 'putenv(', 'microtime(', 'random_', 'file_get_contents(', 'file_put_contents(', 'fopen(', 'curl_', 'ReflectionClass', 'DependencyResolver', 'ApplicationGraph', 'TrustedDeveloper', 'emit', 'header(', 'exit(', 'die('] as $forbidden) {
                self::assertStringNotContainsString($forbidden, $source);
            }
        }

        $routeDefinition = new ReflectionClass(RouteDefinition::class);
        self::assertTrue($routeDefinition->isFinal());
        self::assertTrue($routeDefinition->isReadOnly());
        self::assertSame(['methods', 'name', 'pathTemplate'], $this->declaredPropertyNames($routeDefinition));
        self::assertSame(['__construct', 'methods', 'name', 'pathTemplate'], $this->declaredPublicMethodNames($routeDefinition));
        $routeTable = new ReflectionClass(RouteTable::class);
        self::assertTrue($routeTable->isFinal());
        self::assertTrue($routeTable->isReadOnly());
        self::assertSame(['routes'], $this->declaredPropertyNames($routeTable));
        self::assertSame(['__construct', 'routes'], $this->declaredPublicMethodNames($routeTable));
        $routeMatch = new ReflectionClass(RouteMatch::class);
        self::assertTrue($routeMatch->isFinal());
        self::assertTrue($routeMatch->isReadOnly());
        self::assertSame(['parameters', 'route'], $this->declaredPropertyNames($routeMatch));
        self::assertSame(['__construct', 'parameters', 'route'], $this->declaredPublicMethodNames($routeMatch));
        $matcherInterface = new ReflectionClass(RouteMatcherInterface::class);
        self::assertSame(['match'], $this->declaredPublicMethodNames($matcherInterface));
        $matcher = new ReflectionClass(RouteMatcher::class);
        self::assertTrue($matcher->isFinal());
        self::assertTrue($matcher->isReadOnly());
        self::assertSame(['compiledRoutes', 'routes'], $this->declaredPropertyNames($matcher));
        self::assertSame(['__construct', 'match'], $this->declaredPublicMethodNames($matcher));
        self::assertTrue($matcher->implementsInterface(RouteMatcherInterface::class));
        $syntax = new ReflectionClass(RouteSyntax::class);
        self::assertTrue($syntax->isFinal());
        self::assertSame([], $syntax->getProperties());
        self::assertTrue($syntax->getConstructor()?->isPrivate());
        self::assertSame(['requestMethod', 'requestPath', 'routeMatchParameters', 'routeMethods', 'routeName', 'routeTemplate'], $this->declaredPublicMethodNames($syntax));

        $pipeline = new ReflectionClass(MiddlewarePipeline::class);
        self::assertTrue($pipeline->isFinal());
        self::assertTrue($pipeline->isReadOnly());
        self::assertTrue($pipeline->implementsInterface(RequestHandlerInterface::class));
        self::assertSame(['entryHandler', 'middleware', 'terminalHandler'], $this->declaredPropertyNames($pipeline));
        self::assertSame(['__construct', 'handle', 'middleware', 'terminalHandler'], $this->declaredPublicMethodNames($pipeline));
        self::assertSame(128, $pipeline->getConstant('MAX_MIDDLEWARE'));
        $pipelineParameters = $pipeline->getConstructor()?->getParameters() ?? [];
        self::assertSame([RequestHandlerInterface::class, MiddlewareInterface::class], array_map(static fn($parameter): string => (string) $parameter->getType(), $pipelineParameters));
        self::assertTrue($pipelineParameters[1]->isVariadic());

        $layer = new ReflectionClass(MiddlewareLayer::class);
        self::assertTrue($layer->isFinal());
        self::assertTrue($layer->isReadOnly());
        self::assertTrue($layer->implementsInterface(RequestHandlerInterface::class));
        self::assertSame(['middleware', 'nextHandler'], $this->declaredPropertyNames($layer));
        self::assertSame(['__construct', 'handle'], $this->declaredPublicMethodNames($layer));

        $routingHandler = new ReflectionClass(RoutingRequestHandler::class);
        self::assertTrue($routingHandler->isFinal());
        self::assertTrue($routingHandler->isReadOnly());
        self::assertTrue($routingHandler->implementsInterface(RequestHandlerInterface::class));
        self::assertSame(RouteMatch::class, $routingHandler->getConstant('ROUTE_MATCH_ATTRIBUTE'));
        self::assertSame(['handlers', 'matcher', 'routes', 'unmatchedHandler'], $this->declaredPropertyNames($routingHandler));
        self::assertSame(['__construct', 'handle', 'handlers', 'routes', 'unmatchedHandler'], $this->declaredPublicMethodNames($routingHandler));
        self::assertSame([RouteTable::class, 'array', RequestHandlerInterface::class], array_map(
            static fn($parameter): string => (string) $parameter->getType(),
            $routingHandler->getConstructor()?->getParameters() ?? [],
        ));

        $webApplication = new ReflectionClass(WebApplication::class);
        self::assertTrue($webApplication->isFinal());
        self::assertFalse($webApplication->isReadOnly());
        self::assertFalse($webApplication->getParentClass());
        self::assertSame([RequestHandlerInterface::class], $webApplication->getInterfaceNames());
        $webProperties = array_values(array_filter(
            $webApplication->getProperties(),
            static fn(\ReflectionProperty $property): bool => WebApplication::class === $property->getDeclaringClass()->getName(),
        ));
        usort($webProperties, static fn(\ReflectionProperty $left, \ReflectionProperty $right): int => strcmp($left->getName(), $right->getName()));
        self::assertSame(['kernel', 'pipeline', 'retired'], array_map(static fn(\ReflectionProperty $property): string => $property->getName(), $webProperties));
        foreach ($webProperties as $property) {
            self::assertTrue($property->isPrivate());
            self::assertFalse($property->isStatic());
        }
        self::assertTrue($webProperties[0]->isReadOnly());
        self::assertTrue($webProperties[1]->isReadOnly());
        self::assertFalse($webProperties[2]->isReadOnly());
        $webConstructor = $webApplication->getConstructor();
        self::assertNotNull($webConstructor);
        self::assertTrue($webConstructor->isPublic());
        self::assertSame([KernelInterface::class, RequestHandlerInterface::class], array_map(
            static fn($parameter): string => (string) $parameter->getType(),
            $webConstructor->getParameters(),
        ));
        self::assertTrue($webApplication->getMethod('__clone')->isPrivate());
        self::assertFalse($webApplication->hasMethod('__destruct'));
        self::assertSame(['__construct', 'boot', 'handle', 'isRetired', 'shutdown'], $this->declaredPublicMethodNames($webApplication));

        self::assertSame(['handle'], $this->declaredPublicMethodNames(new ReflectionClass(RequestHandlerInterface::class)));
        self::assertSame(['process'], $this->declaredPublicMethodNames(new ReflectionClass(MiddlewareInterface::class)));

        foreach ([
            '.php-cs-fixer.dist.php' => ['packages/routing/src', 'packages/routing/tests', 'packages/web-application/src', 'packages/web-application/tests', 'packages/web-runtime/src', 'packages/web-runtime/tests', 'packages/php-cgi/src', 'packages/php-cgi/tests'],
            'phpstan.neon.dist' => ['packages/routing/src', 'packages/routing/tests', 'packages/http-server/src', 'packages/http-server/tests', 'packages/web-application/src', 'packages/web-application/tests', 'packages/web-runtime/src', 'packages/web-runtime/tests', 'packages/php-cgi/src', 'packages/php-cgi/tests'],
            'phpunit.xml.dist' => ['packages/routing/src', 'packages/routing/tests', 'packages/http-server/src', 'packages/http-server/tests', 'packages/web-application/src', 'packages/web-application/tests', 'packages/web-runtime/src', 'packages/web-runtime/tests', 'packages/php-cgi/src', 'packages/php-cgi/tests'],
        ] as $configuration => $needles) {
            $contents = file_get_contents(dirname(__DIR__, 2) . '/' . $configuration);
            self::assertIsString($contents);
            foreach ($needles as $needle) {
                self::assertStringContainsString($needle, $contents);
                self::assertSame(1, substr_count($contents, $needle));
            }
        }

        $relationships = [
            'Kongono\\Http\\Message\\Message\\Response' => 'Psr\\Http\\Message\\ResponseInterface',
            'Kongono\\Http\\Message\\Message\\ServerRequest' => 'Psr\\Http\\Message\\ServerRequestInterface',
            'Kongono\\Http\\Message\\Message\\Stream' => 'Psr\\Http\\Message\\StreamInterface',
            'Kongono\\Http\\Message\\Message\\UploadedFile' => 'Psr\\Http\\Message\\UploadedFileInterface',
            'Kongono\\Http\\Message\\Message\\Uri' => 'Psr\\Http\\Message\\UriInterface',
            'Kongono\\Http\\Message\\Factory\\ResponseFactory' => 'Psr\\Http\\Message\\ResponseFactoryInterface',
            'Kongono\\Http\\Message\\Factory\\ServerRequestFactory' => 'Psr\\Http\\Message\\ServerRequestFactoryInterface',
            'Kongono\\Http\\Message\\Factory\\StreamFactory' => 'Psr\\Http\\Message\\StreamFactoryInterface',
            'Kongono\\Http\\Message\\Factory\\UploadedFileFactory' => 'Psr\\Http\\Message\\UploadedFileFactoryInterface',
            'Kongono\\Http\\Message\\Factory\\UriFactory' => 'Psr\\Http\\Message\\UriFactoryInterface',
        ];
        foreach ($relationships as $implementation => $interface) {
            self::assertContains($interface, (new ReflectionClass($implementation))->getInterfaceNames());
        }
        foreach (array_slice(array_keys($relationships), 5) as $factory) {
            $reflection = new ReflectionClass($factory);
            self::assertTrue($reflection->isFinal());
            self::assertSame([], $reflection->getProperties());
            self::assertNull($reflection->getConstructor());
        }
    }

    public function testPhpCgiPackageShapeDependenciesAndIsolationAreExact(): void
    {
        $root = dirname(__DIR__, 2);
        $sourceFiles = glob($root . '/packages/php-cgi/src/*/*.php');
        self::assertIsArray($sourceFiles);
        self::assertCount(27, $sourceFiles);
        $diagnosticActivationImportPaths = [
            '/Bootstrap/CgiOperatorBootstrap.php',
            '/Bootstrap/CgiOperatorProfile.php',
            '/Delivery/CgiWebDelivery.php',
            '/Internal/CgiTrustedPresentationPermissionEvaluator.php',
        ];
        foreach ($sourceFiles as $sourceFile) {
            $source = file_get_contents($sourceFile);
            self::assertIsString($source);
            $normalizedSourceFile = str_replace('\\', '/', $sourceFile);
            self::assertSame(0, preg_match('~Kongono\\\\(?:ApplicationGraph|DependencyInjection|DiagnosticHandler|DiagnosticPresentation|Http\\\\Server|Kernel|Routing|Web\\\\Application)|Psr\\\\Http\\\\Server|\$(?:GLOBALS|_ENV|_REQUEST|_GET|_COOKIE)|\b(?:php_sapi_name|ini_get_all|getenv|putenv|listener|socket|DependencyResolver|ApplicationGraph)\b|\.env|\b(?:exit|die)\s*\(|FastCGI parser|process manager|operator config|trust evaluation~', $source));

            $isDelivery = str_ends_with($normalizedSourceFile, '/Delivery/CgiWebDelivery.php');
            $isEntry = str_ends_with($normalizedSourceFile, '/Entry/CgiWebEntry.php');
            $isFrontController = str_ends_with($normalizedSourceFile, '/Entry/CgiFrontController.php');
            $isGeneratedFrontController = str_ends_with($normalizedSourceFile, '/Entry/CgiGeneratedFrontController.php');
            if (!$isDelivery) {
                foreach (['$_SERVER', '$_POST', '$_FILES', 'php://input', 'WebDeliveryInterface'] as $forbiddenCapture) {
                    self::assertStringNotContainsString($forbiddenCapture, $source);
                }
            }
            if (!$isDelivery && !$isEntry && !$isFrontController && !$isGeneratedFrontController) {
                self::assertStringNotContainsString('Kongono\\Web\\Runtime', $source);
            }

            $importsDiagnosticActivation = str_contains($source, 'use Kongono\DiagnosticActivation\\');
            $mayImportDiagnosticActivation = array_any(
                $diagnosticActivationImportPaths,
                static fn(string $suffix): bool => str_ends_with($normalizedSourceFile, $suffix),
            );
            self::assertSame($mayImportDiagnosticActivation, $importsDiagnosticActivation);

            if (str_ends_with($normalizedSourceFile, '/Internal/CgiOperatorBootstrapValidation.php')) {
                self::assertSame(2, substr_count($source, 'PHP_SAPI'));
                self::assertSame(1, substr_count($source, 'ini_get('));
            } elseif (!$isDelivery) {
                self::assertStringNotContainsString('PHP_SAPI', $source);
                self::assertStringNotContainsString('ini_get(', $source);
            }

            if (str_ends_with($normalizedSourceFile, '/Internal/CgiNativeEmissionTarget.php')) {
                foreach (['headers_sent()', 'http_response_code(', 'header(', "file_put_contents('php://output'", 'set_error_handler(', 'restore_error_handler()'] as $requiredNativeCall) {
                    self::assertStringContainsString($requiredNativeCall, $source);
                }
            } else {
                self::assertSame(0, preg_match('~\b(?:headers_sent|http_response_code|header|header_remove|flush|ob_[a-z_]+|set_error_handler|restore_error_handler)\s*\(|php://output|file_put_contents\s*\(~', $source));
            }
            self::assertSame(0, preg_match('~\b(?:header_remove|flush|register_shutdown_function)\s*\(|\bob_[a-z_]+\s*\(|__destruct|\b(?:retry|fallback|secondResponse|currentResponse)\b~i', $source));
        }
        $manifest = json_decode((string) file_get_contents($root . '/packages/php-cgi/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertSame(['kongono/api-metadata' => '*', 'kongono/diagnostic-activation' => '*', 'kongono/diagnostics' => '*', 'kongono/http-message' => '*', 'kongono/web-runtime' => '*', 'php' => '>=8.5.0 <8.6.0', 'psr/http-message' => '^2.0'], $manifest['require']);
        self::assertIsArray($manifest['autoload']);
        self::assertIsArray($manifest['autoload']['psr-4']);
        self::assertIsArray($manifest['autoload-dev']);
        self::assertIsArray($manifest['autoload-dev']['psr-4']);
        self::assertSame(['Kongono\\PhpCgi\\' => 'src/'], $manifest['autoload']['psr-4']);
        self::assertSame(['Kongono\\PhpCgi\\Tests\\' => 'tests/'], $manifest['autoload-dev']['psr-4']);
        $packageManifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($packageManifests);
        self::assertCount(19, $packageManifests);
        foreach ($packageManifests as $packageManifestPath) {
            if (str_ends_with(str_replace('\\', '/', $packageManifestPath), '/php-cgi/composer.json')) {
                continue;
            }

            $packageManifest = json_decode((string) file_get_contents($packageManifestPath), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($packageManifest);
            self::assertIsArray($packageManifest['require']);
            self::assertArrayNotHasKey('kongono/php-cgi', $packageManifest['require']);
        }
        $converter = new ReflectionClass(CgiServerRequestConverter::class);
        $convert = $converter->getMethod('convert');
        self::assertSame([true, [], null, ['convert'], ['string', 'array', 'array', 'array', 'mixed'], ServerRequestInterface::class], [$converter->isFinal(), $converter->getProperties(), $converter->getConstructor(), $this->declaredPublicMethodNames($converter), array_map(static fn(\ReflectionParameter $parameter): string => (string) $parameter->getType(), $convert->getParameters()), (string) $convert->getReturnType()]);
        $emitter = new ReflectionClass(CgiResponseEmitter::class);
        $emit = $emitter->getMethod('emit');
        self::assertSame([true, [], null, ['emit'], ['Psr\Http\Message\ResponseInterface', 'string'], 'void'], [$emitter->isFinal(), $emitter->getProperties(), $emitter->getConstructor(), $this->declaredPublicMethodNames($emitter), array_map(static fn(\ReflectionParameter $parameter): string => (string) $parameter->getType(), $emit->getParameters()), (string) $emit->getReturnType()]);
        foreach ([
            InvalidCgiRequestException::class => [InvalidArgumentException::class, [PhpCgiDiagnostic::class, 'string', '?Throwable']],
            CgiOperatorBootstrapException::class => [RuntimeException::class, ['string', '?Throwable']],
            CgiRequestReadException::class => [RuntimeException::class, ['string', '?Throwable']],
            CgiDeliveryCleanupException::class => [RuntimeException::class, ['string', '?Throwable']],
            InvalidCgiResponseException::class => [InvalidArgumentException::class, [PhpCgiDiagnostic::class, 'string', '?Throwable']],
        ] as $exceptionType => [$expectedParent, $expectedParameters]) {
            $exception = new ReflectionClass($exceptionType);
            $parent = $exception->getParentClass();
            self::assertNotFalse($parent);
            self::assertSame([true, true, ['diagnostic', 'diagnosticContext'], ['__construct', 'diagnosticContext', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle'], $expectedParent, $expectedParameters], [$exception->isFinal(), $exception->implementsInterface(PhpCgiExceptionInterface::class), $this->declaredPropertyNames($exception), $this->declaredPublicMethodNames($exception), $parent->getName(), array_map(static fn(\ReflectionParameter $parameter): string => (string) $parameter->getType(), $exception->getConstructor()?->getParameters() ?? [])]);
        }
        $entryShutdownException = new ReflectionClass(CgiEntryShutdownException::class);
        $entryShutdownParent = $entryShutdownException->getParentClass();
        self::assertNotFalse($entryShutdownParent);
        self::assertSame(
            [true, true, ['diagnostic', 'diagnosticContext', 'primaryFailure', 'shutdownFailure'], ['__construct', 'diagnosticContext', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle', 'primaryFailure', 'shutdownFailure'], RuntimeException::class, ['Throwable', 'Throwable']],
            [$entryShutdownException->isFinal(), $entryShutdownException->implementsInterface(PhpCgiExceptionInterface::class), $this->declaredPropertyNames($entryShutdownException), $this->declaredPublicMethodNames($entryShutdownException), $entryShutdownParent->getName(), array_map(static fn(\ReflectionParameter $parameter): string => (string) $parameter->getType(), $entryShutdownException->getConstructor()?->getParameters() ?? [])],
        );
        foreach ([CgiRequestValidation::class, CgiHeaderProjector::class, CgiQueryParser::class, CgiCookieParser::class] as $helperType) {
            $helper = new ReflectionClass($helperType);
            $constructor = $helper->getConstructor();
            self::assertNotNull($constructor);
            self::assertSame([true, [], [], true], [$helper->isFinal(), $helper->getProperties(), $helper->getConstants(), $constructor->isPrivate()]);
            foreach ($helper->getMethods() as $method) {
                if ('__construct' !== $method->getName() && $helperType === $method->getDeclaringClass()->getName()) {
                    self::assertTrue($method->isStatic());
                }
            }
        }

        $bootstrapValidation = new ReflectionClass(CgiOperatorBootstrapValidation::class);
        $bootstrapValidationConstructor = $bootstrapValidation->getConstructor();
        self::assertNotNull($bootstrapValidationConstructor);
        self::assertSame([true, [], ['DIRECTIVE_NAMES'], true, ['captureAndValidate', 'validate']], [$bootstrapValidation->isFinal(), $bootstrapValidation->getProperties(), array_keys($bootstrapValidation->getConstants()), $bootstrapValidationConstructor->isPrivate(), $this->declaredPublicMethodNames($bootstrapValidation)]);

        $permissionEvaluator = new ReflectionClass(CgiTrustedPresentationPermissionEvaluator::class);
        $permissionEvaluatorConstructor = $permissionEvaluator->getConstructor();
        self::assertNotNull($permissionEvaluatorConstructor);
        self::assertSame([true, [], [], true, ['evaluate']], [$permissionEvaluator->isFinal(), $permissionEvaluator->getProperties(), $permissionEvaluator->getConstants(), $permissionEvaluatorConstructor->isPrivate(), $this->declaredPublicMethodNames($permissionEvaluator)]);

        $delivery = new ReflectionClass(CgiWebDelivery::class);
        self::assertSame([true, false, true, ['capture', 'close', 'evaluateTrustedPresentationPermission', 'fromSnapshot', 'request', 'send']], [$delivery->isFinal(), $delivery->isReadOnly(), $delivery->implementsInterface(WebDeliveryInterface::class), $this->declaredPublicMethodNames($delivery)]);
        $entry = new ReflectionClass(CgiWebEntry::class);
        self::assertSame([true, [], true, ['run']], [$entry->isFinal(), $entry->getProperties(), $entry->getConstructor()?->isPrivate(), $this->declaredPublicMethodNames($entry)]);
        $frontController = new ReflectionClass(CgiFrontController::class);
        $frontConstructor = $frontController->getConstructor();
        self::assertNotNull($frontConstructor);
        $frontRun = $frontController->getMethod('run');
        self::assertSame(
            [true, [], [], [], [], true, ['run'], true, [WebRuntimeAdapter::class, 'Closure'], 'void'],
            [$frontController->isFinal(), $frontController->getProperties(), $frontController->getConstants(), $frontController->getInterfaceNames(), $frontController->getTraitNames(), $frontConstructor->isPrivate(), $this->declaredPublicMethodNames($frontController), $frontRun->isStatic(), array_map(static fn(\ReflectionParameter $parameter): string => (string) $parameter->getType(), $frontRun->getParameters()), (string) $frontRun->getReturnType()],
        );
        $frontSource = file_get_contents($root . '/packages/php-cgi/src/Entry/CgiFrontController.php');
        self::assertIsString($frontSource);
        self::assertSame(1, substr_count($frontSource, 'CgiWebEntry::run($runtime)'));
        self::assertSame(1, substr_count($frontSource, '$shutdown();'));
        self::assertStringNotContainsString('finally', $frontSource);
        self::assertSame(0, preg_match('~register_shutdown_function|__destruct|header(?:_remove)?\\s*\\(|flush\\s*\\(|ob_[a-z_]+\\s*\\(|\\b(?:exit|die)\\s*\\(|listener|socket|process manager|service locator|container~i', $frontSource));

        $generatedFrontController = new ReflectionClass(CgiGeneratedFrontController::class);
        $generatedRun = $generatedFrontController->getMethod('run');
        self::assertSame(
            [true, true, ['run'], true, [GeneratedWebRuntimeApplication::class], 'void'],
            [$generatedFrontController->isFinal(), $generatedFrontController->getConstructor()?->isPrivate(), $this->declaredPublicMethodNames($generatedFrontController), $generatedRun->isStatic(), array_map(static fn(\ReflectionParameter $parameter): string => (string) $parameter->getType(), $generatedRun->getParameters()), (string) $generatedRun->getReturnType()],
        );

    }

    public function testPhase3ExternalAcceptanceRemainsTestOnlyAndProductionIsUnchanged(): void
    {
        $root = dirname(__DIR__, 2);
        $externalRoot = $root . '/tests/External/PhpCgi';
        $externalFiles = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($externalRoot, FilesystemIterator::SKIP_DOTS),
        );
        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && 'php' === $file->getExtension()) {
                $externalFiles[] = str_replace('\\', '/', $file->getPathname());
            }
        }
        sort($externalFiles);
        self::assertCount(11, $externalFiles);

        foreach ($externalFiles as $externalFile) {
            $source = file_get_contents($externalFile);
            self::assertIsString($source);
            self::assertStringNotContainsString('#[Api', $source);
            self::assertStringNotContainsString('Kongono\\ApiMetadata', $source);
            $declarations = $this->classLikeDeclarations($source);
            self::assertLessThanOrEqual(1, count($declarations));
            if ([] !== $declarations) {
                $relative = substr($externalFile, strlen(str_replace('\\', '/', $root . '/tests/')));
                self::assertSame(
                    'Kongono\\Tests\\' . str_replace('/', '\\', substr($relative, 0, -4)),
                    $declarations[0],
                );
            }
        }

        $productionFiles = [];
        $sourceDirectories = glob($root . '/packages/*/src', GLOB_ONLYDIR);
        self::assertIsArray($sourceDirectories);
        self::assertCount(19, $sourceDirectories);
        foreach ($sourceDirectories as $sourceDirectory) {
            $sourceIterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($sourceDirectory, FilesystemIterator::SKIP_DOTS),
            );
            /** @var SplFileInfo $sourceFile */
            foreach ($sourceIterator as $sourceFile) {
                if ($sourceFile->isFile() && 'php' === $sourceFile->getExtension()) {
                    $productionFiles[] = $sourceFile->getPathname();
                }
            }
        }
        self::assertCount(262, $productionFiles);
        foreach ($productionFiles as $productionFile) {
            $source = file_get_contents($productionFile);
            self::assertIsString($source);
            foreach (['Kongono\\Tests\\External', 'FastCgiRecordCodec', 'FastCgiClient', 'FastCgiResponse', 'PhpCgiProcess', 'Phase3AcceptanceRunner', 'KONGONO_PHASE3_ACCEPTANCE_JOURNAL'] as $forbidden) {
                self::assertStringNotContainsString($forbidden, $source);
            }
        }

        $manifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($manifests);
        self::assertCount(19, $manifests);
        foreach ($manifests as $manifest) {
            $contents = file_get_contents($manifest);
            self::assertIsString($contents);
            self::assertStringNotContainsString('Kongono\\Tests\\External', $contents);
            self::assertStringNotContainsString('tests/External', $contents);
        }

        $rootManifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($rootManifest);
        self::assertIsArray($rootManifest['scripts']);
        self::assertSame('@php tests/External/PhpCgi/run.php', $rootManifest['scripts']['acceptance:phase3']);
        self::assertIsArray($rootManifest['autoload-dev']);
        self::assertIsArray($rootManifest['autoload-dev']['psr-4']);
        self::assertSame('tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\Tests\\']);
        self::assertArrayNotHasKey('Kongono\\Tests\\External\\', $rootManifest['autoload-dev']['psr-4']);
    }

    /**
     * @template T of object
     * @param ReflectionClass<T> $reflection
     * @return list<string>
     */
    private function declaredPropertyNames(ReflectionClass $reflection): array
    {
        $properties = array_values(array_filter(
            $reflection->getProperties(),
            static fn(\ReflectionProperty $property): bool => $reflection->getName() === $property->getDeclaringClass()->getName(),
        ));
        foreach ($properties as $property) {
            self::assertTrue($property->isPrivate());
            self::assertTrue($property->isReadOnly());
            self::assertFalse($property->isStatic());
        }
        $names = array_map(static fn(\ReflectionProperty $property): string => $property->getName(), $properties);
        sort($names);

        return $names;
    }

    /**
     * @template T of object
     * @param ReflectionClass<T> $reflection
     * @return list<string>
     */
    private function declaredPublicMethodNames(ReflectionClass $reflection): array
    {
        $methods = array_values(array_filter(
            $reflection->getMethods(ReflectionMethod::IS_PUBLIC),
            static fn(ReflectionMethod $method): bool => $reflection->getName() === $method->getDeclaringClass()->getName(),
        ));
        $names = array_map(static fn(ReflectionMethod $method): string => $method->getName(), $methods);
        sort($names);

        return $names;
    }

    /** @return list<string> */
    private function discoverProductionTypes(): array
    {
        $packageSourceDirectories = glob(dirname(__DIR__, 2) . '/packages/*/src', GLOB_ONLYDIR);

        if (false === $packageSourceDirectories) {
            throw new RuntimeException('Unable to discover package source directories.');
        }

        sort($packageSourceDirectories);
        self::assertCount(19, $packageSourceDirectories);
        $packageManifests = glob(dirname(__DIR__, 2) . '/packages/*/composer.json');
        self::assertIsArray($packageManifests);
        self::assertCount(19, $packageManifests);
        $types = [];

        foreach ($packageSourceDirectories as $sourceDirectory) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($sourceDirectory, FilesystemIterator::SKIP_DOTS),
            );

            /** @var SplFileInfo $file */
            foreach ($files as $file) {
                if (!$file->isFile() || 'php' !== $file->getExtension()) {
                    continue;
                }

                $source = file_get_contents($file->getPathname());

                if (false === $source) {
                    throw new RuntimeException(sprintf('Unable to read production source %s.', $file->getPathname()));
                }

                array_push($types, ...$this->classLikeDeclarations($source));
            }
        }

        $types = array_values(array_unique($types));
        sort($types);

        return $types;
    }

    /** @return list<string> */
    private function classLikeDeclarations(string $source): array
    {
        $tokens = token_get_all($source);
        $namespace = '';
        $types = [];
        $count = count($tokens);

        for ($index = 0; $index < $count; ++$index) {
            $token = $tokens[$index];

            if (!is_array($token)) {
                continue;
            }

            if (T_NAMESPACE === $token[0]) {
                $namespace = $this->namespaceFollowing($tokens, $index + 1);

                continue;
            }

            if (!in_array($token[0], [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM], true)) {
                continue;
            }

            if (T_CLASS === $token[0]) {
                $previous = $this->previousSignificantTokenId($tokens, $index);

                if (T_NEW === $previous || T_DOUBLE_COLON === $previous) {
                    continue;
                }
            }

            $name = $this->declarationNameFollowing($tokens, $index + 1);

            if (null !== $name) {
                $types[] = '' === $namespace ? $name : $namespace . '\\' . $name;
            }
        }

        return $types;
    }

    /**
     * @param list<array{int, string, int}|string> $tokens
     */
    private function namespaceFollowing(array $tokens, int $start): string
    {
        $namespace = '';

        for ($index = $start, $count = count($tokens); $index < $count; ++$index) {
            $token = $tokens[$index];

            if (';' === $token || '{' === $token) {
                break;
            }

            if (is_array($token) && in_array($token[0], [T_STRING, T_NAME_QUALIFIED, T_NS_SEPARATOR], true)) {
                $namespace .= $token[1];
            }
        }

        return trim($namespace, '\\');
    }

    /**
     * @param list<array{int, string, int}|string> $tokens
     */
    private function declarationNameFollowing(array $tokens, int $start): ?string
    {
        for ($index = $start, $count = count($tokens); $index < $count; ++$index) {
            $token = $tokens[$index];

            if (is_array($token) && T_STRING === $token[0]) {
                return $token[1];
            }

            if ('{' === $token || ';' === $token) {
                return null;
            }
        }

        return null;
    }

    /**
     * @param list<array{int, string, int}|string> $tokens
     */
    private function previousSignificantTokenId(array $tokens, int $before): ?int
    {
        for ($index = $before - 1; $index >= 0; --$index) {
            $token = $tokens[$index];

            if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            return is_array($token) ? $token[0] : null;
        }

        return null;
    }

    /** @return class-string */
    private function autoloadedClassLike(string $type): string
    {
        if (class_exists($type) || interface_exists($type) || trait_exists($type) || enum_exists($type)) {
            return $type;
        }

        throw new RuntimeException(sprintf('Production type %s is not autoloadable.', $type));
    }

    /**
     * @param class-string              $exception
     * @param class-string              $expectedMarker
     * @param list<class-string>        $excludedMarkers
     */
    private static function assertExceptionMarkerMembership(
        string $exception,
        string $expectedMarker,
        array $excludedMarkers,
    ): void {
        self::assertTrue(is_a($exception, $expectedMarker, true));

        foreach ($excludedMarkers as $excludedMarker) {
            self::assertFalse(is_a($exception, $excludedMarker, true));
        }
    }

    private static function assertMarkersDistinct(string $left, string $right): void
    {
        self::assertFalse(is_a($left, $right, true));
        self::assertFalse(is_a($right, $left, true));
    }
}
