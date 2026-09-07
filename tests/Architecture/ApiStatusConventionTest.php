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
use Kongono\Authentication\AuthenticationEvidence;
use Kongono\Authentication\AuthenticationFacts;
use Kongono\Authentication\AuthenticationInstant;
use Kongono\Authentication\AuthenticationMechanism;
use Kongono\Authentication\AuthenticationOrchestrator;
use Kongono\Authentication\AuthenticationRequest;
use Kongono\Authentication\AuthenticationRequirement;
use Kongono\Authentication\AuthenticationStrength;
use Kongono\Authentication\AuthenticationVerification;
use Kongono\Authentication\AuthenticationVerificationOperation;
use Kongono\Authentication\AuthenticationVerifier;
use Kongono\Authentication\Diagnostic\AuthenticationDiagnostic;
use Kongono\Authentication\Exception\AuthenticationCleanupException;
use Kongono\Authentication\Exception\AuthenticationException;
use Kongono\Authentication\Exception\AuthenticationOperationAndCleanupException;
use Kongono\Authentication\Exception\ExceptionInterface as AuthenticationExceptionInterface;
use Kongono\Authentication\Exception\InvalidAuthenticationValueException;
use Kongono\Authorization\AuthorizationDecisionReceipt;
use Kongono\Authorization\AuthorizationEngine;
use Kongono\Authorization\AuthorizationEnvironmentEvidence;
use Kongono\Authorization\AuthorizationEnvironmentProfile;
use Kongono\Authorization\AuthorizationEvaluation;
use Kongono\Authorization\AuthorizationEvaluator;
use Kongono\Authorization\AuthorizationPolicyEvidence;
use Kongono\Authorization\AuthorizationPolicyName;
use Kongono\Authorization\AuthorizationPolicyProvenance;
use Kongono\Authorization\AuthorizationPolicyReference;
use Kongono\Authorization\AuthorizationPolicyVersion;
use Kongono\Authorization\AuthorizationReceiptReason;
use Kongono\Authorization\AuthorizationRequest;
use Kongono\Authorization\Diagnostic\AuthorizationDiagnostic;
use Kongono\Authorization\Exception\AuthorizationEvaluationException;
use Kongono\Authorization\Exception\AuthorizationException;
use Kongono\Authorization\Exception\ExceptionInterface as AuthorizationExceptionInterface;
use Kongono\Authorization\Exception\InvalidAuthorizationValueException;
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
use Kongono\Database\Diagnostic\DatabaseDiagnostic;
use Kongono\Database\Exception\ExceptionInterface as DatabaseExceptionInterface;
use Kongono\DatabaseMapping\Diagnostic\MappingDiagnostic;
use Kongono\DatabaseMigration\Diagnostic\MigrationDiagnostic;
use Kongono\DatabaseMigration\Exception\ExceptionInterface as MigrationExceptionInterface;
use Kongono\DatabaseSql\Diagnostic\SqlDiagnostic;
use Kongono\DatabaseSql\Exception\ExceptionInterface as SqlExceptionInterface;
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
use Kongono\IdentityAccess\Action;
use Kongono\IdentityAccess\AuthenticationOutcome;
use Kongono\IdentityAccess\AuthenticationOutcomeKind;
use Kongono\IdentityAccess\AuthorizationDecision;
use Kongono\IdentityAccess\Diagnostic\IdentityAccessDiagnostic;
use Kongono\IdentityAccess\Exception\ExceptionInterface as IdentityAccessExceptionInterface;
use Kongono\IdentityAccess\Exception\IdentityAccessException;
use Kongono\IdentityAccess\Exception\InvalidIdentityAccessValueException;
use Kongono\IdentityAccess\PrincipalId;
use Kongono\IdentityAccess\PrincipalKind;
use Kongono\IdentityAccess\PrincipalReference;
use Kongono\IdentityAccess\ResourceId;
use Kongono\IdentityAccess\ResourceReference;
use Kongono\IdentityAccess\ResourceType;
use Kongono\IdentityAccess\SecurityContext;
use Kongono\IdentityAccess\SecurityContextState;
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
use Kongono\Persistence\Diagnostic\PersistenceDiagnostic;
use Kongono\Persistence\Exception\ExceptionInterface as PersistenceExceptionInterface;
use Kongono\PersistenceApplication\Diagnostic\PersistenceApplicationDiagnostic;
use Kongono\PersistenceBuild\Diagnostic\PersistenceBuildDiagnostic;
use Kongono\PersistenceBuild\Exception\ExceptionInterface as PersistenceBuildExceptionInterface;
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
        'Kongono\\AgentAdmission\\Admission\\AgentAdmissionBuilder' => ApiStatus::Experimental,
        'Kongono\\AgentAdmission\\Admission\\AgentAdmissionDecision' => ApiStatus::Experimental,
        'Kongono\\AgentAdmission\\Admission\\AgentAdmissionRequest' => ApiStatus::Experimental,
        'Kongono\\AgentAdmission\\Authority\\AgentAuthorityPlan' => ApiStatus::Experimental,
        'Kongono\\AgentAdmission\\Authority\\CapabilityAuthorityDecision' => ApiStatus::Experimental,
        'Kongono\\AgentAdmission\\Authority\\CapabilityAuthorityStatus' => ApiStatus::Experimental,
        'Kongono\\AgentAdmission\\Diagnostic\\AgentAdmissionDiagnostic' => ApiStatus::Internal,
        'Kongono\\AgentAdmission\\Evidence\\CapabilityAuthorizationEvidence' => ApiStatus::Experimental,
        'Kongono\\AgentAdmission\\Exception\\AgentAdmissionException' => ApiStatus::Internal,
        'Kongono\\AgentAdmission\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\AgentAdmission\\Exception\\InvalidAgentAdmissionException' => ApiStatus::Experimental,
        'Kongono\\AgentAdmission\\Identity\\AgentAdmissionIdentity' => ApiStatus::Experimental,
        'Kongono\\AgentAdmission\\Internal\\AdmissionGuard' => ApiStatus::Internal,
        'Kongono\\AgentAdmission\\Internal\\CanonicalAdmission' => ApiStatus::Internal,
        'Kongono\\AgentApplication\\Diagnostic\\AgentApplicationDiagnostic' => ApiStatus::Internal,
        'Kongono\\AgentApplication\\Exception\\AgentApplicationCleanupException' => ApiStatus::Experimental,
        'Kongono\\AgentApplication\\Exception\\AgentApplicationException' => ApiStatus::Internal,
        'Kongono\\AgentApplication\\Exception\\AgentApplicationLifecycleException' => ApiStatus::Experimental,
        'Kongono\\AgentApplication\\Exception\\AgentApplicationOperationAndCleanupException' => ApiStatus::Experimental,
        'Kongono\\AgentApplication\\Exception\\AgentApplicationOperationException' => ApiStatus::Experimental,
        'Kongono\\AgentApplication\\Exception\\AgentApplicationWorkflowException' => ApiStatus::Experimental,
        'Kongono\\AgentApplication\\Exception\\AgentWiringLoadException' => ApiStatus::Experimental,
        'Kongono\\AgentApplication\\Exception\\AgentWiringPublicationException' => ApiStatus::Experimental,
        'Kongono\\AgentApplication\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\AgentApplication\\Exception\\InvalidAgentApplicationProfileException' => ApiStatus::Experimental,
        'Kongono\\AgentApplication\\Exception\\InvalidGeneratedAgentWiringException' => ApiStatus::Experimental,
        'Kongono\\AgentApplication\\Generated\\AgentWiringGenerator' => ApiStatus::Experimental,
        'Kongono\\AgentApplication\\Generated\\AgentWiringPublisher' => ApiStatus::Experimental,
        'Kongono\\AgentApplication\\Generated\\AgentWiringValidator' => ApiStatus::Experimental,
        'Kongono\\AgentApplication\\Generated\\GeneratedAgentApplicationBoundary' => ApiStatus::Experimental,
        'Kongono\\AgentApplication\\Generated\\GeneratedAgentWiring' => ApiStatus::Experimental,
        'Kongono\\AgentApplication\\Internal\\CanonicalProfile' => ApiStatus::Internal,
        'Kongono\\AgentApplication\\Internal\\GeneratedWiringEmitter' => ApiStatus::Internal,
        'Kongono\\AgentApplication\\Internal\\GeneratedWiringImplementation' => ApiStatus::Internal,
        'Kongono\\AgentApplication\\Internal\\LifecycleFailureComposer' => ApiStatus::Internal,
        'Kongono\\AgentApplication\\Internal\\PathGuard' => ApiStatus::Internal,
        'Kongono\\AgentApplication\\Profile\\AgentApplicationProfile' => ApiStatus::Experimental,
        'Kongono\\AgentApplication\\Profile\\AgentApplicationProfileFactory' => ApiStatus::Experimental,
        'Kongono\\AgentApplication\\Profile\\AgentApplicationProfileIdentity' => ApiStatus::Experimental,
        'Kongono\\AgentApplication\\Runtime\\AgentApplication' => ApiStatus::Experimental,
        'Kongono\\AgentApplication\\Runtime\\AgentApplicationRunInput' => ApiStatus::Experimental,
        'Kongono\\AgentApplication\\Runtime\\AgentApplicationState' => ApiStatus::Experimental,
        'Kongono\\AgentApplication\\Runtime\\AgentWiringLoader' => ApiStatus::Experimental,
        'Kongono\\AgentApplication\\Runtime\\LoadedAgentWiring' => ApiStatus::Experimental,
        'Kongono\\AgentApplication\\Workflow\\AgentApplicationBuildWorkflow' => ApiStatus::Experimental,
        'Kongono\\AgentApplication\\Workflow\\CurrentAgentApplicationBuild' => ApiStatus::Experimental,
        'Kongono\\AgentInspection\\Derivation\\AgentIntelligenceDeriver' => ApiStatus::Experimental,
        'Kongono\\AgentInspection\\Derivation\\AgentIntelligenceView' => ApiStatus::Experimental,
        'Kongono\\AgentInspection\\Diagnostic\\AgentInspectionDiagnostic' => ApiStatus::Internal,
        'Kongono\\AgentInspection\\Exception\\AgentInspectionBindingException' => ApiStatus::Experimental,
        'Kongono\\AgentInspection\\Exception\\AgentInspectionException' => ApiStatus::Internal,
        'Kongono\\AgentInspection\\Exception\\AgentInspectionProjectionException' => ApiStatus::Experimental,
        'Kongono\\AgentInspection\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\AgentInspection\\Exception\\InvalidAgentInspectionException' => ApiStatus::Experimental,
        'Kongono\\AgentInspection\\Exception\\InvalidAgentIntelligenceEvidenceException' => ApiStatus::Experimental,
        'Kongono\\AgentInspection\\Exception\\InvalidCompletedRuntimeEvidenceException' => ApiStatus::Experimental,
        'Kongono\\AgentInspection\\Graph\\AgentGraphEvidence' => ApiStatus::Experimental,
        'Kongono\\AgentInspection\\Graph\\AgentGraphEvidenceProjector' => ApiStatus::Experimental,
        'Kongono\\AgentInspection\\Inspection\\AgentPrincipalInspection' => ApiStatus::Experimental,
        'Kongono\\AgentInspection\\Inspection\\AgentPrincipalInspectionRequest' => ApiStatus::Experimental,
        'Kongono\\AgentInspection\\Inspection\\AgentPrincipalInspector' => ApiStatus::Experimental,
        'Kongono\\AgentInspection\\Internal\\CanonicalJson' => ApiStatus::Internal,
        'Kongono\\AgentInspection\\Projection\\AgentInspectionObservationProjector' => ApiStatus::Experimental,
        'Kongono\\AgentInspection\\Projection\\AgentInspectionSecurityAuditProjector' => ApiStatus::Experimental,
        'Kongono\\AgentInspection\\Role\\DeveloperIntelligenceRole' => ApiStatus::Experimental,
        'Kongono\\AgentInspection\\Verification\\AgentIntelligenceVerdict' => ApiStatus::Experimental,
        'Kongono\\AgentInspection\\Verification\\AgentIntelligenceVerification' => ApiStatus::Experimental,
        'Kongono\\AgentInspection\\Verification\\AgentIntelligenceVerifier' => ApiStatus::Experimental,
        'Kongono\\AgentRuntime\\Authority\\AgentAuthorityCurrentness' => ApiStatus::Experimental,
        'Kongono\\AgentRuntime\\Budget\\AgentRuntimeBudget' => ApiStatus::Experimental,
        'Kongono\\AgentRuntime\\Budget\\AgentRuntimeUsage' => ApiStatus::Experimental,
        'Kongono\\AgentRuntime\\Cancellation\\AgentCancellation' => ApiStatus::Experimental,
        'Kongono\\AgentRuntime\\Capability\\AgentCapabilityInvocation' => ApiStatus::Experimental,
        'Kongono\\AgentRuntime\\Capability\\AgentCapabilityOutcome' => ApiStatus::Experimental,
        'Kongono\\AgentRuntime\\Capability\\AgentCapabilityPort' => ApiStatus::Experimental,
        'Kongono\\AgentRuntime\\Capability\\AgentCapabilityResult' => ApiStatus::Experimental,
        'Kongono\\AgentRuntime\\Clock\\AgentMonotonicClock' => ApiStatus::Experimental,
        'Kongono\\AgentRuntime\\Diagnostic\\AgentRuntimeDiagnostic' => ApiStatus::Internal,
        'Kongono\\AgentRuntime\\Exception\\AgentRuntimeCleanupException' => ApiStatus::Experimental,
        'Kongono\\AgentRuntime\\Exception\\AgentRuntimeException' => ApiStatus::Internal,
        'Kongono\\AgentRuntime\\Exception\\AgentRuntimeOperationAndCleanupException' => ApiStatus::Experimental,
        'Kongono\\AgentRuntime\\Exception\\AgentRuntimeOperationException' => ApiStatus::Experimental,
        'Kongono\\AgentRuntime\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\AgentRuntime\\Exception\\InvalidAgentRuntimeException' => ApiStatus::Experimental,
        'Kongono\\AgentRuntime\\Internal\\BudgetMeter' => ApiStatus::Internal,
        'Kongono\\AgentRuntime\\Internal\\LifecycleFailureComposer' => ApiStatus::Internal,
        'Kongono\\AgentRuntime\\Internal\\ReceiptFactory' => ApiStatus::Internal,
        'Kongono\\AgentRuntime\\Internal\\RuntimeGuard' => ApiStatus::Internal,
        'Kongono\\AgentRuntime\\Proposal\\AgentProposalPort' => ApiStatus::Experimental,
        'Kongono\\AgentRuntime\\Proposal\\AgentProposalRequest' => ApiStatus::Experimental,
        'Kongono\\AgentRuntime\\Proposal\\AgentProposalResult' => ApiStatus::Experimental,
        'Kongono\\AgentRuntime\\Runtime\\AgentRuntime' => ApiStatus::Experimental,
        'Kongono\\AgentRuntime\\Runtime\\AgentRuntimeOutcome' => ApiStatus::Experimental,
        'Kongono\\AgentRuntime\\Runtime\\AgentRuntimeRequest' => ApiStatus::Experimental,
        'Kongono\\AgentRuntime\\Runtime\\AgentRuntimeResult' => ApiStatus::Experimental,
        'Kongono\\AgentRuntime\\Runtime\\AgentRuntimeState' => ApiStatus::Experimental,
        'Kongono\\AgentDeclarationBuild\\Artifact\\AgentDeclarationArtifact' => ApiStatus::Experimental,
        'Kongono\\AgentDeclarationBuild\\Artifact\\AgentDeclarationArtifactBuilder' => ApiStatus::Experimental,
        'Kongono\\AgentDeclarationBuild\\Artifact\\AgentDeclarationArtifactPublisher' => ApiStatus::Experimental,
        'Kongono\\AgentDeclarationBuild\\Artifact\\AgentDeclarationArtifactValidator' => ApiStatus::Experimental,
        'Kongono\\AgentDeclarationBuild\\Declaration\\AgentDeclaration' => ApiStatus::Experimental,
        'Kongono\\AgentDeclarationBuild\\Declaration\\AgentDeclarationIdentity' => ApiStatus::Experimental,
        'Kongono\\AgentDeclarationBuild\\Diagnostic\\AgentDeclarationBuildDiagnostic' => ApiStatus::Internal,
        'Kongono\\AgentDeclarationBuild\\Exception\\AgentDeclarationBuildException' => ApiStatus::Internal,
        'Kongono\\AgentDeclarationBuild\\Exception\\AgentDeclarationPublicationException' => ApiStatus::Experimental,
        'Kongono\\AgentDeclarationBuild\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\AgentDeclarationBuild\\Exception\\InvalidAgentDeclarationArtifactException' => ApiStatus::Experimental,
        'Kongono\\AgentDeclarationBuild\\Exception\\InvalidAgentDeclarationException' => ApiStatus::Experimental,
        'Kongono\\AgentDeclarationBuild\\Exception\\StaleAgentDeclarationArtifactException' => ApiStatus::Experimental,
        'Kongono\\AgentDeclarationBuild\\Internal\\CanonicalJson' => ApiStatus::Internal,
        'Kongono\\AgentDeclarationBuild\\Internal\\PathGuard' => ApiStatus::Internal,
        'Kongono\\AgentPrincipalContract\\Capability\\CapabilityIdentity' => ApiStatus::Experimental,
        'Kongono\\AgentPrincipalContract\\Capability\\CapabilityPurpose' => ApiStatus::Experimental,
        'Kongono\\AgentPrincipalContract\\Capability\\CapabilityRequest' => ApiStatus::Experimental,
        'Kongono\\AgentPrincipalContract\\Capability\\CapabilityRequirement' => ApiStatus::Experimental,
        'Kongono\\AgentPrincipalContract\\Constraint\\AgentConstraint' => ApiStatus::Experimental,
        'Kongono\\AgentPrincipalContract\\Constraint\\ConstraintKind' => ApiStatus::Experimental,
        'Kongono\\AgentPrincipalContract\\ContractVersion' => ApiStatus::Experimental,
        'Kongono\\AgentPrincipalContract\\Diagnostic\\AgentPrincipalContractDiagnostic' => ApiStatus::Internal,
        'Kongono\\AgentPrincipalContract\\Evidence\\EvidenceIdentity' => ApiStatus::Experimental,
        'Kongono\\AgentPrincipalContract\\Evidence\\EvidenceKind' => ApiStatus::Experimental,
        'Kongono\\AgentPrincipalContract\\Evidence\\EvidenceReference' => ApiStatus::Experimental,
        'Kongono\\AgentPrincipalContract\\Exception\\AgentPrincipalContractException' => ApiStatus::Internal,
        'Kongono\\AgentPrincipalContract\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\AgentPrincipalContract\\Exception\\InvalidAgentPrincipalContractException' => ApiStatus::Experimental,
        'Kongono\\AgentPrincipalContract\\Identity\\AgentPrincipalReference' => ApiStatus::Experimental,
        'Kongono\\AgentPrincipalContract\\Identity\\OperationReference' => ApiStatus::Experimental,
        'Kongono\\AgentPrincipalContract\\Intent\\AgentIntent' => ApiStatus::Experimental,
        'Kongono\\AgentPrincipalContract\\Intent\\IntentIdentity' => ApiStatus::Experimental,
        'Kongono\\AgentPrincipalContract\\Intent\\IntentPurpose' => ApiStatus::Experimental,
        'Kongono\\AgentPrincipalContract\\Internal\\ContractGuard' => ApiStatus::Internal,
        'Kongono\\AgentPrincipalContract\\Proposal\\ActionProposal' => ApiStatus::Experimental,
        'Kongono\\AgentPrincipalContract\\Proposal\\ProposalIdentity' => ApiStatus::Experimental,
        'Kongono\\AgentPrincipalContract\\Provenance\\AgentProvenance' => ApiStatus::Experimental,
        'Kongono\\AgentPrincipalContract\\Provenance\\ProvenanceSource' => ApiStatus::Experimental,
        'Kongono\\AgentPrincipalContract\\Receipt\\AgentReceipt' => ApiStatus::Experimental,
        'Kongono\\AgentPrincipalContract\\Receipt\\ReceiptIdentity' => ApiStatus::Experimental,
        'Kongono\\AgentPrincipalContract\\Receipt\\ReceiptOutcome' => ApiStatus::Experimental,
        'Kongono\\AgentPrincipalContract\\Visibility\\AgentVisibility' => ApiStatus::Experimental,
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
        'Kongono\\ApplicationGraphArtifact\\Artifact\\ApplicationGraphArtifact' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraphArtifact\\Artifact\\ApplicationGraphArtifactBuilder' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraphArtifact\\Artifact\\ApplicationGraphArtifactLoader' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraphArtifact\\Artifact\\ApplicationGraphArtifactPublisher' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraphArtifact\\Artifact\\ApplicationGraphArtifactValidator' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraphArtifact\\Diagnostic\\ApplicationGraphArtifactDiagnostic' => ApiStatus::Internal,
        'Kongono\\ApplicationGraphArtifact\\Exception\\ApplicationGraphArtifactException' => ApiStatus::Internal,
        'Kongono\\ApplicationGraphArtifact\\Exception\\ApplicationGraphArtifactLoadException' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraphArtifact\\Exception\\ApplicationGraphArtifactPublicationException' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraphArtifact\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\ApplicationGraphArtifact\\Exception\\InvalidApplicationGraphArtifactException' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraphArtifact\\Exception\\StaleApplicationGraphArtifactException' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraphArtifact\\Identity\\GraphIdentity' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraphArtifact\\Inspection\\ApplicationGraphArtifactInspection' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraphArtifact\\Internal\\CanonicalJson' => ApiStatus::Internal,
        'Kongono\\ApplicationGraphArtifact\\Internal\\GraphArtifactCodec' => ApiStatus::Internal,
        'Kongono\\ApplicationGraphArtifact\\Internal\\PathGuard' => ApiStatus::Internal,
        'Kongono\\ApplicationGraphArtifact\\Internal\\StrictJsonDecoder' => ApiStatus::Internal,
        'Kongono\\ApplicationGraphBuild\\Build\\ApplicationGraphBuilder' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraphBuild\\Diagnostic\\GraphBuildDiagnostic' => ApiStatus::Internal,
        'Kongono\\ApplicationGraphBuild\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\ApplicationGraphBuild\\Exception\\GraphBuildException' => ApiStatus::Internal,
        'Kongono\\ApplicationGraphBuild\\Exception\\InvalidGraphBuildException' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraphBuild\\Graph\\AuthoritativeApplicationGraph' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraphBuild\\Graph\\GraphField' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraphBuild\\Graph\\GraphNode' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraphBuild\\Graph\\GraphRelation' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraphBuild\\Internal\\GraphConstructionValidator' => ApiStatus::Internal,
        'Kongono\\ApplicationGraphBuild\\Ir\\GraphFieldRecord' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraphBuild\\Ir\\GraphNodeRecord' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraphBuild\\Ir\\GraphRelationRecord' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraphComposition\\Build\\CompositionApplicationGraphBuilder' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraphComposition\\Diagnostic\\CompositionGraphDiagnostic' => ApiStatus::Internal,
        'Kongono\\ApplicationGraphComposition\\Exception\\CompositionGraphException' => ApiStatus::Internal,
        'Kongono\\ApplicationGraphComposition\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\ApplicationGraphComposition\\Exception\\InvalidCompositionGraphProjectionException' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraphComposition\\Internal\\CanonicalJson' => ApiStatus::Internal,
        'Kongono\\ApplicationGraphComposition\\Internal\\CompositionArtifactGuard' => ApiStatus::Internal,
        'Kongono\\ApplicationGraphComposition\\Internal\\CompositionModelProjector' => ApiStatus::Internal,
        'Kongono\\ApplicationGraphComposition\\Internal\\FramedIdentityDeriver' => ApiStatus::Internal,
        'Kongono\\ApplicationGraphComposition\\Projection\\CompositionIrProjection' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraphComposition\\Projection\\CompositionIrProjector' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraphContract\\ContractVersion' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraphContract\\Diagnostic\\GraphContractDiagnostic' => ApiStatus::Internal,
        'Kongono\\ApplicationGraphContract\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\ApplicationGraphContract\\Exception\\GraphContractException' => ApiStatus::Internal,
        'Kongono\\ApplicationGraphContract\\Exception\\InvalidGraphContractException' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraphContract\\Identity\\NodeIdentity' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraphContract\\Identity\\ProducerIdentity' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraphContract\\Identity\\RelationIdentity' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraphContract\\Identity\\SourceIdentity' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraphContract\\Internal\\IdentityGuard' => ApiStatus::Internal,
        'Kongono\\ApplicationGraphContract\\Provenance\\DerivationVersion' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraphContract\\Provenance\\GraphProvenance' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraphContract\\Provenance\\ProducerContract' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraphContract\\Schema\\FactVisibility' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraphContract\\Schema\\FieldDisclosure' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraphContract\\Schema\\FieldName' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraphContract\\Schema\\FieldSchema' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraphContract\\Schema\\FieldType' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraphContract\\Schema\\NodeSchema' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraphContract\\Schema\\RelationSchema' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraphContract\\Schema\\SchemaCatalog' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraphContract\\Vocabulary\\NodeType' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraphContract\\Vocabulary\\RelationType' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraphIntelligence\\Derivation\\ApplicationGraphIntelligenceDeriver' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraphIntelligence\\Derivation\\ApplicationGraphIntelligenceView' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraphIntelligence\\Diagnostic\\ApplicationGraphIntelligenceDiagnostic' => ApiStatus::Internal,
        'Kongono\\ApplicationGraphIntelligence\\Exception\\ApplicationGraphIntelligenceException' => ApiStatus::Internal,
        'Kongono\\ApplicationGraphIntelligence\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\ApplicationGraphIntelligence\\Exception\\InvalidApplicationGraphIntelligenceValueException' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraphIntelligence\\Role\\DeveloperIntelligenceRole' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraphIntelligence\\Verification\\ApplicationGraphIntelligenceVerdict' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraphIntelligence\\Verification\\ApplicationGraphIntelligenceVerification' => ApiStatus::Experimental,
        'Kongono\\ApplicationGraphIntelligence\\Verification\\ApplicationGraphIntelligenceVerifier' => ApiStatus::Experimental,
        'Kongono\\ExtensionAdmission\\Admission\\AdmittedExtension' => ApiStatus::Experimental,
        'Kongono\\ExtensionAdmission\\Admission\\AdmittedExtensionGraphInput' => ApiStatus::Experimental,
        'Kongono\\ExtensionAdmission\\Admission\\ExtensionAdmissionBuilder' => ApiStatus::Experimental,
        'Kongono\\ExtensionAdmission\\Conflict\\ContributionConflictResolution' => ApiStatus::Experimental,
        'Kongono\\ExtensionAdmission\\Contribution\\ExtensionGraphContribution' => ApiStatus::Experimental,
        'Kongono\\ExtensionAdmission\\Diagnostic\\ExtensionAdmissionDiagnostic' => ApiStatus::Internal,
        'Kongono\\ExtensionAdmission\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\ExtensionAdmission\\Exception\\ExtensionAdmissionException' => ApiStatus::Experimental,
        'Kongono\\ExtensionAdmission\\Exception\\InvalidExtensionAdmissionException' => ApiStatus::Experimental,
        'Kongono\\ExtensionAdmission\\Exception\\UnresolvedExtensionConflictException' => ApiStatus::Experimental,
        'Kongono\\ExtensionAdmission\\Identity\\AdmissionIdentity' => ApiStatus::Experimental,
        'Kongono\\ExtensionAdmission\\Internal\\AdmissionGuard' => ApiStatus::Internal,
        'Kongono\\ExtensionAdmission\\Internal\\AdmissionProjector' => ApiStatus::Internal,
        'Kongono\\ExtensionAdmission\\Internal\\CanonicalAdmission' => ApiStatus::Internal,
        'Kongono\\ExtensionAdmission\\Internal\\GraphContributionValidator' => ApiStatus::Internal,
        'Kongono\\ExtensionAdmission\\Projection\\ExtensionGraphProvenance' => ApiStatus::Experimental,
        'Kongono\\ExtensionAdmission\\Selection\\ExtensionAdmissionPlan' => ApiStatus::Experimental,
        'Kongono\\ExtensionAdmission\\Selection\\ExtensionSelection' => ApiStatus::Experimental,
        'Kongono\\ExtensionBuild\\ArtifactVersion' => ApiStatus::Experimental,
        'Kongono\\ExtensionBuild\\Artifact\\ExtensionDeclarationArtifact' => ApiStatus::Experimental,
        'Kongono\\ExtensionBuild\\Artifact\\ExtensionDeclarationArtifactBuilder' => ApiStatus::Experimental,
        'Kongono\\ExtensionBuild\\Artifact\\ExtensionDeclarationArtifactPublisher' => ApiStatus::Experimental,
        'Kongono\\ExtensionBuild\\Artifact\\ExtensionDeclarationArtifactValidator' => ApiStatus::Experimental,
        'Kongono\\ExtensionBuild\\Diagnostic\\ExtensionBuildDiagnostic' => ApiStatus::Internal,
        'Kongono\\ExtensionBuild\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\ExtensionBuild\\Exception\\ExtensionBuildException' => ApiStatus::Internal,
        'Kongono\\ExtensionBuild\\Exception\\ExtensionDeclarationPublicationException' => ApiStatus::Experimental,
        'Kongono\\ExtensionBuild\\Exception\\InvalidExtensionBuildException' => ApiStatus::Experimental,
        'Kongono\\ExtensionBuild\\Exception\\StaleExtensionDeclarationArtifactException' => ApiStatus::Experimental,
        'Kongono\\ExtensionBuild\\Identity\\DeclarationSetIdentity' => ApiStatus::Experimental,
        'Kongono\\ExtensionBuild\\Internal\\CanonicalJson' => ApiStatus::Internal,
        'Kongono\\ExtensionBuild\\Internal\\DeclarationArtifactCodec' => ApiStatus::Internal,
        'Kongono\\ExtensionBuild\\Internal\\PathGuard' => ApiStatus::Internal,
        'Kongono\\ExtensionComposition\\Artifact\\GeneratedExtensionWiringArtifact' => ApiStatus::Experimental,
        'Kongono\\ExtensionComposition\\Diagnostic\\ExtensionCompositionDiagnostic' => ApiStatus::Internal,
        'Kongono\\ExtensionComposition\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\ExtensionComposition\\Exception\\ExtensionCompositionException' => ApiStatus::Experimental,
        'Kongono\\ExtensionComposition\\Exception\\ExtensionWiringPublicationException' => ApiStatus::Experimental,
        'Kongono\\ExtensionComposition\\Exception\\InvalidExtensionGrantPlanException' => ApiStatus::Experimental,
        'Kongono\\ExtensionComposition\\Exception\\InvalidGeneratedExtensionWiringException' => ApiStatus::Experimental,
        'Kongono\\ExtensionComposition\\Generated\\ExtensionWiringGenerator' => ApiStatus::Experimental,
        'Kongono\\ExtensionComposition\\Generated\\ExtensionWiringPublisher' => ApiStatus::Experimental,
        'Kongono\\ExtensionComposition\\Generated\\ExtensionWiringValidator' => ApiStatus::Experimental,
        'Kongono\\ExtensionComposition\\Grant\\CapabilityDecision' => ApiStatus::Experimental,
        'Kongono\\ExtensionComposition\\Grant\\CapabilityDisposition' => ApiStatus::Experimental,
        'Kongono\\ExtensionComposition\\Grant\\CapabilityProviderLifetime' => ApiStatus::Experimental,
        'Kongono\\ExtensionComposition\\Identity\\GrantPlanIdentity' => ApiStatus::Experimental,
        'Kongono\\ExtensionComposition\\Implementation\\TrustedExtensionImplementation' => ApiStatus::Experimental,
        'Kongono\\ExtensionComposition\\Internal\\CanonicalJson' => ApiStatus::Internal,
        'Kongono\\ExtensionComposition\\Internal\\ExtensionGrantPlanValidator' => ApiStatus::Internal,
        'Kongono\\ExtensionComposition\\Internal\\GeneratedExtensionWiringEmitter' => ApiStatus::Internal,
        'Kongono\\ExtensionComposition\\Internal\\GeneratedExtensionWiringImplementation' => ApiStatus::Internal,
        'Kongono\\ExtensionComposition\\Internal\\PathGuard' => ApiStatus::Internal,
        'Kongono\\ExtensionComposition\\Plan\\ExtensionGrantPlan' => ApiStatus::Experimental,
        'Kongono\\ExtensionComposition\\Runtime\\GeneratedExtensionApplicationBoundary' => ApiStatus::Experimental,
        'Kongono\\ExtensionComposition\\Runtime\\GeneratedExtensionOperationBoundary' => ApiStatus::Experimental,
        'Kongono\\ExtensionContract\\Capability\\CapabilityIdentity' => ApiStatus::Experimental,
        'Kongono\\ExtensionContract\\Capability\\CapabilityPurpose' => ApiStatus::Experimental,
        'Kongono\\ExtensionContract\\Capability\\CapabilityRequest' => ApiStatus::Experimental,
        'Kongono\\ExtensionContract\\Capability\\CapabilityRequirement' => ApiStatus::Experimental,
        'Kongono\\ExtensionContract\\ContractVersion' => ApiStatus::Experimental,
        'Kongono\\ExtensionContract\\Contribution\\ContributionDeclaration' => ApiStatus::Experimental,
        'Kongono\\ExtensionContract\\Contribution\\ContributionIdentity' => ApiStatus::Experimental,
        'Kongono\\ExtensionContract\\Contribution\\ContributionSchema' => ApiStatus::Experimental,
        'Kongono\\ExtensionContract\\Contribution\\ContributionVisibility' => ApiStatus::Experimental,
        'Kongono\\ExtensionContract\\Declaration\\ExtensionDeclaration' => ApiStatus::Experimental,
        'Kongono\\ExtensionContract\\Diagnostic\\ExtensionContractDiagnostic' => ApiStatus::Internal,
        'Kongono\\ExtensionContract\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\ExtensionContract\\Exception\\ExtensionContractException' => ApiStatus::Internal,
        'Kongono\\ExtensionContract\\Exception\\InvalidExtensionContractException' => ApiStatus::Experimental,
        'Kongono\\ExtensionContract\\ExtensionCategory' => ApiStatus::Experimental,
        'Kongono\\ExtensionContract\\Identity\\ExtensionIdentity' => ApiStatus::Experimental,
        'Kongono\\ExtensionContract\\Internal\\ContractGuard' => ApiStatus::Internal,
        'Kongono\\ExtensionContract\\Lifecycle\\ExtensionLifecycle' => ApiStatus::Experimental,
        'Kongono\\ExtensionContract\\Provenance\\ExtensionProvenance' => ApiStatus::Experimental,
        'Kongono\\ExtensionContract\\Provenance\\ExtensionSourceIdentity' => ApiStatus::Experimental,
        'Kongono\\ExtensionContract\\Version\\ExtensionVersion' => ApiStatus::Experimental,
        'Kongono\\ExtensionHost\\Contract\\ApplicationExtensionInterface' => ApiStatus::Experimental,
        'Kongono\\ExtensionHost\\Contract\\OperationExtensionInterface' => ApiStatus::Experimental,
        'Kongono\\ExtensionHost\\Diagnostic\\ExtensionHostDiagnostic' => ApiStatus::Internal,
        'Kongono\\ExtensionHost\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\ExtensionHost\\Exception\\ExtensionHostException' => ApiStatus::Experimental,
        'Kongono\\ExtensionHost\\Exception\\ExtensionHostLifecycleException' => ApiStatus::Experimental,
        'Kongono\\ExtensionHost\\Exception\\ExtensionHostUnavailableException' => ApiStatus::Experimental,
        'Kongono\\ExtensionHost\\Exception\\InvalidGeneratedExtensionHostException' => ApiStatus::Experimental,
        'Kongono\\ExtensionHost\\Internal\\LifecycleFailureComposer' => ApiStatus::Internal,
        'Kongono\\ExtensionHost\\Internal\\PathGuard' => ApiStatus::Internal,
        'Kongono\\ExtensionHost\\Runtime\\ExtensionHostLoader' => ApiStatus::Experimental,
        'Kongono\\ExtensionHost\\Runtime\\ExtensionHostState' => ApiStatus::Experimental,
        'Kongono\\ExtensionHost\\Runtime\\LoadedExtensionWiring' => ApiStatus::Experimental,
        'Kongono\\ExtensionHost\\Runtime\\TrustedExtensionHost' => ApiStatus::Experimental,
        'Kongono\\ExtensionHost\\Runtime\\TrustedExtensionOperation' => ApiStatus::Experimental,
        'Kongono\\ConsoleApplication\\Handler\\ExtensionInspectCommandHandler' => ApiStatus::Experimental,
        'Kongono\\ExtensionInspection\\Derivation\\ExtensionIntelligenceDeriver' => ApiStatus::Experimental,
        'Kongono\\ExtensionInspection\\Derivation\\ExtensionIntelligenceView' => ApiStatus::Experimental,
        'Kongono\\ExtensionInspection\\Diagnostic\\ExtensionInspectionDiagnostic' => ApiStatus::Internal,
        'Kongono\\ExtensionInspection\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\ExtensionInspection\\Exception\\ExtensionInspectionException' => ApiStatus::Internal,
        'Kongono\\ExtensionInspection\\Exception\\ExtensionInspectionLoadException' => ApiStatus::Experimental,
        'Kongono\\ExtensionInspection\\Exception\\InvalidExtensionInspectionValueException' => ApiStatus::Experimental,
        'Kongono\\ExtensionInspection\\Inspection\\ExtensionPlatformInspection' => ApiStatus::Experimental,
        'Kongono\\ExtensionInspection\\Inspection\\ExtensionPlatformInspector' => ApiStatus::Experimental,
        'Kongono\\ExtensionInspection\\Internal\\CanonicalJson' => ApiStatus::Internal,
        'Kongono\\ExtensionInspection\\Internal\\PathGuard' => ApiStatus::Internal,
        'Kongono\\ExtensionInspection\\Loading\\ExtensionInspectionLoader' => ApiStatus::Experimental,
        'Kongono\\ExtensionInspection\\Role\\DeveloperIntelligenceRole' => ApiStatus::Experimental,
        'Kongono\\ExtensionInspection\\Verification\\ExtensionIntelligenceVerdict' => ApiStatus::Experimental,
        'Kongono\\ExtensionInspection\\Verification\\ExtensionIntelligenceVerification' => ApiStatus::Experimental,
        'Kongono\\ExtensionInspection\\Verification\\ExtensionIntelligenceVerifier' => ApiStatus::Experimental,
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
        'Kongono\\Composition\\Workflow\\PlannedApplicationBuild' => ApiStatus::Experimental,
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
        'Kongono\\Composition\\Exception\\InvalidGeneratedOperationInputsException' => ApiStatus::Experimental,
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
        'Kongono\\Composition\\Runtime\\GeneratedOperationInputApplicationBoundary' => ApiStatus::Experimental,
        'Kongono\\Composition\\Runtime\\GeneratedOperationInputs' => ApiStatus::Experimental,
        GeneratedWiringLoader::class => ApiStatus::Experimental,
        LoadedGeneratedWiring::class => ApiStatus::Experimental,
        'Kongono\\Console\\CommandCancellation' => ApiStatus::Experimental,
        'Kongono\\Console\\CommandCleanup' => ApiStatus::Experimental,
        'Kongono\\Console\\CommandDefinition' => ApiStatus::Experimental,
        'Kongono\\Console\\CommandExecutionIntent' => ApiStatus::Experimental,
        'Kongono\\Console\\CommandFailure' => ApiStatus::Experimental,
        'Kongono\\Console\\CommandFailureCategory' => ApiStatus::Experimental,
        'Kongono\\Console\\CommandHandler' => ApiStatus::Experimental,
        'Kongono\\Console\\CommandIdentifier' => ApiStatus::Experimental,
        'Kongono\\Console\\CommandInput' => ApiStatus::Experimental,
        'Kongono\\Console\\CommandInputDefinition' => ApiStatus::Experimental,
        'Kongono\\Console\\CommandInputName' => ApiStatus::Experimental,
        'Kongono\\Console\\CommandInputRequirement' => ApiStatus::Experimental,
        'Kongono\\Console\\CommandInputSensitivity' => ApiStatus::Experimental,
        'Kongono\\Console\\CommandInputValue' => ApiStatus::Experimental,
        'Kongono\\Console\\CommandInteractionMode' => ApiStatus::Experimental,
        'Kongono\\Console\\CommandInteractionPolicy' => ApiStatus::Experimental,
        'Kongono\\Console\\CommandInvocation' => ApiStatus::Experimental,
        'Kongono\\Console\\CommandLifecycleState' => ApiStatus::Experimental,
        'Kongono\\Console\\CommandMutationPolicy' => ApiStatus::Experimental,
        'Kongono\\Console\\CommandOperation' => ApiStatus::Experimental,
        'Kongono\\Console\\CommandOutcome' => ApiStatus::Experimental,
        'Kongono\\Console\\CommandParseResult' => ApiStatus::Experimental,
        'Kongono\\Console\\CommandResult' => ApiStatus::Experimental,
        'Kongono\\Console\\CommandResultData' => ApiStatus::Experimental,
        'Kongono\\Console\\CommandResultField' => ApiStatus::Experimental,
        'Kongono\\Console\\CommandResultFieldDisclosure' => ApiStatus::Experimental,
        'Kongono\\Console\\CommandResultFieldLabel' => ApiStatus::Experimental,
        'Kongono\\Console\\CommandResultFieldName' => ApiStatus::Experimental,
        'Kongono\\Console\\CommandResultRecord' => ApiStatus::Experimental,
        'Kongono\\Console\\CommandResultValue' => ApiStatus::Experimental,
        'Kongono\\Console\\CommandResultValueKind' => ApiStatus::Experimental,
        'Kongono\\Console\\CommandSpecification' => ApiStatus::Experimental,
        'Kongono\\Console\\CommandSpecificationSet' => ApiStatus::Experimental,
        'Kongono\\Console\\CommandSummary' => ApiStatus::Experimental,
        'Kongono\\Console\\CommandTokenParser' => ApiStatus::Experimental,
        'Kongono\\Console\\Diagnostic\\ConsoleDiagnostic' => ApiStatus::Internal,
        'Kongono\\Console\\Exception\\ConsoleException' => ApiStatus::Internal,
        'Kongono\\Console\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\Console\\Exception\\InvalidConsoleValueException' => ApiStatus::Experimental,
        'Kongono\\ConsoleApplication\\Diagnostic\\ConsoleApplicationDiagnostic' => ApiStatus::Internal,
        'Kongono\\ConsoleApplication\\Exception\\ConsoleApplicationException' => ApiStatus::Internal,
        'Kongono\\ConsoleApplication\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\ConsoleApplication\\Exception\\InvalidConsoleApplicationValueException' => ApiStatus::Experimental,
        'Kongono\\ConsoleApplication\\Handler\\ApplicationBuildCommandHandler' => ApiStatus::Experimental,
        'Kongono\\ConsoleApplication\\Handler\\ApplicationGraphInspectCommandHandler' => ApiStatus::Experimental,
        'Kongono\\ConsoleApplication\\Handler\\ObservabilityCheckCommandHandler' => ApiStatus::Experimental,
        'Kongono\\ConsoleApplication\\Handler\\ObservabilityInspectCommandHandler' => ApiStatus::Experimental,
        'Kongono\\ConsoleApplication\\Handler\\SecurityCheckCommandHandler' => ApiStatus::Experimental,
        'Kongono\\ConsoleApplication\\Handler\\SecurityInspectCommandHandler' => ApiStatus::Experimental,
        'Kongono\\ConsoleApplication\\Internal\\KongoCommandValidator' => ApiStatus::Internal,
        'Kongono\\ConsoleApplication\\KongoApplication' => ApiStatus::Experimental,
        'Kongono\\ConsoleApplication\\KongoApplicationFactory' => ApiStatus::Experimental,
        'Kongono\\ConsoleApplication\\KongoCommand' => ApiStatus::Experimental,
        'Kongono\\ConsoleApplication\\KongoProcessRequest' => ApiStatus::Experimental,
        'Kongono\\ConsoleApplication\\Process\\NativeKongoEntrypoint' => ApiStatus::Experimental,
        'Kongono\\ConsoleApplication\\Process\\NativeStreamOutputPort' => ApiStatus::Experimental,
        'Kongono\\ConsoleApplication\\Process\\NeverCancelled' => ApiStatus::Experimental,
        'Kongono\\ConsoleBuild\\Build\\ConsoleArtifact' => ApiStatus::Experimental,
        'Kongono\\ConsoleBuild\\Build\\ConsoleArtifactBuilder' => ApiStatus::Experimental,
        'Kongono\\ConsoleBuild\\Build\\ConsoleArtifactValidator' => ApiStatus::Experimental,
        'Kongono\\ConsoleBuild\\Diagnostic\\ConsoleBuildDiagnostic' => ApiStatus::Internal,
        'Kongono\\ConsoleBuild\\Exception\\ConsoleBuildException' => ApiStatus::Internal,
        'Kongono\\ConsoleBuild\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\ConsoleBuild\\Exception\\InvalidConsoleArtifactException' => ApiStatus::Experimental,
        'Kongono\\ConsoleBuild\\Exception\\InvalidConsoleSourceException' => ApiStatus::Experimental,
        'Kongono\\ConsoleBuild\\Exception\\StaleConsoleArtifactException' => ApiStatus::Experimental,
        'Kongono\\ConsoleBuild\\Internal\\CanonicalJson' => ApiStatus::Internal,
        'Kongono\\ConsoleBuild\\Internal\\ConsoleBuildFailureCategory' => ApiStatus::Internal,
        'Kongono\\ConsoleBuild\\Internal\\ConsoleBuildImplementation' => ApiStatus::Internal,
        'Kongono\\ConsoleBuild\\Internal\\ConsoleModelCompiler' => ApiStatus::Internal,
        'Kongono\\ConsoleBuild\\Internal\\PathGuard' => ApiStatus::Internal,
        'Kongono\\ConsoleBuild\\Internal\\StrictJsonDecoder' => ApiStatus::Internal,
        'Kongono\\ConsoleBuild\\Model\\ConsoleModel' => ApiStatus::Experimental,
        'Kongono\\ConsoleCommands\\Diagnostic\\ConsoleCommandsDiagnostic' => ApiStatus::Internal,
        'Kongono\\ConsoleCommands\\Exception\\ConsoleCommandsException' => ApiStatus::Internal,
        'Kongono\\ConsoleCommands\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\ConsoleCommands\\Exception\\InvalidConsoleCommandsValueException' => ApiStatus::Experimental,
        'Kongono\\ConsoleCommands\\Handler\\CompositionInspectCommandHandler' => ApiStatus::Experimental,
        'Kongono\\ConsoleCommands\\Handler\\GraphInspectCommandHandler' => ApiStatus::Experimental,
        'Kongono\\ConsoleCommands\\Handler\\QualityInspectCommandHandler' => ApiStatus::Experimental,
        'Kongono\\ConsoleCommands\\Internal\\ReadOnlyCommandFamilyValidator' => ApiStatus::Internal,
        'Kongono\\ConsoleCommands\\ReadOnlyFrameworkCommand' => ApiStatus::Experimental,
        'Kongono\\ConsoleCommands\\ReadOnlyFrameworkCommandFamily' => ApiStatus::Experimental,
        'Kongono\\ConsolePresentation\\CommandExitStatus' => ApiStatus::Experimental,
        'Kongono\\ConsolePresentation\\CommandMediaType' => ApiStatus::Experimental,
        'Kongono\\ConsolePresentation\\CommandOutputPort' => ApiStatus::Experimental,
        'Kongono\\ConsolePresentation\\CommandPresentation' => ApiStatus::Experimental,
        'Kongono\\ConsolePresentation\\CommandPresentationAdapter' => ApiStatus::Experimental,
        'Kongono\\ConsolePresentation\\HumanCommandPresenter' => ApiStatus::Experimental,
        'Kongono\\ConsolePresentation\\MachineCommandPresenter' => ApiStatus::Experimental,
        'Kongono\\ConsolePresentation\\Diagnostic\\ConsolePresentationDiagnostic' => ApiStatus::Internal,
        'Kongono\\ConsolePresentation\\Exception\\CommandOutputException' => ApiStatus::Public,
        'Kongono\\ConsolePresentation\\Exception\\ConsolePresentationException' => ApiStatus::Internal,
        'Kongono\\ConsolePresentation\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\ConsolePresentation\\Exception\\InvalidConsolePresentationException' => ApiStatus::Experimental,
        'Kongono\\ConsolePresentation\\Internal\\CanonicalJson' => ApiStatus::Internal,
        'Kongono\\ConsolePresentation\\Internal\\CommandHelpProjection' => ApiStatus::Internal,
        'Kongono\\ConsolePresentation\\Internal\\CommandResultProjection' => ApiStatus::Internal,
        'Kongono\\ConsolePresentation\\Internal\\HumanValueFormatter' => ApiStatus::Internal,
        'Kongono\\ConsoleRuntime\\CommandDispatcher' => ApiStatus::Experimental,
        'Kongono\\ConsoleRuntime\\CommandExecutionOperation' => ApiStatus::Experimental,
        'Kongono\\ConsoleRuntime\\CommandHandlerBinding' => ApiStatus::Experimental,
        'Kongono\\ConsoleRuntime\\CommandHandlerSet' => ApiStatus::Experimental,
        'Kongono\\ConsoleRuntime\\Diagnostic\\ConsoleRuntimeDiagnostic' => ApiStatus::Internal,
        'Kongono\\ConsoleRuntime\\Exception\\CommandExecutionException' => ApiStatus::Public,
        'Kongono\\ConsoleRuntime\\Exception\\ConsoleRuntimeException' => ApiStatus::Internal,
        'Kongono\\ConsoleRuntime\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\ConsoleRuntime\\Exception\\InvalidCommandRuntimeValueException' => ApiStatus::Experimental,
        'Kongono\\Database\\CommandResult' => ApiStatus::Experimental,
        'Kongono\\Database\\DatabaseCapabilities' => ApiStatus::Experimental,
        'Kongono\\Database\\DatabaseCapability' => ApiStatus::Experimental,
        'Kongono\\Database\\DatabaseColumn' => ApiStatus::Experimental,
        'Kongono\\Database\\DatabaseConnector' => ApiStatus::Public,
        'Kongono\\Database\\DatabaseExecutor' => ApiStatus::Public,
        'Kongono\\Database\\DatabaseParameter' => ApiStatus::Experimental,
        'Kongono\\Database\\DatabaseRow' => ApiStatus::Experimental,
        'Kongono\\Database\\DatabaseSession' => ApiStatus::Public,
        'Kongono\\Database\\DatabaseStatement' => ApiStatus::Experimental,
        'Kongono\\Database\\DatabaseTransaction' => ApiStatus::Public,
        'Kongono\\Database\\DatabaseValue' => ApiStatus::Experimental,
        'Kongono\\Database\\DatabaseValueKind' => ApiStatus::Experimental,
        'Kongono\\Database\\ResultExpectation' => ApiStatus::Experimental,
        'Kongono\\Database\\RowStream' => ApiStatus::Public,
        'Kongono\\Database\\TransactionAccess' => ApiStatus::Experimental,
        'Kongono\\Database\\TransactionIsolation' => ApiStatus::Experimental,
        'Kongono\\Database\\TransactionOptions' => ApiStatus::Experimental,
        'Kongono\\Database\\TransactionState' => ApiStatus::Experimental,
        'Kongono\\Database\\Diagnostic\\DatabaseDiagnostic' => ApiStatus::Internal,
        'Kongono\\Database\\Exception\\CombinedDatabaseFailureException' => ApiStatus::Experimental,
        'Kongono\\Database\\Exception\\DatabaseBindingException' => ApiStatus::Experimental,
        'Kongono\\Database\\Exception\\DatabaseCleanupException' => ApiStatus::Experimental,
        'Kongono\\Database\\Exception\\DatabaseConnectionException' => ApiStatus::Experimental,
        'Kongono\\Database\\Exception\\DatabaseException' => ApiStatus::Internal,
        'Kongono\\Database\\Exception\\DatabaseExecutionException' => ApiStatus::Experimental,
        'Kongono\\Database\\Exception\\DatabaseResultException' => ApiStatus::Experimental,
        'Kongono\\Database\\Exception\\DatabaseTransactionBeginException' => ApiStatus::Experimental,
        'Kongono\\Database\\Exception\\DatabaseTransactionCommitException' => ApiStatus::Experimental,
        'Kongono\\Database\\Exception\\DatabaseTransactionRollbackException' => ApiStatus::Experimental,
        'Kongono\\Database\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\Database\\Exception\\InvalidDatabaseStateException' => ApiStatus::Experimental,
        'Kongono\\Database\\Exception\\InvalidDatabaseValueException' => ApiStatus::Experimental,
        'Kongono\\Database\\Exception\\UnsupportedDatabaseCapabilityException' => ApiStatus::Experimental,
        'Kongono\\Persistence\\AggregateCascadeExecutor' => ApiStatus::Experimental,
        'Kongono\\Persistence\\AggregateCascadePlan' => ApiStatus::Experimental,
        'Kongono\\Persistence\\AggregateLoadExecutor' => ApiStatus::Experimental,
        'Kongono\\Persistence\\AggregateLoadPlan' => ApiStatus::Experimental,
        'Kongono\\Persistence\\AggregateRepository' => ApiStatus::Experimental,
        'Kongono\\Persistence\\CascadeAction' => ApiStatus::Experimental,
        'Kongono\\Persistence\\ChangeKind' => ApiStatus::Experimental,
        'Kongono\\Persistence\\ChangeExecutionOutcome' => ApiStatus::Experimental,
        'Kongono\\Persistence\\ChangeSet' => ApiStatus::Experimental,
        'Kongono\\Persistence\\ConcurrencyToken' => ApiStatus::Experimental,
        'Kongono\\Persistence\\ContextState' => ApiStatus::Experimental,
        'Kongono\\Persistence\\Diagnostic\\PersistenceDiagnostic' => ApiStatus::Internal,
        'Kongono\\Persistence\\EntityIdentity' => ApiStatus::Experimental,
        'Kongono\\Persistence\\EntityState' => ApiStatus::Experimental,
        'Kongono\\Persistence\\Exception\\CombinedPersistenceFailureException' => ApiStatus::Experimental,
        'Kongono\\Persistence\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\Persistence\\Exception\\InvalidAggregatePersistenceException' => ApiStatus::Experimental,
        'Kongono\\Persistence\\Exception\\InvalidPersistenceStateException' => ApiStatus::Experimental,
        'Kongono\\Persistence\\Exception\\InvalidPersistenceValueException' => ApiStatus::Experimental,
        'Kongono\\Persistence\\Exception\\PersistenceCleanupException' => ApiStatus::Experimental,
        'Kongono\\Persistence\\Exception\\PersistenceException' => ApiStatus::Internal,
        'Kongono\\Persistence\\Exception\\PersistenceOperationException' => ApiStatus::Experimental,
        'Kongono\\Persistence\\Exception\\PersistenceUncertainStateException' => ApiStatus::Experimental,
        'Kongono\\Persistence\\FlushOutcome' => ApiStatus::Experimental,
        'Kongono\\Persistence\\FlushResult' => ApiStatus::Experimental,
        'Kongono\\Persistence\\Internal\\TrackedEntity' => ApiStatus::Internal,
        'Kongono\\Persistence\\LoadedAggregate' => ApiStatus::Experimental,
        'Kongono\\Persistence\\OperationPersistenceContext' => ApiStatus::Experimental,
        'Kongono\\Persistence\\PersistenceChange' => ApiStatus::Experimental,
        'Kongono\\Persistence\\PersistenceChangeExecutor' => ApiStatus::Experimental,
        'Kongono\\Persistence\\PersistenceContext' => ApiStatus::Experimental,
        'Kongono\\Persistence\\PersistenceStateAdapter' => ApiStatus::Experimental,
        'Kongono\\Persistence\\PersistenceUnitOfWork' => ApiStatus::Experimental,
        'Kongono\\Persistence\\RelationshipCardinality' => ApiStatus::Experimental,
        'Kongono\\Persistence\\RelationshipCascadePolicy' => ApiStatus::Experimental,
        'Kongono\\Persistence\\RelationshipLoad' => ApiStatus::Experimental,
        'Kongono\\PersistenceBuild\\Build\\PersistenceArtifact' => ApiStatus::Experimental,
        'Kongono\\PersistenceBuild\\Build\\PersistenceArtifactBuilder' => ApiStatus::Experimental,
        'Kongono\\PersistenceBuild\\Build\\PersistenceArtifactPublisher' => ApiStatus::Experimental,
        'Kongono\\PersistenceBuild\\Build\\PersistenceArtifactValidator' => ApiStatus::Experimental,
        'Kongono\\PersistenceBuild\\Diagnostic\\PersistenceBuildDiagnostic' => ApiStatus::Internal,
        'Kongono\\PersistenceBuild\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\PersistenceBuild\\Exception\\GeneratedPersistenceLoadException' => ApiStatus::Experimental,
        'Kongono\\PersistenceBuild\\Exception\\GeneratedPersistenceStatePublicationException' => ApiStatus::Experimental,
        'Kongono\\PersistenceBuild\\Exception\\InvalidGeneratedPersistenceStateException' => ApiStatus::Experimental,
        'Kongono\\PersistenceBuild\\Exception\\InvalidPersistenceArtifactException' => ApiStatus::Experimental,
        'Kongono\\PersistenceBuild\\Exception\\InvalidPersistenceSourceException' => ApiStatus::Experimental,
        'Kongono\\PersistenceBuild\\Exception\\PersistenceArtifactPublicationException' => ApiStatus::Experimental,
        'Kongono\\PersistenceBuild\\Exception\\PersistenceBuildException' => ApiStatus::Internal,
        'Kongono\\PersistenceBuild\\Exception\\StalePersistenceArtifactException' => ApiStatus::Experimental,
        'Kongono\\PersistenceBuild\\Generated\\GeneratedAggregateRepositoryArtifact' => ApiStatus::Experimental,
        'Kongono\\PersistenceBuild\\Generated\\GeneratedAggregateRepositoryGenerator' => ApiStatus::Experimental,
        'Kongono\\PersistenceBuild\\Generated\\GeneratedAggregateRepositoryPublisher' => ApiStatus::Experimental,
        'Kongono\\PersistenceBuild\\Generated\\GeneratedAggregateRepositoryValidator' => ApiStatus::Experimental,
        'Kongono\\PersistenceBuild\\Generated\\GeneratedPersistenceStateArtifact' => ApiStatus::Experimental,
        'Kongono\\PersistenceBuild\\Generated\\GeneratedPersistenceStateGenerator' => ApiStatus::Experimental,
        'Kongono\\PersistenceBuild\\Generated\\GeneratedPersistenceStatePublisher' => ApiStatus::Experimental,
        'Kongono\\PersistenceBuild\\Generated\\GeneratedPersistenceStateValidator' => ApiStatus::Experimental,
        'Kongono\\PersistenceBuild\\Internal\\AggregateRepositoryGenerationImplementation' => ApiStatus::Internal,
        'Kongono\\PersistenceBuild\\Internal\\AggregateRepositorySourceCompiler' => ApiStatus::Internal,
        'Kongono\\PersistenceBuild\\Internal\\CanonicalJson' => ApiStatus::Internal,
        'Kongono\\PersistenceBuild\\Internal\\GeneratedAggregateRepositoryEmitter' => ApiStatus::Internal,
        'Kongono\\PersistenceBuild\\Internal\\GeneratedPersistenceStateEmitter' => ApiStatus::Internal,
        'Kongono\\PersistenceBuild\\Internal\\PathGuard' => ApiStatus::Internal,
        'Kongono\\PersistenceBuild\\Internal\\PersistenceBuildFailureCategory' => ApiStatus::Internal,
        'Kongono\\PersistenceBuild\\Internal\\PersistenceBuildImplementation' => ApiStatus::Internal,
        'Kongono\\PersistenceBuild\\Internal\\PersistenceGenerationImplementation' => ApiStatus::Internal,
        'Kongono\\PersistenceBuild\\Internal\\PersistenceModelCompiler' => ApiStatus::Internal,
        'Kongono\\PersistenceBuild\\Internal\\StrictJsonDecoder' => ApiStatus::Internal,
        'Kongono\\PersistenceBuild\\Model\\AggregateDefinition' => ApiStatus::Experimental,
        'Kongono\\PersistenceBuild\\Model\\AggregateRepositoryDefinition' => ApiStatus::Experimental,
        'Kongono\\PersistenceBuild\\Model\\ConstructionDefinition' => ApiStatus::Experimental,
        'Kongono\\PersistenceBuild\\Model\\ConstructionKind' => ApiStatus::Experimental,
        'Kongono\\PersistenceBuild\\Model\\EntityDefinition' => ApiStatus::Experimental,
        'Kongono\\PersistenceBuild\\Model\\FieldDefinition' => ApiStatus::Experimental,
        'Kongono\\PersistenceBuild\\Model\\FieldRole' => ApiStatus::Experimental,
        'Kongono\\PersistenceBuild\\Model\\FieldValueKind' => ApiStatus::Experimental,
        'Kongono\\PersistenceBuild\\Model\\LoadPlanDefinition' => ApiStatus::Experimental,
        'Kongono\\PersistenceBuild\\Model\\PersistenceModel' => ApiStatus::Experimental,
        'Kongono\\PersistenceBuild\\Model\\RelationshipCardinality' => ApiStatus::Experimental,
        'Kongono\\PersistenceBuild\\Model\\RelationshipCascadeDefinition' => ApiStatus::Experimental,
        'Kongono\\PersistenceBuild\\Model\\RelationshipDefinition' => ApiStatus::Experimental,
        'Kongono\\PersistenceBuild\\Model\\RelationshipOrder' => ApiStatus::Experimental,
        'Kongono\\PersistenceBuild\\Model\\RelationshipLoadDefinition' => ApiStatus::Experimental,
        'Kongono\\PersistenceBuild\\Model\\RelationshipOwnership' => ApiStatus::Experimental,
        'Kongono\\PersistenceBuild\\Model\\SortDirection' => ApiStatus::Experimental,
        'Kongono\\PersistenceBuild\\Runtime\\GeneratedPersistenceLoader' => ApiStatus::Experimental,
        'Kongono\\PersistenceBuild\\Runtime\\LoadedGeneratedPersistence' => ApiStatus::Experimental,
        'Kongono\\DatabaseApplication\\DataOperationRoot' => ApiStatus::Experimental,
        'Kongono\\DatabaseApplication\\Diagnostic\\DatabaseApplicationDiagnostic' => ApiStatus::Internal,
        'Kongono\\DatabaseApplication\\Exception\\DataApplicationLifecycleException' => ApiStatus::Public,
        'Kongono\\DatabaseApplication\\Exception\\DataApplicationWorkflowException' => ApiStatus::Public,
        'Kongono\\DatabaseApplication\\Exception\\DatabaseApplicationException' => ApiStatus::Internal,
        'Kongono\\DatabaseApplication\\Exception\\DatabaseSessionUnavailableException' => ApiStatus::Public,
        'Kongono\\DatabaseApplication\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\DatabaseApplication\\Exception\\InvalidGeneratedDataProfileException' => ApiStatus::Public,
        'Kongono\\DatabaseApplication\\GeneratedDataApplication' => ApiStatus::Experimental,
        'Kongono\\DatabaseApplication\\GeneratedDataOperation' => ApiStatus::Experimental,
        'Kongono\\DatabaseApplication\\OperationDatabaseSession' => ApiStatus::Experimental,
        'Kongono\\DatabaseApplication\\Workflow\\CurrentDataApplicationBuild' => ApiStatus::Experimental,
        'Kongono\\DatabaseApplication\\Workflow\\CurrentDataAccessBuild' => ApiStatus::Experimental,
        'Kongono\\DatabaseApplication\\Workflow\\DataApplicationBuildWorkflow' => ApiStatus::Experimental,
        'Kongono\\DatabaseApplication\\Workflow\\DataAccessBuildWorkflow' => ApiStatus::Experimental,
        'Kongono\\DatabaseApplication\\Workflow\\PreparedDataAccessBuild' => ApiStatus::Experimental,
        'Kongono\\PersistenceApplication\\Diagnostic\\PersistenceApplicationDiagnostic' => ApiStatus::Internal,
        'Kongono\\PersistenceApplication\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\PersistenceApplication\\Exception\\PersistenceApplicationException' => ApiStatus::Internal,
        'Kongono\\PersistenceApplication\\Exception\\PersistenceApplicationWorkflowException' => ApiStatus::Experimental,
        'Kongono\\PersistenceApplication\\Workflow\\CurrentPersistenceApplicationBuild' => ApiStatus::Experimental,
        'Kongono\\PersistenceApplication\\Workflow\\PersistenceApplicationBuildWorkflow' => ApiStatus::Experimental,
        'Kongono\\DatabaseMapping\\Build\\MappingArtifact' => ApiStatus::Experimental,
        'Kongono\\DatabaseMapping\\Build\\MappingArtifactBuilder' => ApiStatus::Experimental,
        'Kongono\\DatabaseMapping\\Build\\MappingArtifactPublisher' => ApiStatus::Experimental,
        'Kongono\\DatabaseMapping\\Build\\MappingArtifactValidator' => ApiStatus::Experimental,
        'Kongono\\DatabaseMapping\\Diagnostic\\MappingDiagnostic' => ApiStatus::Internal,
        'Kongono\\DatabaseMapping\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\DatabaseMapping\\Exception\\GeneratedDataAccessLoadException' => ApiStatus::Public,
        'Kongono\\DatabaseMapping\\Exception\\GeneratedDataAccessPublicationException' => ApiStatus::Public,
        'Kongono\\DatabaseMapping\\Exception\\HydrationException' => ApiStatus::Public,
        'Kongono\\DatabaseMapping\\Exception\\InvalidGeneratedDataAccessException' => ApiStatus::Public,
        'Kongono\\DatabaseMapping\\Exception\\InvalidMappingArtifactException' => ApiStatus::Public,
        'Kongono\\DatabaseMapping\\Exception\\InvalidMappingSourceException' => ApiStatus::Public,
        'Kongono\\DatabaseMapping\\Exception\\MappingArtifactPublicationException' => ApiStatus::Public,
        'Kongono\\DatabaseMapping\\Exception\\MappingException' => ApiStatus::Internal,
        'Kongono\\DatabaseMapping\\Exception\\RepositoryCleanupException' => ApiStatus::Public,
        'Kongono\\DatabaseMapping\\Exception\\RepositoryExecutionException' => ApiStatus::Public,
        'Kongono\\DatabaseMapping\\Exception\\RowCardinalityException' => ApiStatus::Public,
        'Kongono\\DatabaseMapping\\Exception\\StaleMappingArtifactException' => ApiStatus::Public,
        'Kongono\\DatabaseMapping\\Generated\\GeneratedDataAccessArtifact' => ApiStatus::Experimental,
        'Kongono\\DatabaseMapping\\Generated\\GeneratedDataAccessGenerator' => ApiStatus::Experimental,
        'Kongono\\DatabaseMapping\\Generated\\GeneratedDataAccessPublisher' => ApiStatus::Experimental,
        'Kongono\\DatabaseMapping\\Generated\\GeneratedDataAccessValidator' => ApiStatus::Experimental,
        'Kongono\\DatabaseMapping\\Internal\\CanonicalJson' => ApiStatus::Internal,
        'Kongono\\DatabaseMapping\\Internal\\GeneratedDataAccessEmitter' => ApiStatus::Internal,
        'Kongono\\DatabaseMapping\\Internal\\MappingFailureCategory' => ApiStatus::Internal,
        'Kongono\\DatabaseMapping\\Internal\\MappingImplementation' => ApiStatus::Internal,
        'Kongono\\DatabaseMapping\\Internal\\MappingModelCompiler' => ApiStatus::Internal,
        'Kongono\\DatabaseMapping\\Internal\\PathGuard' => ApiStatus::Internal,
        'Kongono\\DatabaseMapping\\Internal\\StrictJsonDecoder' => ApiStatus::Internal,
        'Kongono\\DatabaseMapping\\Runtime\\GeneratedDataAccessLoader' => ApiStatus::Experimental,
        'Kongono\\DatabaseMapping\\Runtime\\LoadedGeneratedDataAccess' => ApiStatus::Experimental,
        'Kongono\\DatabaseMigration\\AddColumn' => ApiStatus::Experimental,
        'Kongono\\DatabaseMigration\\AppliedMigration' => ApiStatus::Experimental,
        'Kongono\\DatabaseMigration\\CreateIndex' => ApiStatus::Experimental,
        'Kongono\\DatabaseMigration\\CreateTable' => ApiStatus::Experimental,
        'Kongono\\DatabaseMigration\\DestructiveMigrationPolicy' => ApiStatus::Experimental,
        'Kongono\\DatabaseMigration\\Diagnostic\\MigrationDiagnostic' => ApiStatus::Internal,
        'Kongono\\DatabaseMigration\\DropColumn' => ApiStatus::Experimental,
        'Kongono\\DatabaseMigration\\DropIndex' => ApiStatus::Experimental,
        'Kongono\\DatabaseMigration\\DropTable' => ApiStatus::Experimental,
        'Kongono\\DatabaseMigration\\Exception\\CombinedMigrationFailureException' => ApiStatus::Experimental,
        'Kongono\\DatabaseMigration\\Exception\\DestructiveMigrationReviewRequiredException' => ApiStatus::Experimental,
        'Kongono\\DatabaseMigration\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\DatabaseMigration\\Exception\\InvalidMigrationDefinitionException' => ApiStatus::Experimental,
        'Kongono\\DatabaseMigration\\Exception\\MigrationException' => ApiStatus::Internal,
        'Kongono\\DatabaseMigration\\Exception\\MigrationExecutionException' => ApiStatus::Experimental,
        'Kongono\\DatabaseMigration\\Exception\\MigrationHistoryMismatchException' => ApiStatus::Experimental,
        'Kongono\\DatabaseMigration\\Exception\\MigrationLockUnavailableException' => ApiStatus::Experimental,
        'Kongono\\DatabaseMigration\\Exception\\MigrationNotReversibleException' => ApiStatus::Experimental,
        'Kongono\\DatabaseMigration\\Exception\\MigrationUncertainStateException' => ApiStatus::Experimental,
        'Kongono\\DatabaseMigration\\Internal\\MigrationCanonicalJson' => ApiStatus::Internal,
        'Kongono\\DatabaseMigration\\MigrationCatalog' => ApiStatus::Experimental,
        'Kongono\\DatabaseMigration\\MigrationDefinition' => ApiStatus::Experimental,
        'Kongono\\DatabaseMigration\\MigrationDirection' => ApiStatus::Experimental,
        'Kongono\\DatabaseMigration\\MigrationDirectionPlan' => ApiStatus::Experimental,
        'Kongono\\DatabaseMigration\\MigrationExecutor' => ApiStatus::Public,
        'Kongono\\DatabaseMigration\\MigrationHistory' => ApiStatus::Experimental,
        'Kongono\\DatabaseMigration\\MigrationOperation' => ApiStatus::Experimental,
        'Kongono\\DatabaseMigration\\MigrationPlan' => ApiStatus::Experimental,
        'Kongono\\DatabaseMigration\\MigrationPlanner' => ApiStatus::Experimental,
        'Kongono\\DatabaseMigration\\MigrationRisk' => ApiStatus::Experimental,
        'Kongono\\DatabaseMigration\\MigrationRunResult' => ApiStatus::Experimental,
        'Kongono\\DatabaseMigration\\PlannedMigration' => ApiStatus::Experimental,
        'Kongono\\DatabaseMigration\\RenameColumn' => ApiStatus::Experimental,
        'Kongono\\DatabaseMigration\\RenameTable' => ApiStatus::Experimental,
        'Kongono\\DatabaseMigration\\SchemaColumn' => ApiStatus::Experimental,
        'Kongono\\DatabaseMigration\\SchemaColumnType' => ApiStatus::Experimental,
        'Kongono\\DatabaseMigrationPgsql\\Internal\\PostgreSqlMigrationSql' => ApiStatus::Internal,
        'Kongono\\DatabaseMigrationPgsql\\PostgreSql17MigrationCompiler' => ApiStatus::Experimental,
        'Kongono\\DatabaseMigrationPgsql\\PostgreSql17MigrationExecutor' => ApiStatus::Experimental,
        'Kongono\\DatabaseMigrationPgsql\\PostgreSqlMigrationConfiguration' => ApiStatus::Experimental,
        'Kongono\\DatabasePdoPgsql\\PdoPgsqlConfiguration' => ApiStatus::Experimental,
        'Kongono\\DatabasePdoPgsql\\PdoPgsqlConnector' => ApiStatus::Experimental,
        'Kongono\\DatabasePdoPgsql\\PdoPgsqlExecution' => ApiStatus::Internal,
        'Kongono\\DatabasePdoPgsql\\PdoPgsqlRowStream' => ApiStatus::Internal,
        'Kongono\\DatabasePdoPgsql\\PdoPgsqlSession' => ApiStatus::Internal,
        'Kongono\\DatabasePdoPgsql\\PdoPgsqlTransaction' => ApiStatus::Internal,
        'Kongono\\DatabaseSql\\DeletePlan' => ApiStatus::Experimental,
        'Kongono\\DatabaseSql\\Diagnostic\\SqlDiagnostic' => ApiStatus::Internal,
        'Kongono\\DatabaseSql\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\DatabaseSql\\Exception\\InvalidSqlPlanException' => ApiStatus::Experimental,
        'Kongono\\DatabaseSql\\Exception\\SqlCompilationException' => ApiStatus::Experimental,
        'Kongono\\DatabaseSql\\Exception\\SqlException' => ApiStatus::Internal,
        'Kongono\\DatabaseSql\\Exception\\UnsupportedDialectCapabilityException' => ApiStatus::Experimental,
        'Kongono\\DatabaseSql\\InsertPlan' => ApiStatus::Experimental,
        'Kongono\\DatabaseSql\\Internal\\SqlValidation' => ApiStatus::Internal,
        'Kongono\\DatabaseSql\\SelectPlan' => ApiStatus::Experimental,
        'Kongono\\DatabaseSql\\SqlAndPredicate' => ApiStatus::Experimental,
        'Kongono\\DatabaseSql\\SqlAssignment' => ApiStatus::Experimental,
        'Kongono\\DatabaseSql\\SqlColumn' => ApiStatus::Experimental,
        'Kongono\\DatabaseSql\\SqlComparisonOperator' => ApiStatus::Experimental,
        'Kongono\\DatabaseSql\\SqlComparisonPredicate' => ApiStatus::Experimental,
        'Kongono\\DatabaseSql\\SqlDialectCapabilities' => ApiStatus::Experimental,
        'Kongono\\DatabaseSql\\SqlDialectCapability' => ApiStatus::Experimental,
        'Kongono\\DatabaseSql\\SqlDialectCompiler' => ApiStatus::Experimental,
        'Kongono\\DatabaseSql\\SqlIdentifier' => ApiStatus::Experimental,
        'Kongono\\DatabaseSql\\SqlInPredicate' => ApiStatus::Experimental,
        'Kongono\\DatabaseSql\\SqlMutationScope' => ApiStatus::Experimental,
        'Kongono\\DatabaseSql\\SqlNotPredicate' => ApiStatus::Experimental,
        'Kongono\\DatabaseSql\\SqlNullOrdering' => ApiStatus::Experimental,
        'Kongono\\DatabaseSql\\SqlNullPredicate' => ApiStatus::Experimental,
        'Kongono\\DatabaseSql\\SqlNullTest' => ApiStatus::Experimental,
        'Kongono\\DatabaseSql\\SqlOrder' => ApiStatus::Experimental,
        'Kongono\\DatabaseSql\\SqlOrPredicate' => ApiStatus::Experimental,
        'Kongono\\DatabaseSql\\SqlPlan' => ApiStatus::Experimental,
        'Kongono\\DatabaseSql\\SqlPlanKind' => ApiStatus::Experimental,
        'Kongono\\DatabaseSql\\SqlPredicate' => ApiStatus::Experimental,
        'Kongono\\DatabaseSql\\SqlProjection' => ApiStatus::Experimental,
        'Kongono\\DatabaseSql\\SqlQualifiedName' => ApiStatus::Experimental,
        'Kongono\\DatabaseSql\\SqlSortDirection' => ApiStatus::Experimental,
        'Kongono\\DatabaseSql\\UpdatePlan' => ApiStatus::Experimental,
        'Kongono\\DatabaseSqlPgsql\\Internal\\PostgreSqlCompilation' => ApiStatus::Internal,
        'Kongono\\DatabaseSqlPgsql\\PostgreSql17Compiler' => ApiStatus::Experimental,
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
        AuthenticationEvidence::class => ApiStatus::Experimental,
        AuthenticationFacts::class => ApiStatus::Experimental,
        AuthenticationInstant::class => ApiStatus::Experimental,
        AuthenticationMechanism::class => ApiStatus::Experimental,
        AuthenticationOrchestrator::class => ApiStatus::Experimental,
        AuthenticationRequest::class => ApiStatus::Experimental,
        AuthenticationRequirement::class => ApiStatus::Experimental,
        AuthenticationStrength::class => ApiStatus::Experimental,
        AuthenticationVerification::class => ApiStatus::Experimental,
        AuthenticationVerificationOperation::class => ApiStatus::Experimental,
        AuthenticationVerifier::class => ApiStatus::Experimental,
        AuthenticationDiagnostic::class => ApiStatus::Internal,
        AuthenticationCleanupException::class => ApiStatus::Experimental,
        AuthenticationException::class => ApiStatus::Internal,
        AuthenticationOperationAndCleanupException::class => ApiStatus::Experimental,
        AuthenticationExceptionInterface::class => ApiStatus::Public,
        InvalidAuthenticationValueException::class => ApiStatus::Experimental,
        AuthorizationDecisionReceipt::class => ApiStatus::Experimental,
        AuthorizationEngine::class => ApiStatus::Experimental,
        AuthorizationEnvironmentEvidence::class => ApiStatus::Experimental,
        AuthorizationEnvironmentProfile::class => ApiStatus::Experimental,
        AuthorizationEvaluation::class => ApiStatus::Experimental,
        AuthorizationEvaluator::class => ApiStatus::Experimental,
        AuthorizationPolicyEvidence::class => ApiStatus::Experimental,
        AuthorizationPolicyName::class => ApiStatus::Experimental,
        AuthorizationPolicyProvenance::class => ApiStatus::Experimental,
        AuthorizationPolicyReference::class => ApiStatus::Experimental,
        AuthorizationPolicyVersion::class => ApiStatus::Experimental,
        AuthorizationReceiptReason::class => ApiStatus::Experimental,
        AuthorizationRequest::class => ApiStatus::Experimental,
        AuthorizationDiagnostic::class => ApiStatus::Internal,
        AuthorizationEvaluationException::class => ApiStatus::Experimental,
        AuthorizationException::class => ApiStatus::Internal,
        AuthorizationExceptionInterface::class => ApiStatus::Public,
        InvalidAuthorizationValueException::class => ApiStatus::Experimental,
        'Kongono\\SecurityEnforcement\\Diagnostic\\SecurityEnforcementDiagnostic' => ApiStatus::Internal,
        'Kongono\\SecurityEnforcement\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\SecurityEnforcement\\Exception\\ProtectedCapabilityEnforcementException' => ApiStatus::Experimental,
        'Kongono\\SecurityEnforcement\\Exception\\SecurityEnforcementException' => ApiStatus::Internal,
        'Kongono\\SecurityEnforcement\\ProtectedCapability' => ApiStatus::Experimental,
        'Kongono\\SecurityEnforcement\\ProtectedCapabilityEnforcer' => ApiStatus::Experimental,
        'Kongono\\SecurityApplication\\BrowserSessionProtectedApplication' => ApiStatus::Experimental,
        'Kongono\\SecurityHttp\\BrowserSessionCookieEmitter' => ApiStatus::Experimental,
        'Kongono\\SecurityHttp\\BrowserSessionCsrfProof' => ApiStatus::Experimental,
        'Kongono\\SecurityHttp\\BrowserSessionEvidence' => ApiStatus::Experimental,
        'Kongono\\SecurityHttp\\BrowserSessionIdentifier' => ApiStatus::Experimental,
        'Kongono\\SecurityHttp\\BrowserSessionProfile' => ApiStatus::Experimental,
        'Kongono\\SecurityHttp\\BrowserSessionSecurityGateway' => ApiStatus::Experimental,
        'Kongono\\SecurityHttp\\BrowserSessionSecurityOperation' => ApiStatus::Experimental,
        'Kongono\\SecurityHttp\\Diagnostic\\SecurityHttpDiagnostic' => ApiStatus::Internal,
        'Kongono\\SecurityHttp\\Exception\\BrowserSessionAuthenticationException' => ApiStatus::Experimental,
        'Kongono\\SecurityHttp\\Exception\\BrowserSessionRequestException' => ApiStatus::Experimental,
        'Kongono\\SecurityHttp\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\SecurityHttp\\Exception\\InvalidBrowserSessionValueException' => ApiStatus::Experimental,
        'Kongono\\SecurityHttp\\Exception\\SecurityHttpException' => ApiStatus::Internal,
        'Kongono\\SecurityHttp\\Internal\\BrowserSessionRequestExtractor' => ApiStatus::Internal,
        'Kongono\\SecurityHttp\\Internal\\BrowserSessionRequestProfile' => ApiStatus::Internal,
        'Kongono\\SecurityHttp\\TrustedOrigin' => ApiStatus::Experimental,
        'Kongono\\SecurityPolicyBuild\\Build\\SecurityPolicyArtifact' => ApiStatus::Experimental,
        'Kongono\\SecurityPolicyBuild\\Build\\SecurityPolicyArtifactBuilder' => ApiStatus::Experimental,
        'Kongono\\SecurityPolicyBuild\\Build\\SecurityPolicyArtifactPublisher' => ApiStatus::Experimental,
        'Kongono\\SecurityPolicyBuild\\Build\\SecurityPolicyArtifactValidator' => ApiStatus::Experimental,
        'Kongono\\SecurityPolicyBuild\\Build\\SecurityPolicySourceReader' => ApiStatus::Experimental,
        'Kongono\\SecurityPolicyBuild\\Diagnostic\\SecurityPolicyBuildDiagnostic' => ApiStatus::Internal,
        'Kongono\\SecurityPolicyBuild\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\SecurityPolicyBuild\\Exception\\InvalidSecurityPolicyArtifactException' => ApiStatus::Experimental,
        'Kongono\\SecurityPolicyBuild\\Exception\\InvalidSecurityPolicySourceException' => ApiStatus::Experimental,
        'Kongono\\SecurityPolicyBuild\\Exception\\SecurityPolicyBuildException' => ApiStatus::Internal,
        'Kongono\\SecurityPolicyBuild\\Exception\\SecurityPolicyPublicationException' => ApiStatus::Experimental,
        'Kongono\\SecurityPolicyBuild\\Exception\\SecurityPolicyWorkflowException' => ApiStatus::Experimental,
        'Kongono\\SecurityPolicyBuild\\Exception\\StaleSecurityPolicyArtifactException' => ApiStatus::Experimental,
        'Kongono\\SecurityPolicyBuild\\Internal\\CanonicalJson' => ApiStatus::Internal,
        'Kongono\\SecurityPolicyBuild\\Internal\\PathGuard' => ApiStatus::Internal,
        'Kongono\\SecurityPolicyBuild\\Internal\\StrictJsonDecoder' => ApiStatus::Internal,
        'Kongono\\SecurityPolicyBuild\\Model\\SecurityPolicyModel' => ApiStatus::Experimental,
        'Kongono\\SecurityPolicyBuild\\Workflow\\CurrentSecurityPolicy' => ApiStatus::Experimental,
        'Kongono\\SecurityPolicyBuild\\Workflow\\PlannedSecurityPolicy' => ApiStatus::Experimental,
        'Kongono\\SecurityPolicyBuild\\Workflow\\SecurityPolicyBuildWorkflow' => ApiStatus::Experimental,
        'Kongono\\SecurityRuntime\\Diagnostic\\SecurityRuntimeDiagnostic' => ApiStatus::Internal,
        'Kongono\\SecurityRuntime\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\SecurityRuntime\\Exception\\InvalidGeneratedSecurityProfileException' => ApiStatus::Experimental,
        'Kongono\\SecurityRuntime\\Exception\\InvalidSecurityContextVerificationException' => ApiStatus::Experimental,
        'Kongono\\SecurityRuntime\\Exception\\SecurityRuntimeException' => ApiStatus::Internal,
        'Kongono\\SecurityRuntime\\Exception\\SecurityRuntimeLifecycleException' => ApiStatus::Experimental,
        'Kongono\\SecurityRuntime\\GeneratedSecurityApplication' => ApiStatus::Experimental,
        'Kongono\\SecurityRuntime\\GeneratedSecurityOperation' => ApiStatus::Experimental,
        'Kongono\\SecurityRuntime\\SecurityContextFactory' => ApiStatus::Experimental,
        'Kongono\\SecurityRuntime\\SecurityOperationRoot' => ApiStatus::Experimental,
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
        Action::class => ApiStatus::Experimental,
        AuthenticationOutcome::class => ApiStatus::Experimental,
        AuthenticationOutcomeKind::class => ApiStatus::Experimental,
        AuthorizationDecision::class => ApiStatus::Experimental,
        IdentityAccessDiagnostic::class => ApiStatus::Internal,
        IdentityAccessExceptionInterface::class => ApiStatus::Public,
        IdentityAccessException::class => ApiStatus::Internal,
        InvalidIdentityAccessValueException::class => ApiStatus::Experimental,
        PrincipalId::class => ApiStatus::Experimental,
        PrincipalKind::class => ApiStatus::Experimental,
        PrincipalReference::class => ApiStatus::Experimental,
        ResourceId::class => ApiStatus::Experimental,
        ResourceReference::class => ApiStatus::Experimental,
        ResourceType::class => ApiStatus::Experimental,
        SecurityContext::class => ApiStatus::Experimental,
        SecurityContextState::class => ApiStatus::Experimental,
        'Kongono\\ModelInvocationAgentBridge\\Bridge\\ModelResultProposalBridge' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationAgentBridge\\Diagnostic\\ModelInvocationAgentBridgeDiagnostic' => ApiStatus::Internal,
        'Kongono\\ModelInvocationAgentBridge\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\ModelInvocationAgentBridge\\Exception\\InvalidModelInvocationAgentBridgeException' => ApiStatus::Internal,
        'Kongono\\ModelInvocationAgentBridge\\Exception\\ModelInvocationAgentBridgeException' => ApiStatus::Internal,
        'Kongono\\ModelInvocationAgentBridge\\Exception\\ProposalBridgeRejectedException' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationAgentBridge\\Internal\\BridgeGuard' => ApiStatus::Internal,
        'Kongono\\ModelInvocationAgentBridge\\Internal\\CanonicalEvidence' => ApiStatus::Internal,
        'Kongono\\ModelInvocationAgentBridge\\Internal\\ProposalFactory' => ApiStatus::Internal,
        'Kongono\\ModelInvocationAgentBridge\\Internal\\UsageProjector' => ApiStatus::Internal,
        'Kongono\\ModelInvocationAgentBridge\\Internal\\VisibilityProjector' => ApiStatus::Internal,
        'Kongono\\ModelInvocationAgentBridge\\Mapping\\ActionProposalMapping' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationAgentBridge\\Mapping\\ProposalUsageSelector' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationApplication\\Diagnostic\\ModelInvocationApplicationDiagnostic' => ApiStatus::Internal,
        'Kongono\\ModelInvocationApplication\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\ModelInvocationApplication\\Exception\\InvalidGeneratedModelInvocationWiringException' => ApiStatus::Internal,
        'Kongono\\ModelInvocationApplication\\Exception\\InvalidModelInvocationApplicationProfileException' => ApiStatus::Internal,
        'Kongono\\ModelInvocationApplication\\Exception\\ModelInvocationApplicationException' => ApiStatus::Internal,
        'Kongono\\ModelInvocationApplication\\Exception\\ModelInvocationApplicationLifecycleException' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationApplication\\Exception\\ModelInvocationApplicationOperationException' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationApplication\\Exception\\ModelInvocationApplicationWorkflowException' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationApplication\\Exception\\ModelInvocationWiringLoadException' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationApplication\\Exception\\ModelInvocationWiringPublicationException' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationApplication\\Generated\\GeneratedModelInvocationApplicationBoundary' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationApplication\\Generated\\GeneratedModelInvocationWiring' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationApplication\\Generated\\ModelInvocationWiringGenerator' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationApplication\\Generated\\ModelInvocationWiringPublisher' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationApplication\\Generated\\ModelInvocationWiringValidator' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationApplication\\Internal\\CanonicalProfile' => ApiStatus::Internal,
        'Kongono\\ModelInvocationApplication\\Internal\\GeneratedWiringEmitter' => ApiStatus::Internal,
        'Kongono\\ModelInvocationApplication\\Internal\\GeneratedWiringImplementation' => ApiStatus::Internal,
        'Kongono\\ModelInvocationApplication\\Internal\\PathGuard' => ApiStatus::Internal,
        'Kongono\\ModelInvocationApplication\\Profile\\ModelInvocationApplicationProfile' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationApplication\\Profile\\ModelInvocationApplicationProfileFactory' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationApplication\\Profile\\ModelInvocationApplicationProfileIdentity' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationApplication\\Runtime\\LoadedModelInvocationWiring' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationApplication\\Runtime\\ModelInvocationApplication' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationApplication\\Runtime\\ModelInvocationApplicationRunInput' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationApplication\\Runtime\\ModelInvocationApplicationState' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationApplication\\Runtime\\ModelInvocationWiringLoader' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationApplication\\Workflow\\CurrentModelInvocationApplicationBuild' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationApplication\\Workflow\\ModelInvocationApplicationBuildWorkflow' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationBuild\\Artifact\\InvocationPlanArtifact' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationBuild\\Artifact\\InvocationPlanArtifactBuilder' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationBuild\\Artifact\\InvocationPlanArtifactPublisher' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationBuild\\Artifact\\InvocationPlanArtifactValidator' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationBuild\\Budget\\CostLimit' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationBuild\\Budget\\InvocationExecutionBudget' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationBuild\\Budget\\UsageLimit' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationBuild\\Diagnostic\\ModelInvocationBuildDiagnostic' => ApiStatus::Internal,
        'Kongono\\ModelInvocationBuild\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\ModelInvocationBuild\\Exception\\InvalidInvocationPlanArtifactException' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationBuild\\Exception\\InvalidInvocationPlanSourceException' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationBuild\\Exception\\InvocationPlanPublicationException' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationBuild\\Exception\\ModelInvocationBuildException' => ApiStatus::Internal,
        'Kongono\\ModelInvocationBuild\\Exception\\StaleInvocationPlanArtifactException' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationBuild\\Internal\\CanonicalJson' => ApiStatus::Internal,
        'Kongono\\ModelInvocationBuild\\Internal\\PathGuard' => ApiStatus::Internal,
        'Kongono\\ModelInvocationBuild\\Plan\\AdapterIdentity' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationBuild\\Plan\\InvocationPlanIdentity' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationBuild\\Plan\\InvocationPlanSource' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationContract\\ContractVersion' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationContract\\Cost\\CostEvidence' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationContract\\Cost\\CostEvidenceKind' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationContract\\Cost\\CurrencyCode' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationContract\\Cost\\DecimalAmount' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationContract\\Diagnostic\\ModelInvocationContractDiagnostic' => ApiStatus::Internal,
        'Kongono\\ModelInvocationContract\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\ModelInvocationContract\\Exception\\InvalidModelInvocationContractException' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationContract\\Exception\\ModelInvocationContractException' => ApiStatus::Internal,
        'Kongono\\ModelInvocationContract\\Input\\InputMessage' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationContract\\Input\\InputRole' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationContract\\Internal\\ContractGuard' => ApiStatus::Internal,
        'Kongono\\ModelInvocationContract\\Invocation\\InvocationIdentity' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationContract\\Invocation\\InvocationLimits' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationContract\\Invocation\\InvocationRequest' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationContract\\Invocation\\ResultMode' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationContract\\Lifecycle\\InvocationState' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationContract\\Profile\\ModelProfileReference' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationContract\\Profile\\ProfileIdentity' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationContract\\Provider\\ModelIdentity' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationContract\\Provider\\ProviderCapability' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationContract\\Provider\\ProviderCapabilitySet' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationContract\\Provider\\ProviderIdentity' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationContract\\Receipt\\InvocationOutcome' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationContract\\Receipt\\InvocationReceipt' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationContract\\Receipt\\ReceiptIdentity' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationContract\\Result\\ActionArguments' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationContract\\Result\\ActionName' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationContract\\Result\\ActionSuggestion' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationContract\\Result\\FinishReason' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationContract\\Result\\ModelResult' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationContract\\Result\\ResultIdentity' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationContract\\Result\\ResultText' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationContract\\Stream\\ModelStreamEvent' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationContract\\Stream\\StreamEventKind' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationContract\\Stream\\StreamSequence' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationContract\\Usage\\UsageEvidence' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationContract\\Usage\\UsageMetricName' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationContract\\Usage\\UsageQuantity' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationContract\\Usage\\UsageUnitName' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationContract\\Visibility\\ModelVisibility' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationInspection\\Derivation\\ModelInvocationIntelligenceDeriver' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationInspection\\Derivation\\ModelInvocationIntelligenceView' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationInspection\\Diagnostic\\ModelInvocationInspectionDiagnostic' => ApiStatus::Internal,
        'Kongono\\ModelInvocationInspection\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\ModelInvocationInspection\\Exception\\InvalidModelInvocationInspectionException' => ApiStatus::Internal,
        'Kongono\\ModelInvocationInspection\\Exception\\InvalidModelInvocationIntelligenceEvidenceException' => ApiStatus::Internal,
        'Kongono\\ModelInvocationInspection\\Exception\\ModelInvocationInspectionBindingException' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationInspection\\Exception\\ModelInvocationInspectionException' => ApiStatus::Internal,
        'Kongono\\ModelInvocationInspection\\Exception\\ModelInvocationInspectionProjectionException' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationInspection\\Exception\\ModelInvocationRuntimeEvidenceException' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationInspection\\Graph\\ModelInvocationGraphEvidence' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationInspection\\Graph\\ModelInvocationGraphEvidenceProjector' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationInspection\\Inspection\\ModelInvocationInspection' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationInspection\\Inspection\\ModelInvocationInspectionRequest' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationInspection\\Inspection\\ModelInvocationInspector' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationInspection\\Internal\\CanonicalJson' => ApiStatus::Internal,
        'Kongono\\ModelInvocationInspection\\Projection\\ModelInvocationObservationProjector' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationInspection\\Projection\\ModelInvocationSecurityAuditProjector' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationInspection\\Role\\ModelInspectionDeveloperRole' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationInspection\\Verification\\ModelInvocationIntelligenceVerdict' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationInspection\\Verification\\ModelInvocationIntelligenceVerification' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationInspection\\Verification\\ModelInvocationIntelligenceVerifier' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationRuntime\\Adapter\\InvocationAdapter' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationRuntime\\Adapter\\InvocationAdapterEmission' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationRuntime\\Adapter\\InvocationAdapterSession' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationRuntime\\Cancellation\\InvocationCancellation' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationRuntime\\Clock\\InvocationMonotonicClock' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationRuntime\\Diagnostic\\ModelInvocationRuntimeDiagnostic' => ApiStatus::Internal,
        'Kongono\\ModelInvocationRuntime\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\ModelInvocationRuntime\\Exception\\InvalidInvocationRuntimeException' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationRuntime\\Exception\\InvocationCleanupException' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationRuntime\\Exception\\InvocationOperationAndCleanupException' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationRuntime\\Exception\\InvocationOperationException' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationRuntime\\Exception\\InvocationRejectedException' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationRuntime\\Exception\\ModelInvocationRuntimeException' => ApiStatus::Internal,
        'Kongono\\ModelInvocationRuntime\\Execution\\InvocationRuntimeRequest' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationRuntime\\Execution\\InvocationRuntimeResult' => ApiStatus::Experimental,
        'Kongono\\ModelInvocationRuntime\\Internal\\BudgetMeter' => ApiStatus::Internal,
        'Kongono\\ModelInvocationRuntime\\Internal\\LifecycleFailureComposer' => ApiStatus::Internal,
        'Kongono\\ModelInvocationRuntime\\Internal\\ReceiptFactory' => ApiStatus::Internal,
        'Kongono\\ModelInvocationRuntime\\Internal\\RuntimeGuard' => ApiStatus::Internal,
        'Kongono\\ModelInvocationRuntime\\Internal\\StreamGuard' => ApiStatus::Internal,
        'Kongono\\ModelInvocationRuntime\\Runtime\\ModelInvocationRuntime' => ApiStatus::Experimental,
        'Kongono\\Observability\\AttributeKey' => ApiStatus::Experimental,
        'Kongono\\Observability\\AttributeSet' => ApiStatus::Experimental,
        'Kongono\\Observability\\AttributeValue' => ApiStatus::Experimental,
        'Kongono\\Observability\\AttributeValueKind' => ApiStatus::Experimental,
        'Kongono\\Observability\\CorrelationId' => ApiStatus::Experimental,
        'Kongono\\Observability\\Diagnostic\\ObservabilityDiagnostic' => ApiStatus::Internal,
        'Kongono\\Observability\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\Observability\\Exception\\InvalidObservabilityValueException' => ApiStatus::Experimental,
        'Kongono\\Observability\\Exception\\ObservabilityException' => ApiStatus::Internal,
        'Kongono\\Observability\\Internal\\ObservabilityValidation' => ApiStatus::Internal,
        'Kongono\\Observability\\Observation' => ApiStatus::Experimental,
        'Kongono\\Observability\\ObservationKind' => ApiStatus::Experimental,
        'Kongono\\Observability\\ObservationRecorder' => ApiStatus::Experimental,
        'Kongono\\Observability\\ObservationStatus' => ApiStatus::Experimental,
        'Kongono\\Observability\\ObservabilityContext' => ApiStatus::Experimental,
        'Kongono\\Observability\\SignalAttribute' => ApiStatus::Experimental,
        'Kongono\\Observability\\SignalIdentity' => ApiStatus::Experimental,
        'Kongono\\ObservabilityApplication\\ApplicationObservability' => ApiStatus::Experimental,
        'Kongono\\ObservabilityApplication\\ApplicationObservabilityFactory' => ApiStatus::Experimental,
        'Kongono\\ObservabilityApplication\\ApplicationObservabilityOperation' => ApiStatus::Experimental,
        'Kongono\\ObservabilityApplication\\ApplicationObservabilityOperationState' => ApiStatus::Experimental,
        'Kongono\\ObservabilityApplication\\ApplicationObservabilityPlan' => ApiStatus::Experimental,
        'Kongono\\ObservabilityApplication\\ApplicationObservabilityWork' => ApiStatus::Experimental,
        'Kongono\\ObservabilityApplication\\Diagnostic\\ObservabilityApplicationDiagnostic' => ApiStatus::Internal,
        'Kongono\\ObservabilityApplication\\Exception\\ApplicationObservabilityCleanupException' => ApiStatus::Experimental,
        'Kongono\\ObservabilityApplication\\Exception\\ApplicationObservabilityLifecycleException' => ApiStatus::Experimental,
        'Kongono\\ObservabilityApplication\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\ObservabilityApplication\\Exception\\ObservabilityApplicationException' => ApiStatus::Internal,
        'Kongono\\ObservabilityApplication\\Internal\\ApplicationObservabilityOperationBoundary' => ApiStatus::Internal,
        'Kongono\\ObservabilityBuild\\Build\\ObservabilityArtifact' => ApiStatus::Experimental,
        'Kongono\\ObservabilityBuild\\Build\\ObservabilityArtifactBuilder' => ApiStatus::Experimental,
        'Kongono\\ObservabilityBuild\\Build\\ObservabilityArtifactPublisher' => ApiStatus::Internal,
        'Kongono\\ObservabilityBuild\\Build\\ObservabilityArtifactValidator' => ApiStatus::Internal,
        'Kongono\\ObservabilityBuild\\Build\\ObservabilitySourceReader' => ApiStatus::Experimental,
        'Kongono\\ObservabilityBuild\\Diagnostic\\ObservabilityBuildDiagnostic' => ApiStatus::Internal,
        'Kongono\\ObservabilityBuild\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\ObservabilityBuild\\Exception\\InvalidObservabilityArtifactException' => ApiStatus::Experimental,
        'Kongono\\ObservabilityBuild\\Exception\\InvalidObservabilitySourceException' => ApiStatus::Experimental,
        'Kongono\\ObservabilityBuild\\Exception\\ObservabilityPublicationException' => ApiStatus::Experimental,
        'Kongono\\ObservabilityBuild\\Exception\\ObservabilityBuildException' => ApiStatus::Internal,
        'Kongono\\ObservabilityBuild\\Exception\\ObservabilityWorkflowException' => ApiStatus::Experimental,
        'Kongono\\ObservabilityBuild\\Exception\\StaleObservabilityArtifactException' => ApiStatus::Experimental,
        'Kongono\\ObservabilityBuild\\Internal\\CanonicalJson' => ApiStatus::Internal,
        'Kongono\\ObservabilityBuild\\Internal\\PathGuard' => ApiStatus::Internal,
        'Kongono\\ObservabilityBuild\\Internal\\StrictJsonDecoder' => ApiStatus::Internal,
        'Kongono\\ObservabilityBuild\\Model\\ObservabilityConfigurationModel' => ApiStatus::Experimental,
        'Kongono\\ObservabilityBuild\\Workflow\\CurrentObservabilityConfiguration' => ApiStatus::Experimental,
        'Kongono\\ObservabilityBuild\\Workflow\\ObservabilityBuildWorkflow' => ApiStatus::Experimental,
        'Kongono\\ObservabilityBuild\\Workflow\\PlannedObservabilityConfiguration' => ApiStatus::Experimental,
        'Kongono\\OpenTelemetry\\Diagnostic\\OpenTelemetryDiagnostic' => ApiStatus::Internal,
        'Kongono\\OpenTelemetry\\Exception\\ExceptionInterface' => ApiStatus::Experimental,
        'Kongono\\OpenTelemetry\\Exception\\InvalidOpenTelemetryConfigurationException' => ApiStatus::Internal,
        'Kongono\\OpenTelemetry\\Exception\\OpenTelemetryBackpressureException' => ApiStatus::Internal,
        'Kongono\\OpenTelemetry\\Exception\\OpenTelemetryException' => ApiStatus::Internal,
        'Kongono\\OpenTelemetry\\Exception\\OpenTelemetryExportException' => ApiStatus::Internal,
        'Kongono\\OpenTelemetry\\Exception\\OpenTelemetryLifecycleException' => ApiStatus::Internal,
        'Kongono\\OpenTelemetry\\Exception\\OpenTelemetryMappingException' => ApiStatus::Internal,
        'Kongono\\OpenTelemetry\\Internal\\ObservationQueue' => ApiStatus::Internal,
        'Kongono\\OpenTelemetry\\Internal\\OpenTelemetryValidation' => ApiStatus::Internal,
        'Kongono\\OpenTelemetry\\Internal\\OtlpAttributeMapper' => ApiStatus::Internal,
        'Kongono\\OpenTelemetry\\Internal\\OtlpLogMapper' => ApiStatus::Internal,
        'Kongono\\OpenTelemetry\\Internal\\OtlpMetricMapper' => ApiStatus::Internal,
        'Kongono\\OpenTelemetry\\Internal\\OtlpRequestFactory' => ApiStatus::Internal,
        'Kongono\\OpenTelemetry\\Internal\\OtlpTraceMapper' => ApiStatus::Internal,
        'Kongono\\OpenTelemetry\\Internal\\QueuedObservation' => ApiStatus::Internal,
        'Kongono\\OpenTelemetry\\OpenTelemetryBatchPolicy' => ApiStatus::Experimental,
        'Kongono\\OpenTelemetry\\OpenTelemetryBridge' => ApiStatus::Experimental,
        'Kongono\\OpenTelemetry\\OpenTelemetryBridgeState' => ApiStatus::Experimental,
        'Kongono\\OpenTelemetry\\OpenTelemetryClock' => ApiStatus::Experimental,
        'Kongono\\OpenTelemetry\\OpenTelemetryResource' => ApiStatus::Experimental,
        'Kongono\\OpenTelemetry\\OpenTelemetryTimeAnchor' => ApiStatus::Experimental,
        'Kongono\\OpenTelemetry\\OtlpHttpTransport' => ApiStatus::Experimental,
        'Kongono\\OpenTelemetry\\OtlpSignal' => ApiStatus::Experimental,
        'Kongono\\Tracing\\Diagnostic\\TracingDiagnostic' => ApiStatus::Internal,
        'Kongono\\Tracing\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\Tracing\\Exception\\InvalidTracingValueException' => ApiStatus::Experimental,
        'Kongono\\Tracing\\Exception\\TraceRecordingException' => ApiStatus::Experimental,
        'Kongono\\Tracing\\Exception\\TracingCollaboratorException' => ApiStatus::Experimental,
        'Kongono\\Tracing\\Exception\\TracingException' => ApiStatus::Internal,
        'Kongono\\Tracing\\Exception\\TracingLifecycleException' => ApiStatus::Experimental,
        'Kongono\\Tracing\\Internal\\RecordingTraceOperation' => ApiStatus::Internal,
        'Kongono\\Tracing\\Internal\\RecordingTraceSpan' => ApiStatus::Internal,
        'Kongono\\Tracing\\Internal\\TraceObservationFactory' => ApiStatus::Internal,
        'Kongono\\Tracing\\Internal\\TracingValidation' => ApiStatus::Internal,
        'Kongono\\Tracing\\SpanId' => ApiStatus::Experimental,
        'Kongono\\Tracing\\TraceClock' => ApiStatus::Experimental,
        'Kongono\\Tracing\\TraceId' => ApiStatus::Experimental,
        'Kongono\\Tracing\\TraceIdentifierSource' => ApiStatus::Experimental,
        'Kongono\\Tracing\\TraceLifecycleState' => ApiStatus::Experimental,
        'Kongono\\Tracing\\TraceOperation' => ApiStatus::Experimental,
        'Kongono\\Tracing\\TracePropagation' => ApiStatus::Experimental,
        'Kongono\\Tracing\\TraceRuntime' => ApiStatus::Experimental,
        'Kongono\\Tracing\\TraceSampler' => ApiStatus::Experimental,
        'Kongono\\Tracing\\TraceSamplingDecision' => ApiStatus::Experimental,
        'Kongono\\Tracing\\TraceSamplingRequest' => ApiStatus::Experimental,
        'Kongono\\Tracing\\TraceSpan' => ApiStatus::Experimental,
        'Kongono\\Metrics\\Diagnostic\\MetricsDiagnostic' => ApiStatus::Internal,
        'Kongono\\Metrics\\Exception\\ExceptionInterface' => ApiStatus::Public,
        'Kongono\\Metrics\\Exception\\InvalidMetricSchemaException' => ApiStatus::Experimental,
        'Kongono\\Metrics\\Exception\\InvalidMetricValueException' => ApiStatus::Experimental,
        'Kongono\\Metrics\\Exception\\MetricAggregationException' => ApiStatus::Experimental,
        'Kongono\\Metrics\\Exception\\MetricLifecycleException' => ApiStatus::Experimental,
        'Kongono\\Metrics\\Exception\\MetricRecordingException' => ApiStatus::Experimental,
        'Kongono\\Metrics\\Exception\\MetricsException' => ApiStatus::Internal,
        'Kongono\\Metrics\\HistogramBoundaries' => ApiStatus::Experimental,
        'Kongono\\Metrics\\Internal\\AggregatingMetricCollection' => ApiStatus::Internal,
        'Kongono\\Metrics\\Internal\\GaugeMetricAggregate' => ApiStatus::Internal,
        'Kongono\\Metrics\\Internal\\HistogramMetricAggregate' => ApiStatus::Internal,
        'Kongono\\Metrics\\Internal\\MetricAggregate' => ApiStatus::Internal,
        'Kongono\\Metrics\\Internal\\MetricAggregateFactory' => ApiStatus::Internal,
        'Kongono\\Metrics\\Internal\\MetricNumberAccumulator' => ApiStatus::Internal,
        'Kongono\\Metrics\\Internal\\MetricObservationFactory' => ApiStatus::Internal,
        'Kongono\\Metrics\\Internal\\MetricSeries' => ApiStatus::Internal,
        'Kongono\\Metrics\\Internal\\MetricsValidation' => ApiStatus::Internal,
        'Kongono\\Metrics\\Internal\\SumMetricAggregate' => ApiStatus::Internal,
        'Kongono\\Metrics\\MetricCollection' => ApiStatus::Experimental,
        'Kongono\\Metrics\\MetricCollectionState' => ApiStatus::Experimental,
        'Kongono\\Metrics\\MetricDimension' => ApiStatus::Experimental,
        'Kongono\\Metrics\\MetricDimensionSchema' => ApiStatus::Experimental,
        'Kongono\\Metrics\\MetricInstrument' => ApiStatus::Experimental,
        'Kongono\\Metrics\\MetricInstrumentKind' => ApiStatus::Experimental,
        'Kongono\\Metrics\\MetricMeasurement' => ApiStatus::Experimental,
        'Kongono\\Metrics\\MetricNumber' => ApiStatus::Experimental,
        'Kongono\\Metrics\\MetricPlan' => ApiStatus::Experimental,
        'Kongono\\Metrics\\MetricRuntime' => ApiStatus::Experimental,
        'Kongono\\Metrics\\MetricUnit' => ApiStatus::Experimental,
        'Kongono\\OperationalEvents\\Diagnostic\\OperationalEventsDiagnostic' => ApiStatus::Internal,
        'Kongono\\OperationalEvents\\Exception\\ExceptionInterface' => ApiStatus::Experimental,
        'Kongono\\OperationalEvents\\Exception\\InvalidOperationalEventSchemaException' => ApiStatus::Experimental,
        'Kongono\\OperationalEvents\\Exception\\InvalidOperationalEventValueException' => ApiStatus::Experimental,
        'Kongono\\OperationalEvents\\Exception\\OperationalEventException' => ApiStatus::Internal,
        'Kongono\\OperationalEvents\\Exception\\OperationalEventRecordingException' => ApiStatus::Experimental,
        'Kongono\\OperationalEvents\\Internal\\OperationalEventObservationFactory' => ApiStatus::Internal,
        'Kongono\\OperationalEvents\\Internal\\OperationalEventValidation' => ApiStatus::Internal,
        'Kongono\\OperationalEvents\\OperationalEvent' => ApiStatus::Experimental,
        'Kongono\\OperationalEvents\\OperationalEventDefinition' => ApiStatus::Experimental,
        'Kongono\\OperationalEvents\\OperationalEventField' => ApiStatus::Experimental,
        'Kongono\\OperationalEvents\\OperationalEventRuntime' => ApiStatus::Experimental,
        'Kongono\\OperationalEvents\\OperationalEventSchema' => ApiStatus::Experimental,
        'Kongono\\OperationalEvents\\OperationalEventSeverity' => ApiStatus::Experimental,
        'Kongono\\SecurityAudit\\Diagnostic\\SecurityAuditDiagnostic' => ApiStatus::Internal,
        'Kongono\\SecurityAudit\\Exception\\ExceptionInterface' => ApiStatus::Experimental,
        'Kongono\\SecurityAudit\\Exception\\InvalidSecurityAuditSchemaException' => ApiStatus::Experimental,
        'Kongono\\SecurityAudit\\Exception\\InvalidSecurityAuditValueException' => ApiStatus::Experimental,
        'Kongono\\SecurityAudit\\Exception\\SecurityAuditException' => ApiStatus::Internal,
        'Kongono\\SecurityAudit\\Exception\\SecurityAuditRecordingException' => ApiStatus::Experimental,
        'Kongono\\SecurityAudit\\Internal\\SecurityAuditObservationFactory' => ApiStatus::Internal,
        'Kongono\\SecurityAudit\\Internal\\SecurityAuditValidation' => ApiStatus::Internal,
        'Kongono\\SecurityAudit\\SecurityAuditDefinition' => ApiStatus::Experimental,
        'Kongono\\SecurityAudit\\SecurityAuditEvidence' => ApiStatus::Experimental,
        'Kongono\\SecurityAudit\\SecurityAuditField' => ApiStatus::Experimental,
        'Kongono\\SecurityAudit\\SecurityAuditOutcome' => ApiStatus::Experimental,
        'Kongono\\SecurityAudit\\SecurityAuditRetentionIntent' => ApiStatus::Experimental,
        'Kongono\\SecurityAudit\\SecurityAuditRuntime' => ApiStatus::Experimental,
        'Kongono\\SecurityAudit\\SecurityAuditSchema' => ApiStatus::Experimental,
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
        self::assertCount(1358, $types);

        self::assertFalse(class_exists('Kongono\\Kernel\\Api\\Api'));
        self::assertFalse(enum_exists('Kongono\\Kernel\\Api\\ApiStatus'));

        foreach ($types as $type) {
            self::assertSame($type, $this->autoloadedClassLike($type));
        }

        $expected = array_keys(self::EXPECTED_STATUS);
        sort($expected);

        self::assertSame($expected, $types);
    }

    public function testConsolePackageBoundaryInventoryAndToolingAreExact(): void
    {
        $root = dirname(__DIR__, 2);
        $manifest = json_decode((string) file_get_contents($root . '/packages/console/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertSame('kongono/console', $manifest['name']);
        self::assertSame('Neutral Structured CLI contracts for Kongono Framework.', $manifest['description']);
        self::assertSame([
            'kongono/api-metadata' => '*',
            'kongono/diagnostics' => '*',
            'php' => '>=8.5.0 <8.6.0',
        ], $manifest['require']);
        self::assertIsArray($manifest['autoload']);
        self::assertIsArray($manifest['autoload']['psr-4']);
        self::assertIsArray($manifest['autoload-dev']);
        self::assertIsArray($manifest['autoload-dev']['psr-4']);
        self::assertSame(['Kongono\\Console\\' => 'src/'], $manifest['autoload']['psr-4']);
        self::assertSame(['Kongono\\Console\\Tests\\' => 'tests/'], $manifest['autoload-dev']['psr-4']);

        $sourceFiles = $this->recursivePhpFiles($root . '/packages/console/src');
        $testFiles = $this->recursivePhpFiles($root . '/packages/console/tests');
        self::assertCount(39, $sourceFiles);
        self::assertCount(15, $testFiles);

        $rootManifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($rootManifest);
        self::assertIsArray($rootManifest['require']);
        self::assertIsArray($rootManifest['autoload-dev']);
        self::assertIsArray($rootManifest['autoload-dev']['psr-4']);
        self::assertIsArray($rootManifest['scripts']);
        self::assertIsArray($rootManifest['scripts']['validate:packages']);
        self::assertSame('@dev', $rootManifest['require']['kongono/console']);
        self::assertSame('packages/console/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\Console\\Tests\\']);
        self::assertContains('composer --working-dir=packages/console validate --no-check-publish', $rootManifest['scripts']['validate:packages']);

        foreach (['.php-cs-fixer.dist.php', 'phpstan.neon.dist', 'phpunit.xml.dist'] as $configuration) {
            $contents = file_get_contents($root . '/' . $configuration);
            self::assertIsString($contents);
            self::assertSame(1, substr_count($contents, 'packages/console/src'));
            self::assertSame(1, substr_count($contents, 'packages/console/tests'));
        }

        foreach ($sourceFiles as $sourceFile) {
            $source = file_get_contents($sourceFile);
            self::assertIsString($source);
            self::assertSame(0, preg_match('/Kongono\\\\(?:Kernel|DependencyInjection|ApplicationGraph|Http|Routing|Web|PhpCgi|Database|Persistence|Chie|Hantei|Oshie)|Symfony|Reflection|ServiceLocator|Container|Registry|Presenter|\$_(?:SERVER|ENV|GET|POST|COOKIE|FILES|REQUEST)|getenv\s*\(|eval\s*\(/i', $source));
        }

        foreach ([
            'Kongono\\Console\\CommandCancellation',
            'Kongono\\Console\\CommandCleanup',
            'Kongono\\Console\\CommandHandler',
        ] as $interface) {
            $implementations = [];
            foreach ($this->discoverProductionTypes() as $type) {
                if ($type === $interface || !class_exists($type)) {
                    continue;
                }
                if ((new ReflectionClass($type))->implementsInterface($interface)) {
                    $implementations[] = $type;
                }
            }
            $expectedImplementations = match ($interface) {
                'Kongono\\Console\\CommandCancellation' => [
                    'Kongono\\ConsoleApplication\\Process\\NeverCancelled',
                ],
                'Kongono\\Console\\CommandHandler' => [
                    'Kongono\\ConsoleApplication\\Handler\\ApplicationBuildCommandHandler',
                    'Kongono\\ConsoleApplication\\Handler\\ApplicationGraphInspectCommandHandler',
                    'Kongono\\ConsoleApplication\\Handler\\ExtensionInspectCommandHandler',
                    'Kongono\\ConsoleApplication\\Handler\\ObservabilityCheckCommandHandler',
                    'Kongono\\ConsoleApplication\\Handler\\ObservabilityInspectCommandHandler',
                    'Kongono\\ConsoleApplication\\Handler\\SecurityCheckCommandHandler',
                    'Kongono\\ConsoleApplication\\Handler\\SecurityInspectCommandHandler',
                    'Kongono\\ConsoleCommands\\Handler\\CompositionInspectCommandHandler',
                    'Kongono\\ConsoleCommands\\Handler\\GraphInspectCommandHandler',
                    'Kongono\\ConsoleCommands\\Handler\\QualityInspectCommandHandler',
                ],
                default => [],
            };
            self::assertSame($expectedImplementations, $implementations, sprintf('%s must match the exact Phase 10G production inventory.', $interface));
        }
        self::assertTrue((new ReflectionClass('Kongono\\Console\\CommandResultRecord'))->implementsInterface('Kongono\\Console\\CommandResultData'));

        $identifiers = [];
        foreach ($this->discoverProductionTypes() as $type) {
            if (!enum_exists($type) || !(new ReflectionClass($type))->isEnum()) {
                continue;
            }
            foreach ($type::cases() as $case) {
                if ($case instanceof \BackedEnum && is_string($case->value) && 1 === preg_match('/^KGN-[A-Z0-9]+(?:-[A-Z0-9]+)*-[0-9]{4}$/D', $case->value)) {
                    $identifiers[] = $case->value;
                }
            }
        }
        self::assertCount(485, $identifiers);
        self::assertCount(485, array_unique($identifiers));
        $productionDiagnosticIdentifiers = [...$identifiers, ...array_keys(HanteiCheckEvidence::CHECKS)];
        self::assertCount(493, $productionDiagnosticIdentifiers);
        self::assertCount(493, array_unique($productionDiagnosticIdentifiers));
        self::assertSame([
            'KGN-CONSOLE-1001',
            'KGN-CONSOLE-1002',
            'KGN-CONSOLE-1003',
            'KGN-CONSOLE-1004',
            'KGN-CONSOLE-1005',
            'KGN-CONSOLE-1006',
            'KGN-CONSOLE-1007',
            'KGN-CONSOLE-1008',
            'KGN-CONSOLE-1009',
            'KGN-CONSOLE-1010',
        ], array_values(array_filter($identifiers, static fn(string $identifier): bool => 1 === preg_match('/^KGN-CONSOLE-1\\d{3}$/D', $identifier))));
        self::assertSame([
            'KGN-CONSOLE-BUILD-1001',
            'KGN-CONSOLE-BUILD-1002',
            'KGN-CONSOLE-BUILD-1003',
            'KGN-CONSOLE-BUILD-1004',
            'KGN-CONSOLE-BUILD-1005',
        ], array_values(array_filter($identifiers, static fn(string $identifier): bool => str_starts_with($identifier, 'KGN-CONSOLE-BUILD-'))));
        self::assertSame([
            'KGN-CONSOLE-RUNTIME-1001',
            'KGN-CONSOLE-RUNTIME-1002',
            'KGN-CONSOLE-RUNTIME-1003',
            'KGN-CONSOLE-RUNTIME-1004',
            'KGN-CONSOLE-RUNTIME-1005',
            'KGN-CONSOLE-RUNTIME-1006',
        ], array_values(array_filter($identifiers, static fn(string $identifier): bool => str_starts_with($identifier, 'KGN-CONSOLE-RUNTIME-'))));
        self::assertSame([
            'KGN-CONSOLE-COMMANDS-1001',
            'KGN-CONSOLE-COMMANDS-1002',
            'KGN-CONSOLE-COMMANDS-1003',
        ], array_values(array_filter($identifiers, static fn(string $identifier): bool => str_starts_with($identifier, 'KGN-CONSOLE-COMMANDS-'))));
        self::assertSame([
            'KGN-CONSOLE-PRESENTATION-1001',
            'KGN-CONSOLE-PRESENTATION-1002',
            'KGN-CONSOLE-PRESENTATION-1003',
            'KGN-CONSOLE-PRESENTATION-1004',
            'KGN-CONSOLE-PRESENTATION-1005',
        ], array_values(array_filter($identifiers, static fn(string $identifier): bool => str_starts_with($identifier, 'KGN-CONSOLE-PRESENTATION-'))));
        self::assertSame([
            'KGN-CONSOLE-APPLICATION-1001',
            'KGN-CONSOLE-APPLICATION-1002',
            'KGN-CONSOLE-APPLICATION-1003',
            'KGN-CONSOLE-APPLICATION-1004',
            'KGN-CONSOLE-APPLICATION-1005',
        ], array_values(array_filter($identifiers, static fn(string $identifier): bool => str_starts_with($identifier, 'KGN-CONSOLE-APPLICATION-'))));
    }

    public function testConsoleBuildBoundaryAndPhase8CContractsAreExact(): void
    {
        $root = dirname(__DIR__, 2);
        $manifest = json_decode((string) file_get_contents($root . '/packages/console-build/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertSame('kongono/console-build', $manifest['name']);
        self::assertSame([
            'ext-hash' => '*',
            'ext-json' => '*',
            'kongono/api-metadata' => '*',
            'kongono/console' => '*',
            'kongono/diagnostics' => '*',
            'php' => '>=8.5.0 <8.6.0',
        ], $manifest['require']);
        $autoload = $manifest['autoload'];
        $autoloadDev = $manifest['autoload-dev'];
        self::assertIsArray($autoload);
        self::assertIsArray($autoloadDev);
        self::assertSame(['Kongono\\ConsoleBuild\\' => 'src/'], $autoload['psr-4']);
        self::assertSame(['Kongono\\ConsoleBuild\\Tests\\' => 'tests/'], $autoloadDev['psr-4']);
        self::assertCount(16, $this->recursivePhpFiles($root . '/packages/console-build/src'));
        self::assertCount(10, $this->recursivePhpFiles($root . '/packages/console-build/tests'));

        $rootManifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($rootManifest);
        $rootRequire = $rootManifest['require'];
        $rootAutoloadDev = $rootManifest['autoload-dev'];
        $rootScripts = $rootManifest['scripts'];
        self::assertIsArray($rootRequire);
        self::assertIsArray($rootAutoloadDev);
        self::assertIsArray($rootScripts);
        $rootAutoloadDevPsr4 = $rootAutoloadDev['psr-4'];
        $validatePackages = $rootScripts['validate:packages'];
        self::assertIsArray($rootAutoloadDevPsr4);
        self::assertIsArray($validatePackages);
        self::assertSame('@dev', $rootRequire['kongono/console-build']);
        self::assertSame('packages/console-build/tests/', $rootAutoloadDevPsr4['Kongono\\ConsoleBuild\\Tests\\']);
        self::assertContains('composer --working-dir=packages/console-build validate --no-check-publish', $validatePackages);
        foreach (['.php-cs-fixer.dist.php', 'phpstan.neon.dist', 'phpunit.xml.dist'] as $configuration) {
            $contents = file_get_contents($root . '/' . $configuration);
            self::assertIsString($contents);
            self::assertSame(1, substr_count($contents, 'packages/console-build/src'));
            self::assertSame(1, substr_count($contents, 'packages/console-build/tests'));
        }

        self::assertSame(['__construct', 'name', 'requirement', 'sensitivity'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\Console\\CommandInputDefinition')));
        self::assertSame(['__construct', 'definition', 'hasInput', 'input', 'inputs'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\Console\\CommandSpecification')));
        self::assertSame(['__construct', 'command', 'commands', 'hasCommand'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\Console\\CommandSpecificationSet')));
        self::assertSame(['accepted', 'failure', 'invocation', 'isAccepted', 'rejected'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\Console\\CommandParseResult')));
        self::assertSame(['parse'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\Console\\CommandTokenParser')));
        self::assertSame(['__construct', 'artifactSha256', 'builderImplementationSha256', 'canonicalBytes', 'model', 'modelSha256', 'sourceSha256'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\ConsoleBuild\\Build\\ConsoleArtifact')));
        self::assertSame(['build'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\ConsoleBuild\\Build\\ConsoleArtifactBuilder')));
        self::assertSame(['validateCurrent', 'validateStructure'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\ConsoleBuild\\Build\\ConsoleArtifactValidator')));
        self::assertSame(['__construct', 'specifications'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\ConsoleBuild\\Model\\ConsoleModel')));
        self::assertCount(16, \Kongono\ConsoleBuild\Internal\ConsoleBuildImplementation::BUILDER_PATHS);

        $source = '';
        foreach ($this->recursivePhpFiles($root . '/packages/console-build/src') as $file) {
            $bytes = file_get_contents($file);
            self::assertIsString($bytes);
            $source .= $bytes;
        }
        self::assertSame(0, preg_match('/Kongono\\\\(?:Kernel|Composition|ApplicationGraph|DependencyInjection|Http|Routing|Web|PhpCgi|Database|Persistence|Chie|Hantei|Oshie)|Symfony\\\\Component\\\\Console|Reflection|ServiceLocator|Registry|Presenter|Dispatcher|Executor|\$_(?:SERVER|ENV)|getenv\s*\(|eval\s*\(/i', $source));
        foreach (['bin', 'src/Execution', 'src/Presentation', 'src/Runtime', 'src/Registry'] as $absent) {
            self::assertDirectoryDoesNotExist($root . '/packages/console-build/' . $absent);
        }
    }

    public function testConsoleRuntimeBoundaryAndPhase8DContractsAreExact(): void
    {
        $root = dirname(__DIR__, 2);
        $manifest = json_decode((string) file_get_contents($root . '/packages/console-runtime/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertSame('kongono/console-runtime', $manifest['name']);
        self::assertSame('Operation-scoped Structured CLI command execution for Kongono Framework.', $manifest['description']);
        self::assertSame([
            'kongono/api-metadata' => '*',
            'kongono/console' => '*',
            'kongono/diagnostics' => '*',
            'php' => '>=8.5.0 <8.6.0',
        ], $manifest['require']);
        self::assertIsArray($manifest['autoload']);
        self::assertIsArray($manifest['autoload-dev']);
        self::assertIsArray($manifest['autoload']['psr-4']);
        self::assertIsArray($manifest['autoload-dev']['psr-4']);
        self::assertSame(['Kongono\\ConsoleRuntime\\' => 'src/'], $manifest['autoload']['psr-4']);
        self::assertSame(['Kongono\\ConsoleRuntime\\Tests\\' => 'tests/'], $manifest['autoload-dev']['psr-4']);
        self::assertCount(9, $this->recursivePhpFiles($root . '/packages/console-runtime/src'));
        self::assertCount(9, $this->recursivePhpFiles($root . '/packages/console-runtime/tests'));

        $rootManifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($rootManifest);
        self::assertIsArray($rootManifest['require']);
        self::assertIsArray($rootManifest['autoload-dev']);
        self::assertIsArray($rootManifest['autoload-dev']['psr-4']);
        self::assertIsArray($rootManifest['scripts']);
        self::assertIsArray($rootManifest['scripts']['validate:packages']);
        self::assertSame('@dev', $rootManifest['require']['kongono/console-runtime']);
        self::assertSame('packages/console-runtime/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\ConsoleRuntime\\Tests\\']);
        self::assertContains('composer --working-dir=packages/console-runtime validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        foreach (['.php-cs-fixer.dist.php', 'phpstan.neon.dist', 'phpunit.xml.dist'] as $configuration) {
            $contents = file_get_contents($root . '/' . $configuration);
            self::assertIsString($contents);
            self::assertSame(1, substr_count($contents, 'packages/console-runtime/src'));
            self::assertSame(1, substr_count($contents, 'packages/console-runtime/tests'));
        }

        self::assertSame(['release'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\Console\\CommandCleanup')));
        self::assertSame(['cancellation', 'defer', 'invocation', 'retire', 'state'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\Console\\CommandOperation')));
        self::assertSame(['__construct', 'handler', 'identifier'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\ConsoleRuntime\\CommandHandlerBinding')));
        self::assertSame(['__construct', 'binding', 'bindings', 'hasBinding'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\ConsoleRuntime\\CommandHandlerSet')));
        self::assertSame(['__construct', 'cancellation', 'defer', 'invocation', 'retire', 'state'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\ConsoleRuntime\\CommandExecutionOperation')));
        self::assertSame(['__construct', 'dispatch'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\ConsoleRuntime\\CommandDispatcher')));
        self::assertSame(['cleanupFailures', 'forFailures', 'primaryFailure'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\ConsoleRuntime\\Exception\\CommandExecutionException')));
        self::assertSame(['binding', 'handlerSet', 'operationLifecycle', 'reentrantDispatch'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\ConsoleRuntime\\Exception\\InvalidCommandRuntimeValueException')));

        $operation = new ReflectionClass('Kongono\\ConsoleRuntime\\CommandExecutionOperation');
        self::assertTrue($operation->isFinal());
        self::assertTrue($operation->implementsInterface('Kongono\\Console\\CommandOperation'));
        $binding = new ReflectionClass('Kongono\\ConsoleRuntime\\CommandHandlerBinding');
        $set = new ReflectionClass('Kongono\\ConsoleRuntime\\CommandHandlerSet');
        self::assertTrue($binding->isFinal());
        self::assertTrue($binding->isReadOnly());
        self::assertTrue($set->isFinal());
        self::assertTrue($set->isReadOnly());

        $source = '';
        foreach ($this->recursivePhpFiles($root . '/packages/console-runtime/src') as $file) {
            $bytes = file_get_contents($file);
            self::assertIsString($bytes);
            $source .= $bytes;
        }
        self::assertSame(0, preg_match('/Kongono\\\\(?:ConsoleBuild|Kernel|Composition|ApplicationGraph|DependencyInjection|Http|Routing|Web|PhpCgi|Database|Persistence|Chie|Hantei|Oshie)|Symfony\\\\Component\\\\Console|Reflection|ServiceLocator|Container|Registry|Presenter|Middleware|\\$_(?:SERVER|ENV|GET|POST|COOKIE|FILES|REQUEST)|\\b(?:getenv|putenv|chdir|getcwd|fopen|file_get_contents|print|printf|echo|exit|die|eval)\\s*\\(/i', $source));
        foreach (['bin', 'src/Build', 'src/Presentation', 'src/Process', 'src/Registry', 'src/Command'] as $absent) {
            self::assertDirectoryDoesNotExist($root . '/packages/console-runtime/' . $absent);
        }
        $packageManifestPaths = glob($root . '/packages/*/composer.json');
        self::assertIsArray($packageManifestPaths);
        foreach ($packageManifestPaths as $packageManifestPath) {
            $normalizedManifestPath = str_replace('\\', '/', $packageManifestPath);
            if (str_ends_with($normalizedManifestPath, '/console-runtime/composer.json')
                || str_ends_with($normalizedManifestPath, '/console-commands/composer.json')
                || str_ends_with($normalizedManifestPath, '/console-application/composer.json')) {
                continue;
            }
            $otherManifest = json_decode((string) file_get_contents($packageManifestPath), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($otherManifest);
            self::assertIsArray($otherManifest['require']);
            self::assertArrayNotHasKey('kongono/console-runtime', $otherManifest['require']);
        }
        foreach (['8e', '8f', '8g', '8h', 'kongo'] as $absent) {
            self::assertFileDoesNotExist($root . '/packages/console-runtime/' . $absent);
        }
    }

    public function testConsolePresentationBoundaryAndPhase8EContractsAreExact(): void
    {
        $root = dirname(__DIR__, 2);
        $manifest = json_decode((string) file_get_contents($root . '/packages/console-presentation/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertSame('kongono/console-presentation', $manifest['name']);
        self::assertSame([
            'kongono/api-metadata' => '*',
            'kongono/console' => '*',
            'kongono/diagnostics' => '*',
            'php' => '>=8.5.0 <8.6.0',
        ], $manifest['require']);
        self::assertIsArray($manifest['autoload']);
        self::assertIsArray($manifest['autoload-dev']);
        self::assertIsArray($manifest['autoload']['psr-4']);
        self::assertIsArray($manifest['autoload-dev']['psr-4']);
        self::assertSame(['Kongono\\ConsolePresentation\\' => 'src/'], $manifest['autoload']['psr-4']);
        self::assertSame(['Kongono\\ConsolePresentation\\Tests\\' => 'tests/'], $manifest['autoload-dev']['psr-4']);
        self::assertCount(16, $this->recursivePhpFiles($root . '/packages/console-presentation/src'));
        self::assertCount(10, $this->recursivePhpFiles($root . '/packages/console-presentation/tests'));

        $rootManifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($rootManifest);
        self::assertIsArray($rootManifest['require']);
        self::assertIsArray($rootManifest['autoload-dev']);
        self::assertIsArray($rootManifest['autoload-dev']['psr-4']);
        self::assertIsArray($rootManifest['scripts']);
        self::assertIsArray($rootManifest['scripts']['validate:packages']);
        self::assertSame('@dev', $rootManifest['require']['kongono/console-presentation']);
        self::assertSame('packages/console-presentation/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\ConsolePresentation\\Tests\\']);
        self::assertContains('composer --working-dir=packages/console-presentation validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        foreach (['.php-cs-fixer.dist.php', 'phpstan.neon.dist', 'phpunit.xml.dist'] as $configuration) {
            $contents = file_get_contents($root . '/' . $configuration);
            self::assertIsString($contents);
            self::assertSame(1, substr_count($contents, 'packages/console-presentation/src'));
            self::assertSame(1, substr_count($contents, 'packages/console-presentation/tests'));
        }

        self::assertSame(['Succeeded', 'InvalidInput', 'Precondition', 'Denied', 'Capability', 'Infrastructure', 'Cancelled'], array_column(\Kongono\ConsolePresentation\CommandExitStatus::cases(), 'name'));
        self::assertSame([0, 2, 3, 4, 5, 6, 7], array_column(\Kongono\ConsolePresentation\CommandExitStatus::cases(), 'value'));
        self::assertSame(['text/plain;charset=utf-8', 'application/vnd.kongono.command-result+json;version=1', 'application/vnd.kongono.command-help+json;version=1'], array_column(\Kongono\ConsolePresentation\CommandMediaType::cases(), 'value'));
        self::assertSame(['write'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\ConsolePresentation\\CommandOutputPort')));
        self::assertSame(['__construct', 'exitStatus', 'mediaType', 'stderr', 'stdout'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\ConsolePresentation\\CommandPresentation')));
        self::assertSame(['deliver'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\ConsolePresentation\\CommandPresentationAdapter')));
        self::assertSame(['presentCommandHelp', 'presentHelp', 'presentResult'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\ConsolePresentation\\HumanCommandPresenter')));
        self::assertSame(['presentCommandHelp', 'presentHelp', 'presentResult'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\ConsolePresentation\\MachineCommandPresenter')));

        $source = '';
        foreach ($this->recursivePhpFiles($root . '/packages/console-presentation/src') as $file) {
            $bytes = file_get_contents($file);
            self::assertIsString($bytes);
            $source .= $bytes;
        }
        self::assertSame(0, preg_match('/Kongono\\\\(?:ConsoleRuntime|ConsoleBuild|Kernel|Composition|ApplicationGraph|DependencyInjection|Http|Routing|Web|PhpCgi|Database|Persistence|Chie|Hantei|Oshie)|Symfony\\\\Component\\\\Console|Reflection|ServiceLocator|Container|Registry|Middleware|\$_(?:SERVER|ENV|GET|POST|COOKIE|FILES|REQUEST)|\$argv|\b(?:getenv|putenv|chdir|getcwd|fopen|file_get_contents|print|printf|echo|exit|die|eval)\s*\(/i', $source));
        $packageManifestPaths = glob($root . '/packages/*/composer.json');
        self::assertIsArray($packageManifestPaths);
        foreach ($packageManifestPaths as $packageManifestPath) {
            $normalizedManifestPath = str_replace('\\', '/', $packageManifestPath);
            if (str_ends_with($normalizedManifestPath, '/console-presentation/composer.json')
                || str_ends_with($normalizedManifestPath, '/console-application/composer.json')) {
                continue;
            }
            $otherManifest = json_decode((string) file_get_contents($packageManifestPath), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($otherManifest);
            self::assertIsArray($otherManifest['require']);
            self::assertArrayNotHasKey('kongono/console-presentation', $otherManifest['require']);
        }
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
        self::assertSame(['__construct', 'applicationClass', 'canonicalBytes', 'compositionArtifactSha256', 'compositionSourceVersion', 'generatedNamespace', 'generatorImplementationSha256', 'modelSha256', 'operationClass', 'wiringSha256'], $this->declaredPublicMethodNames(new ReflectionClass(GeneratedWiringArtifact::class)));
        self::assertSame(['generate'], $this->declaredPublicMethodNames(new ReflectionClass(GeneratedWiringGenerator::class)));
        self::assertSame(['publish'], $this->declaredPublicMethodNames(new ReflectionClass(GeneratedWiringPublisher::class)));
        self::assertSame(['validateCurrent', 'validateStructure'], $this->declaredPublicMethodNames(new ReflectionClass(GeneratedWiringValidator::class)));
        self::assertSame(['beginOperation', 'isRetired', 'shutdown'], $this->declaredPublicMethodNames(new ReflectionClass(GeneratedApplicationBoundary::class)));
        self::assertSame(['beginOperationWithInputs', 'expectedOperationInputContracts'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\Composition\\Runtime\\GeneratedOperationInputApplicationBoundary')));
        self::assertSame(['close', 'isClosed'], $this->declaredPublicMethodNames(new ReflectionClass(GeneratedOperationBoundary::class)));
        self::assertSame(['count', 'none', 'of', 'validateCount', 'validatedAt'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\Composition\\Runtime\\GeneratedOperationInputs')));
        self::assertSame(['loadCurrent'], $this->declaredPublicMethodNames(new ReflectionClass(GeneratedWiringLoader::class)));
        self::assertSame(['__construct', 'applicationClass', 'isStarted', 'operationClass', 'start', 'wiringSha256'], $this->declaredPublicMethodNames(new ReflectionClass(LoadedGeneratedWiring::class)));
        self::assertSame(['build', 'check', 'plan'], $this->declaredPublicMethodNames(new ReflectionClass(ApplicationBuildWorkflow::class)));
        self::assertSame(['__construct', 'artifactSha256', 'compositionArtifactBytes', 'compositionArtifactPath', 'generatedPhpPath', 'generatedWiringSha256'], $this->declaredPublicMethodNames(new ReflectionClass(CurrentApplicationBuild::class)));
        self::assertSame(['__construct', 'artifactSha256', 'compositionArtifactBasename', 'generatedPhpBasename', 'generatedWiringSha256', 'publicationRequired'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\Composition\\Workflow\\PlannedApplicationBuild')));

        $generatedArtifact = new ReflectionClass(GeneratedWiringArtifact::class);
        self::assertTrue($generatedArtifact->isFinal());
        self::assertTrue($generatedArtifact->isReadOnly());
        self::assertSame(['applicationClass', 'canonicalBytes', 'compositionArtifactSha256', 'compositionSourceVersion', 'generatedNamespace', 'generatorImplementationSha256', 'modelSha256', 'operationClass', 'wiringSha256'], $this->declaredPropertyNames($generatedArtifact));
        $artifactConstructor = $generatedArtifact->getConstructor();
        self::assertNotNull($artifactConstructor);
        self::assertSame(['string', 'string', 'string', 'string', 'string', 'int', 'string', 'string', 'string'], array_map(static fn(\ReflectionParameter $parameter): string => (string) $parameter->getType(), $artifactConstructor->getParameters()));

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
            'KGN-COMPOSITION-1019',
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
            'Kongono\\Composition\\Exception\\InvalidGeneratedOperationInputsException',
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

    public function testDatabasePackagesContractsDependenciesDiagnosticsAndNativeIsolationAreExact(): void
    {
        $root = dirname(__DIR__, 2);
        $surfaces = [
            'Kongono\\Database\\DatabaseConnector' => ['openSession'],
            'Kongono\\Database\\DatabaseExecutor' => ['execute'],
            'Kongono\\Database\\DatabaseSession' => ['begin', 'capabilities', 'close', 'isClosed'],
            'Kongono\\Database\\DatabaseTransaction' => ['close', 'commit', 'isClosed', 'rollback', 'state'],
            'Kongono\\Database\\RowStream' => ['close', 'columns', 'fetch', 'isClosed'],
            'Kongono\\Database\\CommandResult' => ['__construct', 'affectedRows'],
            'Kongono\\Database\\DatabaseColumn' => ['__construct', 'label', 'ordinal'],
            'Kongono\\Database\\DatabaseParameter' => ['__construct', 'name', 'value'],
            'Kongono\\Database\\DatabaseStatement' => ['__construct', 'expectation', 'parameters', 'sql'],
            'Kongono\\Database\\DatabaseValue' => ['binary', 'boolean', 'float', 'integer', 'kind', 'null', 'text', 'value'],
            'Kongono\\Database\\DatabaseCapabilities' => ['__construct', 'all', 'has'],
            'Kongono\\Database\\DatabaseRow' => ['__construct', 'at', 'columns', 'named', 'values'],
            'Kongono\\Database\\TransactionOptions' => ['__construct', 'access', 'isolation'],
            'Kongono\\DatabasePdoPgsql\\PdoPgsqlConfiguration' => ['__construct', 'createSession'],
            'Kongono\\DatabasePdoPgsql\\PdoPgsqlConnector' => ['__construct', 'openSession'],
        ];
        foreach ($surfaces as $type => $methods) {
            self::assertSame($methods, $this->declaredPublicMethodNames(new ReflectionClass($type)));
        }

        self::assertSame(
            array_map(static fn(int $ordinal): string => sprintf('KGN-DATABASE-%04d', $ordinal), range(1001, 1011)),
            array_map(static fn(DatabaseDiagnostic $diagnostic): string => $diagnostic->value, DatabaseDiagnostic::cases()),
        );

        $databaseManifest = json_decode((string) file_get_contents($root . '/packages/database/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        $adapterManifest = json_decode((string) file_get_contents($root . '/packages/database-pdo-pgsql/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($databaseManifest);
        self::assertIsArray($adapterManifest);
        self::assertIsArray($databaseManifest['require']);
        self::assertIsArray($databaseManifest['autoload']);
        self::assertIsArray($databaseManifest['autoload']['psr-4']);
        self::assertIsArray($adapterManifest['require']);
        self::assertIsArray($adapterManifest['autoload']);
        self::assertIsArray($adapterManifest['autoload']['psr-4']);
        self::assertSame(['kongono/api-metadata' => '*', 'kongono/diagnostics' => '*', 'php' => '>=8.5.0 <8.6.0'], $databaseManifest['require']);
        self::assertSame(['ext-pdo' => '*', 'ext-pdo_pgsql' => '*', 'kongono/database' => '*', 'php' => '>=8.5.0 <8.6.0'], $adapterManifest['require']);
        self::assertArrayNotHasKey('kongono/database-pdo-pgsql', $databaseManifest['require']);
        self::assertSame(['Kongono\\Database\\' => 'src/'], $databaseManifest['autoload']['psr-4']);
        self::assertSame(['Kongono\\DatabasePdoPgsql\\' => 'src/'], $adapterManifest['autoload']['psr-4']);

        $rootManifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($rootManifest);
        self::assertIsArray($rootManifest['require']);
        self::assertIsArray($rootManifest['autoload-dev']);
        self::assertIsArray($rootManifest['autoload-dev']['psr-4']);
        self::assertIsArray($rootManifest['scripts']);
        self::assertIsArray($rootManifest['scripts']['validate:packages']);
        self::assertSame('@dev', $rootManifest['require']['kongono/database']);
        self::assertSame('@dev', $rootManifest['require']['kongono/database-pdo-pgsql']);
        self::assertSame('packages/database/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\Database\\Tests\\']);
        self::assertSame('packages/database-pdo-pgsql/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\DatabasePdoPgsql\\Tests\\']);
        self::assertSame('@php tests/External/Database/run.php', $rootManifest['scripts']['acceptance:phase6']);
        self::assertSame('@php tests/External/Persistence/run.php', $rootManifest['scripts']['acceptance:phase7']);
        self::assertContains('composer --working-dir=packages/database validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/database-pdo-pgsql validate --no-check-publish', $rootManifest['scripts']['validate:packages']);

        $databaseFiles = $this->recursivePhpFiles($root . '/packages/database/src');
        $adapterFiles = $this->recursivePhpFiles($root . '/packages/database-pdo-pgsql/src');
        self::assertCount(34, $databaseFiles);
        self::assertCount(6, $adapterFiles);
        foreach ($databaseFiles as $file) {
            $source = file_get_contents($file);
            self::assertIsString($source);
            self::assertSame(0, preg_match('/\\bPDO(?:Statement|Exception)?\\b|pdo_pgsql|pgsql:/i', $source));
        }

        foreach (['Kongono\\DatabasePdoPgsql\\PdoPgsqlConfiguration', 'Kongono\\DatabasePdoPgsql\\PdoPgsqlConnector'] as $type) {
            $reflection = new ReflectionClass($type);
            self::assertTrue($reflection->isFinal());
            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                $signature = (string) $method->getReturnType();
                foreach ($method->getParameters() as $parameter) {
                    $signature .= ' ' . (string) $parameter->getType();
                }
                self::assertSame(0, preg_match('/\\bPDO(?:Statement|Exception)?\\b/', $signature));
            }
        }

        $configurationConstructor = (new ReflectionClass('Kongono\\DatabasePdoPgsql\\PdoPgsqlConfiguration'))->getConstructor();
        self::assertNotNull($configurationConstructor);
        $sensitive = [];
        foreach ($configurationConstructor->getParameters() as $parameter) {
            if ([] !== $parameter->getAttributes(\SensitiveParameter::class)) {
                $sensitive[] = $parameter->getName();
            }
        }
        self::assertSame(['host', 'database', 'username', 'password'], $sensitive);

        foreach ([
            'Kongono\\Database\\Exception\\InvalidDatabaseValueException',
            'Kongono\\Database\\Exception\\InvalidDatabaseStateException',
            'Kongono\\Database\\Exception\\UnsupportedDatabaseCapabilityException',
            'Kongono\\Database\\Exception\\DatabaseConnectionException',
            'Kongono\\Database\\Exception\\DatabaseBindingException',
            'Kongono\\Database\\Exception\\DatabaseExecutionException',
            'Kongono\\Database\\Exception\\DatabaseResultException',
            'Kongono\\Database\\Exception\\DatabaseTransactionBeginException',
            'Kongono\\Database\\Exception\\DatabaseTransactionCommitException',
            'Kongono\\Database\\Exception\\DatabaseTransactionRollbackException',
            'Kongono\\Database\\Exception\\DatabaseCleanupException',
            'Kongono\\Database\\Exception\\CombinedDatabaseFailureException',
        ] as $failure) {
            $reflection = new ReflectionClass($failure);
            self::assertTrue($reflection->isFinal());
            self::assertTrue($reflection->implementsInterface(DatabaseExceptionInterface::class));
        }
    }

    public function testAuthenticationPackageContractsDependenciesShapesAndIsolationAreExact(): void
    {
        $root = dirname(__DIR__, 2);
        $manifest = json_decode((string) file_get_contents($root . '/packages/authentication/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertSame('kongono/authentication', $manifest['name']);
        self::assertSame('Deterministic explicit authentication orchestration for Kongono Framework.', $manifest['description']);
        self::assertSame('library', $manifest['type']);
        self::assertSame([
            'php' => '>=8.5.0 <8.6.0',
            'kongono/api-metadata' => '*',
            'kongono/diagnostics' => '*',
            'kongono/identity-access' => '*',
        ], $manifest['require']);
        self::assertIsArray($manifest['autoload']);
        self::assertIsArray($manifest['autoload-dev']);
        self::assertSame(['Kongono\\Authentication\\' => 'src/'], $manifest['autoload']['psr-4']);
        self::assertSame(['Kongono\\Authentication\\Tests\\' => 'tests/'], $manifest['autoload-dev']['psr-4']);

        $rootManifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($rootManifest);
        self::assertIsArray($rootManifest['require']);
        self::assertIsArray($rootManifest['autoload-dev']);
        self::assertIsArray($rootManifest['autoload-dev']['psr-4']);
        self::assertIsArray($rootManifest['scripts']);
        self::assertIsArray($rootManifest['scripts']['validate:packages']);
        self::assertSame('@dev', $rootManifest['require']['kongono/authentication']);
        self::assertSame('packages/authentication/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\Authentication\\Tests\\']);
        self::assertContains('composer --working-dir=packages/authentication validate --no-check-publish', $rootManifest['scripts']['validate:packages']);

        $packageManifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($packageManifests);
        self::assertCount(75, $packageManifests);
        foreach ($packageManifests as $packageManifestPath) {
            $packageManifest = json_decode((string) file_get_contents($packageManifestPath), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($packageManifest);
            self::assertIsArray($packageManifest['require']);
            if (in_array($packageManifest['name'], ['kongono/agent-admission', 'kongono/authentication', 'kongono/security-http', 'kongono/security-runtime'], true)) {
                continue;
            }
            self::assertArrayNotHasKey('kongono/authentication', $packageManifest['require']);
        }

        $sourceFiles = $this->recursivePhpFiles($root . '/packages/authentication/src');
        self::assertCount(17, $sourceFiles);
        foreach ($sourceFiles as $sourceFile) {
            $source = file_get_contents($sourceFile);
            self::assertIsString($source);
            self::assertSame(1, substr_count($source, 'declare(strict_types=1);'));
            self::assertSame(1, substr_count($source, '#[Api('));
            self::assertSame(0, preg_match('/\$_(?:SERVER|ENV|COOKIE|SESSION)|\$GLOBALS|\b(?:getenv|putenv|time|microtime|date|class_exists|interface_exists|trait_exists|enum_exists|spl_autoload_register|file_get_contents|fopen|curl_exec|stream_socket_client|serialize|unserialize|json_encode)\s*\(/', $source));
            self::assertSame(0, preg_match('/use Kongono\\\\(?:ApplicationGraph|Chie|Composition|Console|Database|DependencyInjection|Hantei|Http|Kernel|Knowledge|Oshie|Persistence|PhpCgi|Routing|Web)\\\\/', $source));
            self::assertSame(0, preg_match('/use Kongono\\\\IdentityAccess\\\\(?:Action|AuthorizationDecision|Resource|SecurityContext)/', $source));
            self::assertSame(0, preg_match('/\b(?:JsonSerializable|ArrayAccess|Iterator|__destruct|__serialize|__unserialize|supports|priority|fallback|retry)\b/', $source));
        }

        $types = [
            AuthenticationEvidence::class => ['AuthenticationEvidence.php', ApiStatus::Experimental],
            AuthenticationFacts::class => ['AuthenticationFacts.php', ApiStatus::Experimental],
            AuthenticationInstant::class => ['AuthenticationInstant.php', ApiStatus::Experimental],
            AuthenticationMechanism::class => ['AuthenticationMechanism.php', ApiStatus::Experimental],
            AuthenticationOrchestrator::class => ['AuthenticationOrchestrator.php', ApiStatus::Experimental],
            AuthenticationRequest::class => ['AuthenticationRequest.php', ApiStatus::Experimental],
            AuthenticationRequirement::class => ['AuthenticationRequirement.php', ApiStatus::Experimental],
            AuthenticationStrength::class => ['AuthenticationStrength.php', ApiStatus::Experimental],
            AuthenticationVerification::class => ['AuthenticationVerification.php', ApiStatus::Experimental],
            AuthenticationVerificationOperation::class => ['AuthenticationVerificationOperation.php', ApiStatus::Experimental],
            AuthenticationVerifier::class => ['AuthenticationVerifier.php', ApiStatus::Experimental],
            AuthenticationDiagnostic::class => ['Diagnostic/AuthenticationDiagnostic.php', ApiStatus::Internal],
            AuthenticationCleanupException::class => ['Exception/AuthenticationCleanupException.php', ApiStatus::Experimental],
            AuthenticationException::class => ['Exception/AuthenticationException.php', ApiStatus::Internal],
            AuthenticationOperationAndCleanupException::class => ['Exception/AuthenticationOperationAndCleanupException.php', ApiStatus::Experimental],
            AuthenticationExceptionInterface::class => ['Exception/ExceptionInterface.php', ApiStatus::Public],
            InvalidAuthenticationValueException::class => ['Exception/InvalidAuthenticationValueException.php', ApiStatus::Experimental],
        ];
        foreach ($types as $type => [$relativePath, $status]) {
            $reflection = new ReflectionClass($type);
            $fileName = $reflection->getFileName();
            self::assertIsString($fileName);
            self::assertSame(
                str_replace('\\', '/', $root . '/packages/authentication/src/' . $relativePath),
                str_replace('\\', '/', $fileName),
            );
            $attributes = $reflection->getAttributes(Api::class);
            self::assertCount(1, $attributes);
            self::assertSame($status, $attributes[0]->newInstance()->status);
        }

        self::assertSame(['mechanism'], $this->declaredPublicMethodNames(new ReflectionClass(AuthenticationEvidence::class)));
        self::assertSame(['__construct', 'authenticatedAt', 'mechanism', 'principal', 'strength', 'validUntil'], $this->declaredPublicMethodNames(new ReflectionClass(AuthenticationFacts::class)));
        self::assertSame(['__construct', 'elapsedSince', 'isAfter', 'seconds'], $this->declaredPublicMethodNames(new ReflectionClass(AuthenticationInstant::class)));
        self::assertSame(['__construct', 'equals', 'value'], $this->declaredPublicMethodNames(new ReflectionClass(AuthenticationMechanism::class)));
        self::assertSame(['__construct', 'authenticate'], $this->declaredPublicMethodNames(new ReflectionClass(AuthenticationOrchestrator::class)));
        self::assertSame(['__construct', 'evidence', 'now', 'requirement'], $this->declaredPublicMethodNames(new ReflectionClass(AuthenticationRequest::class)));
        self::assertSame(['__construct', 'maximumAgeSeconds', 'minimumStrength'], $this->declaredPublicMethodNames(new ReflectionClass(AuthenticationRequirement::class)));
        self::assertSame(['__construct', 'level', 'satisfies'], $this->declaredPublicMethodNames(new ReflectionClass(AuthenticationStrength::class)));
        self::assertSame(['absent', 'ambiguous', 'authenticated', 'expired', 'facts', 'invalid', 'outcome', 'replayed', 'unavailable', 'unsupported'], $this->declaredPublicMethodNames(new ReflectionClass(AuthenticationVerification::class)));
        self::assertSame(['retire', 'verify'], $this->declaredPublicMethodNames(new ReflectionClass(AuthenticationVerificationOperation::class)));
        self::assertSame(['begin', 'mechanism'], $this->declaredPublicMethodNames(new ReflectionClass(AuthenticationVerifier::class)));
        self::assertSame(['cases', 'category', 'context', 'from', 'remediation', 'title', 'tryFrom'], $this->declaredPublicMethodNames(new ReflectionClass(AuthenticationDiagnostic::class)));
        self::assertSame(['diagnosticContext', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle'], $this->declaredPublicMethodNames(new ReflectionClass(AuthenticationExceptionInterface::class)));
        self::assertSame(['__toString', 'diagnosticContext', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle'], $this->declaredPublicMethodNames(new ReflectionClass(AuthenticationException::class)));
        self::assertSame(['cleanupFailure', 'from'], $this->declaredPublicMethodNames(new ReflectionClass(AuthenticationCleanupException::class)));
        self::assertSame(['cleanupFailure', 'from', 'operationFailure'], $this->declaredPublicMethodNames(new ReflectionClass(AuthenticationOperationAndCleanupException::class)));
        self::assertSame(['evidence', 'freshness', 'registration', 'strength', 'validation', 'verification'], $this->declaredPublicMethodNames(new ReflectionClass(InvalidAuthenticationValueException::class)));

        self::assertSame(['authenticatedAt', 'mechanism', 'principal', 'strength', 'validUntil'], $this->declaredPropertyNames(new ReflectionClass(AuthenticationFacts::class)));
        self::assertSame(['seconds'], $this->declaredPropertyNames(new ReflectionClass(AuthenticationInstant::class)));
        self::assertSame(['value'], $this->declaredPropertyNames(new ReflectionClass(AuthenticationMechanism::class)));
        self::assertSame(['verifiers'], $this->declaredPropertyNames(new ReflectionClass(AuthenticationOrchestrator::class)));
        self::assertSame(['evidence', 'now', 'requirement'], $this->declaredPropertyNames(new ReflectionClass(AuthenticationRequest::class)));
        self::assertSame(['maximumAgeSeconds', 'minimumStrength'], $this->declaredPropertyNames(new ReflectionClass(AuthenticationRequirement::class)));
        self::assertSame(['level'], $this->declaredPropertyNames(new ReflectionClass(AuthenticationStrength::class)));
        self::assertSame(['facts', 'outcome'], $this->declaredPropertyNames(new ReflectionClass(AuthenticationVerification::class)));

        self::assertSame([
            'KGN-AUTHENTICATION-1001',
            'KGN-AUTHENTICATION-1002',
            'KGN-AUTHENTICATION-1003',
            'KGN-AUTHENTICATION-1004',
            'KGN-AUTHENTICATION-1005',
            'KGN-AUTHENTICATION-1006',
            'KGN-AUTHENTICATION-1007',
            'KGN-AUTHENTICATION-1008',
        ], array_column(AuthenticationDiagnostic::cases(), 'value'));
        self::assertContains(AuthenticationExceptionInterface::class, (new ReflectionClass(AuthenticationException::class))->getInterfaceNames());
        foreach ([AuthenticationCleanupException::class, AuthenticationOperationAndCleanupException::class, InvalidAuthenticationValueException::class] as $failure) {
            self::assertContains(AuthenticationExceptionInterface::class, (new ReflectionClass($failure))->getInterfaceNames());
        }

        $productionCount = 0;
        $packageTestCount = 0;
        $packageDirectories = glob($root . '/packages/*', GLOB_ONLYDIR);
        self::assertIsArray($packageDirectories);
        foreach ($packageDirectories as $packageDirectory) {
            if (is_dir($packageDirectory . '/src')) {
                $productionCount += count($this->recursivePhpFiles($packageDirectory . '/src'));
            }
            if (is_dir($packageDirectory . '/tests')) {
                $packageTestCount += count($this->recursivePhpFiles($packageDirectory . '/tests'));
            }
        }
        self::assertSame(1358, $productionCount);
        self::assertSame(686, $packageTestCount);
        self::assertCount(80, $this->recursivePhpFiles($root . '/tests'));
    }

    public function testIdentityAccessPackageContractsDependenciesShapesAndIsolationAreExact(): void
    {
        $root = dirname(__DIR__, 2);
        $manifest = json_decode((string) file_get_contents($root . '/packages/identity-access/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertSame('kongono/identity-access', $manifest['name']);
        self::assertSame('Neutral identity and access contracts for Kongono Framework.', $manifest['description']);
        self::assertSame(
            ['kongono/api-metadata' => '*', 'kongono/diagnostics' => '*', 'php' => '>=8.5.0 <8.6.0'],
            $manifest['require'],
        );
        self::assertIsArray($manifest['autoload']);
        self::assertIsArray($manifest['autoload-dev']);
        self::assertSame(['Kongono\\IdentityAccess\\' => 'src/'], $manifest['autoload']['psr-4']);
        self::assertSame(['Kongono\\IdentityAccess\\Tests\\' => 'tests/'], $manifest['autoload-dev']['psr-4']);

        $rootManifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($rootManifest);
        self::assertIsArray($rootManifest['require']);
        self::assertIsArray($rootManifest['autoload-dev']);
        self::assertIsArray($rootManifest['autoload-dev']['psr-4']);
        self::assertIsArray($rootManifest['scripts']);
        self::assertIsArray($rootManifest['scripts']['validate:packages']);
        self::assertSame('@dev', $rootManifest['require']['kongono/identity-access']);
        self::assertSame('packages/identity-access/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\IdentityAccess\\Tests\\']);
        self::assertContains('composer --working-dir=packages/identity-access validate --no-check-publish', $rootManifest['scripts']['validate:packages']);

        $packageManifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($packageManifests);
        self::assertCount(75, $packageManifests);
        foreach ($packageManifests as $packageManifestPath) {
            $packageManifest = json_decode((string) file_get_contents($packageManifestPath), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($packageManifest);
            self::assertIsArray($packageManifest['require']);
            if (in_array($packageManifest['name'], ['kongono/agent-admission', 'kongono/agent-principal-contract', 'kongono/authentication', 'kongono/authorization', 'kongono/identity-access', 'kongono/security-enforcement', 'kongono/security-runtime'], true)) {
                continue;
            }
            self::assertArrayNotHasKey('kongono/identity-access', $packageManifest['require']);
        }

        $sourceFiles = $this->recursivePhpFiles($root . '/packages/identity-access/src');
        self::assertCount(16, $sourceFiles);
        foreach ($sourceFiles as $sourceFile) {
            $source = file_get_contents($sourceFile);
            self::assertIsString($source);
            self::assertSame(1, substr_count($source, 'declare(strict_types=1);'));
            self::assertSame(1, substr_count($source, '#[Api('));
            self::assertSame(0, preg_match('/\$_(?:SERVER|ENV|COOKIE|SESSION)|\$GLOBALS|\b(?:getenv|putenv|class_exists|interface_exists|trait_exists|enum_exists|spl_autoload_register|file_get_contents|fopen|curl_exec|stream_socket_client|serialize|unserialize|json_encode)\s*\(/', $source));
            self::assertSame(0, preg_match('/\b(?:JsonSerializable|ArrayAccess|Iterator)\b/', $source));
        }

        $types = [
            Action::class,
            AuthenticationOutcome::class,
            AuthenticationOutcomeKind::class,
            AuthorizationDecision::class,
            IdentityAccessDiagnostic::class,
            IdentityAccessExceptionInterface::class,
            IdentityAccessException::class,
            InvalidIdentityAccessValueException::class,
            PrincipalId::class,
            PrincipalKind::class,
            PrincipalReference::class,
            ResourceId::class,
            ResourceReference::class,
            ResourceType::class,
            SecurityContext::class,
            SecurityContextState::class,
        ];
        foreach ($types as $type) {
            $fileName = (new ReflectionClass($type))->getFileName();
            self::assertIsString($fileName);
            self::assertStringStartsWith(str_replace('\\', '/', $root . '/packages/identity-access/src/'), str_replace('\\', '/', $fileName));
        }

        self::assertSame(['__construct', 'equals', 'value'], $this->declaredPublicMethodNames(new ReflectionClass(PrincipalId::class)));
        self::assertSame(['__construct', 'equals', 'id', 'kind'], $this->declaredPublicMethodNames(new ReflectionClass(PrincipalReference::class)));
        self::assertSame(['absent', 'ambiguous', 'authenticated', 'expired', 'invalid', 'kind', 'principal', 'replayed', 'unavailable', 'unsupported'], $this->declaredPublicMethodNames(new ReflectionClass(AuthenticationOutcome::class)));
        self::assertSame(['__construct', 'equals', 'value'], $this->declaredPublicMethodNames(new ReflectionClass(Action::class)));
        self::assertSame(['__construct', 'equals', 'value'], $this->declaredPublicMethodNames(new ReflectionClass(ResourceType::class)));
        self::assertSame(['__construct', 'equals', 'value'], $this->declaredPublicMethodNames(new ReflectionClass(ResourceId::class)));
        self::assertSame(['__construct', 'equals', 'id', 'type'], $this->declaredPublicMethodNames(new ReflectionClass(ResourceReference::class)));
        self::assertSame(['anonymous', 'authenticated', 'principal', 'state'], $this->declaredPublicMethodNames(new ReflectionClass(SecurityContext::class)));
        self::assertSame(['cases', 'category', 'context', 'from', 'remediation', 'title', 'tryFrom'], $this->declaredPublicMethodNames(new ReflectionClass(IdentityAccessDiagnostic::class)));
        self::assertSame(['diagnosticContext', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle'], $this->declaredPublicMethodNames(new ReflectionClass(IdentityAccessExceptionInterface::class)));
        self::assertSame(['__toString', 'diagnosticContext', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle'], $this->declaredPublicMethodNames(new ReflectionClass(IdentityAccessException::class)));
        self::assertSame(['authentication', 'authorization', 'principal', 'resource', 'securityContext'], $this->declaredPublicMethodNames(new ReflectionClass(InvalidIdentityAccessValueException::class)));

        self::assertSame(['value'], $this->declaredPropertyNames(new ReflectionClass(PrincipalId::class)));
        self::assertSame(['id', 'kind'], $this->declaredPropertyNames(new ReflectionClass(PrincipalReference::class)));
        self::assertSame(['kind', 'principal'], $this->declaredPropertyNames(new ReflectionClass(AuthenticationOutcome::class)));
        self::assertSame(['value'], $this->declaredPropertyNames(new ReflectionClass(Action::class)));
        self::assertSame(['value'], $this->declaredPropertyNames(new ReflectionClass(ResourceType::class)));
        self::assertSame(['value'], $this->declaredPropertyNames(new ReflectionClass(ResourceId::class)));
        self::assertSame(['id', 'type'], $this->declaredPropertyNames(new ReflectionClass(ResourceReference::class)));
        self::assertSame(['principal', 'state'], $this->declaredPropertyNames(new ReflectionClass(SecurityContext::class)));

        self::assertSame(['human', 'service', 'organization', 'system', 'agent'], array_column(PrincipalKind::cases(), 'value'));
        self::assertSame(['authenticated', 'absent', 'invalid', 'expired', 'replayed', 'unsupported', 'ambiguous', 'unavailable'], array_column(AuthenticationOutcomeKind::cases(), 'value'));
        self::assertSame(['permit', 'deny', 'indeterminate'], array_column(AuthorizationDecision::cases(), 'value'));
        self::assertSame(['anonymous', 'authenticated'], array_column(SecurityContextState::cases(), 'value'));
        self::assertContains(IdentityAccessExceptionInterface::class, (new ReflectionClass(IdentityAccessException::class))->getInterfaceNames());
        self::assertContains(IdentityAccessExceptionInterface::class, (new ReflectionClass(InvalidIdentityAccessValueException::class))->getInterfaceNames());
    }

    public function testObservabilityPackageContractsDependenciesShapesAndIsolationAreExact(): void
    {
        $root = dirname(__DIR__, 2);
        $manifest = json_decode((string) file_get_contents($root . '/packages/observability/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertIsArray($manifest['autoload']);
        self::assertIsArray($manifest['autoload']['psr-4']);
        self::assertIsArray($manifest['autoload-dev']);
        self::assertIsArray($manifest['autoload-dev']['psr-4']);
        self::assertSame('kongono/observability', $manifest['name']);
        self::assertSame('Neutral immutable observability contracts for Kongono Framework.', $manifest['description']);
        self::assertSame(
            ['kongono/api-metadata' => '*', 'kongono/diagnostics' => '*', 'php' => '>=8.5.0 <8.6.0'],
            $manifest['require'],
        );
        self::assertSame(['Kongono\\Observability\\' => 'src/'], $manifest['autoload']['psr-4']);
        self::assertSame(['Kongono\\Observability\\Tests\\' => 'tests/'], $manifest['autoload-dev']['psr-4']);

        $rootManifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($rootManifest);
        self::assertIsArray($rootManifest['require']);
        self::assertIsArray($rootManifest['autoload-dev']);
        self::assertIsArray($rootManifest['autoload-dev']['psr-4']);
        self::assertIsArray($rootManifest['scripts']);
        self::assertIsArray($rootManifest['scripts']['validate:packages']);
        self::assertSame('@dev', $rootManifest['require']['kongono/observability']);
        self::assertSame('packages/observability/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\Observability\\Tests\\']);
        self::assertContains('composer --working-dir=packages/observability validate --no-check-publish', $rootManifest['scripts']['validate:packages']);

        $packageManifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($packageManifests);
        self::assertCount(75, $packageManifests);
        foreach ($packageManifests as $packageManifestPath) {
            $packageManifest = json_decode((string) file_get_contents($packageManifestPath), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($packageManifest);
            self::assertIsArray($packageManifest['require']);
            if (!in_array($packageManifest['name'], ['kongono/agent-inspection', 'kongono/metrics', 'kongono/model-invocation-inspection', 'kongono/observability', 'kongono/observability-application', 'kongono/opentelemetry', 'kongono/operational-events', 'kongono/security-audit', 'kongono/tracing'], true)) {
                self::assertArrayNotHasKey('kongono/observability', $packageManifest['require']);
            }
        }

        $sourceFiles = $this->recursivePhpFiles($root . '/packages/observability/src');
        $testFiles = $this->recursivePhpFiles($root . '/packages/observability/tests');
        self::assertCount(17, $sourceFiles);
        self::assertCount(9, $testFiles);
        foreach ($sourceFiles as $sourceFile) {
            $source = file_get_contents($sourceFile);
            self::assertIsString($source);
            self::assertSame(1, substr_count($source, 'declare(strict_types=1);'));
            self::assertSame(1, substr_count($source, '#[Api('));
            self::assertSame(0, preg_match('/\$_(?:SERVER|ENV|COOKIE|SESSION)|\$GLOBALS|\b(?:getenv|putenv|class_exists|interface_exists|trait_exists|enum_exists|spl_autoload_register|file_get_contents|fopen|curl_exec|stream_socket_client|serialize|unserialize|json_encode|microtime|hrtime|random_bytes|random_int)\s*\(/', $source));
            self::assertSame(0, preg_match('/\b(?:OpenTelemetry|Psr\\\\Log|JsonSerializable|ArrayAccess|Iterator|ReflectionClass|register_shutdown_function|eval)\b/', $source));
            self::assertStringNotContainsString('implements ObservationRecorder', $source);
        }

        $surfaces = [
            'Kongono\\Observability\\AttributeKey' => ['__construct', 'equals', 'value'],
            'Kongono\\Observability\\AttributeSet' => ['__construct', 'attributes', 'count', 'find', 'isEmpty'],
            'Kongono\\Observability\\AttributeValue' => ['boolean', 'equals', 'floatingPoint', 'integer', 'kind', 'text', 'value'],
            'Kongono\\Observability\\AttributeValueKind' => ['cases', 'from', 'tryFrom'],
            'Kongono\\Observability\\CorrelationId' => ['__construct', 'equals', 'value'],
            'Kongono\\Observability\\Diagnostic\\ObservabilityDiagnostic' => ['cases', 'category', 'context', 'from', 'remediation', 'title', 'tryFrom'],
            'Kongono\\Observability\\Exception\\ExceptionInterface' => ['diagnosticContext', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle'],
            'Kongono\\Observability\\Exception\\InvalidObservabilityValueException' => ['attribute', 'context', 'signalIdentity'],
            'Kongono\\Observability\\Exception\\ObservabilityException' => ['__toString', 'diagnosticContext', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle'],
            'Kongono\\Observability\\Internal\\ObservabilityValidation' => ['isAttributeKey', 'isBoundedText'],
            'Kongono\\Observability\\Observation' => ['__construct', 'attributes', 'context', 'identity', 'kind', 'status'],
            'Kongono\\Observability\\ObservationKind' => ['cases', 'from', 'tryFrom'],
            'Kongono\\Observability\\ObservationRecorder' => ['record'],
            'Kongono\\Observability\\ObservationStatus' => ['cases', 'from', 'tryFrom'],
            'Kongono\\Observability\\ObservabilityContext' => ['correlated', 'correlationId', 'isCorrelated', 'uncorrelated'],
            'Kongono\\Observability\\SignalAttribute' => ['__construct', 'key', 'value'],
            'Kongono\\Observability\\SignalIdentity' => ['__construct', 'equals', 'name', 'schemaVersion'],
        ];
        foreach ($surfaces as $type => $methods) {
            $reflection = new ReflectionClass($type);
            $fileName = $reflection->getFileName();
            self::assertIsString($fileName);
            self::assertStringStartsWith(str_replace('\\', '/', $root . '/packages/observability/src/'), str_replace('\\', '/', $fileName));
            self::assertSame($methods, $this->declaredPublicMethodNames($reflection));
            self::assertSame([], $reflection->getStaticProperties());
        }

        foreach (['AttributeKey', 'AttributeSet', 'AttributeValue', 'CorrelationId', 'Observation', 'ObservabilityContext', 'SignalAttribute', 'SignalIdentity'] as $valueType) {
            $reflection = new ReflectionClass('Kongono\\Observability\\' . $valueType);
            self::assertTrue($reflection->isFinal());
            self::assertTrue($reflection->isReadOnly());
            foreach (['__debugInfo', '__serialize', '__unserialize', '__toString'] as $magicMethod) {
                self::assertFalse($reflection->hasMethod($magicMethod));
            }
        }
        self::assertSame(['boolean', 'integer', 'floating_point', 'text'], array_column(\Kongono\Observability\AttributeValueKind::cases(), 'value'));
        self::assertSame(['trace', 'metric', 'operational_event', 'security_audit'], array_column(\Kongono\Observability\ObservationKind::cases(), 'value'));
        self::assertSame(['unset', 'ok', 'error'], array_column(\Kongono\Observability\ObservationStatus::cases(), 'value'));
    }

    public function testAuthorizationPackageContractsDependenciesShapesAndIsolationAreExact(): void
    {
        $root = dirname(__DIR__, 2);
        $manifest = json_decode((string) file_get_contents($root . '/packages/authorization/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertSame('kongono/authorization', $manifest['name']);
        self::assertSame('Deterministic deny-by-default authorization evaluation for Kongono Framework.', $manifest['description']);
        self::assertSame('library', $manifest['type']);
        self::assertSame([
            'php' => '>=8.5.0 <8.6.0',
            'kongono/api-metadata' => '*',
            'kongono/diagnostics' => '*',
            'kongono/identity-access' => '*',
        ], $manifest['require']);
        $autoload = $manifest['autoload'];
        $autoloadDev = $manifest['autoload-dev'];
        self::assertIsArray($autoload);
        self::assertIsArray($autoloadDev);
        self::assertIsArray($autoload['psr-4']);
        self::assertIsArray($autoloadDev['psr-4']);
        self::assertSame(['Kongono\\Authorization\\' => 'src/'], $autoload['psr-4']);
        self::assertSame(['Kongono\\Authorization\\Tests\\' => 'tests/'], $autoloadDev['psr-4']);

        $rootManifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($rootManifest);
        $rootRequire = $rootManifest['require'];
        $rootAutoloadDev = $rootManifest['autoload-dev'];
        $rootScripts = $rootManifest['scripts'];
        $rootRepositories = $rootManifest['repositories'];
        self::assertIsArray($rootRequire);
        self::assertIsArray($rootAutoloadDev);
        self::assertIsArray($rootScripts);
        self::assertIsArray($rootRepositories);
        $rootAutoloadDevPsr4 = $rootAutoloadDev['psr-4'];
        $validatePackages = $rootScripts['validate:packages'];
        self::assertIsArray($rootAutoloadDevPsr4);
        self::assertIsArray($validatePackages);
        self::assertSame('@dev', $rootRequire['kongono/authorization']);
        self::assertSame('packages/authorization/tests/', $rootAutoloadDevPsr4['Kongono\\Authorization\\Tests\\']);
        self::assertContains('composer --working-dir=packages/authorization validate --no-check-publish', $validatePackages);
        self::assertContains(['type' => 'path', 'url' => 'packages/authorization'], $rootRepositories);

        $packageManifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($packageManifests);
        self::assertCount(75, $packageManifests);
        foreach ($packageManifests as $packageManifestPath) {
            $packageManifest = json_decode((string) file_get_contents($packageManifestPath), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($packageManifest);
            self::assertIsArray($packageManifest['require']);
            if (in_array($packageManifest['name'], ['kongono/agent-admission', 'kongono/authorization', 'kongono/security-application', 'kongono/security-enforcement', 'kongono/security-policy-build'], true)) {
                continue;
            }
            self::assertArrayNotHasKey('kongono/authorization', $packageManifest['require']);
        }

        $sourceFiles = $this->recursivePhpFiles($root . '/packages/authorization/src');
        $testFiles = $this->recursivePhpFiles($root . '/packages/authorization/tests');
        self::assertCount(18, $sourceFiles);
        self::assertCount(8, $testFiles);
        foreach ($sourceFiles as $sourceFile) {
            $source = file_get_contents($sourceFile);
            self::assertIsString($source);
            self::assertSame(1, substr_count($source, 'declare(strict_types=1);'));
            self::assertSame(1, substr_count($source, '#[Api('));
            self::assertSame(0, preg_match('/\$_(?:SERVER|ENV|COOKIE|SESSION)|\$GLOBALS|\b(?:getenv|putenv|time|microtime|date|class_exists|interface_exists|trait_exists|enum_exists|spl_autoload_register|file_get_contents|fopen|curl_exec|stream_socket_client|serialize|unserialize|json_encode)\s*\(/', $source));
            self::assertSame(0, preg_match('/use Kongono\\\\(?:ApplicationGraph|Authentication|Chie|Composition|Console|Database|DependencyInjection|Hantei|Http|Kernel|Knowledge|Oshie|Persistence|PhpCgi|Routing|Web)\\\\/', $source));
            self::assertSame(0, preg_match('/\b(?:JsonSerializable|ArrayAccess|Iterator|__destruct|__serialize|__unserialize|supports|priority|fallback|retry|allowed|guard|enforce|replay)\b/', $source));
        }

        $types = [
            AuthorizationDecisionReceipt::class => ['AuthorizationDecisionReceipt.php', ApiStatus::Experimental],
            AuthorizationEngine::class => ['AuthorizationEngine.php', ApiStatus::Experimental],
            AuthorizationEnvironmentEvidence::class => ['AuthorizationEnvironmentEvidence.php', ApiStatus::Experimental],
            AuthorizationEnvironmentProfile::class => ['AuthorizationEnvironmentProfile.php', ApiStatus::Experimental],
            AuthorizationEvaluation::class => ['AuthorizationEvaluation.php', ApiStatus::Experimental],
            AuthorizationEvaluator::class => ['AuthorizationEvaluator.php', ApiStatus::Experimental],
            AuthorizationPolicyEvidence::class => ['AuthorizationPolicyEvidence.php', ApiStatus::Experimental],
            AuthorizationPolicyName::class => ['AuthorizationPolicyName.php', ApiStatus::Experimental],
            AuthorizationPolicyProvenance::class => ['AuthorizationPolicyProvenance.php', ApiStatus::Experimental],
            AuthorizationPolicyReference::class => ['AuthorizationPolicyReference.php', ApiStatus::Experimental],
            AuthorizationPolicyVersion::class => ['AuthorizationPolicyVersion.php', ApiStatus::Experimental],
            AuthorizationReceiptReason::class => ['AuthorizationReceiptReason.php', ApiStatus::Experimental],
            AuthorizationRequest::class => ['AuthorizationRequest.php', ApiStatus::Experimental],
            AuthorizationDiagnostic::class => ['Diagnostic/AuthorizationDiagnostic.php', ApiStatus::Internal],
            AuthorizationEvaluationException::class => ['Exception/AuthorizationEvaluationException.php', ApiStatus::Experimental],
            AuthorizationException::class => ['Exception/AuthorizationException.php', ApiStatus::Internal],
            AuthorizationExceptionInterface::class => ['Exception/ExceptionInterface.php', ApiStatus::Public],
            InvalidAuthorizationValueException::class => ['Exception/InvalidAuthorizationValueException.php', ApiStatus::Experimental],
        ];
        foreach ($types as $type => [$relativePath, $status]) {
            $reflection = new ReflectionClass($type);
            $fileName = $reflection->getFileName();
            self::assertIsString($fileName);
            self::assertSame(
                str_replace('\\', '/', $root . '/packages/authorization/src/' . $relativePath),
                str_replace('\\', '/', $fileName),
            );
            $attributes = $reflection->getAttributes(Api::class);
            self::assertCount(1, $attributes);
            self::assertSame($status, $attributes[0]->newInstance()->status);
        }

        self::assertSame(['__construct', 'equals', 'value'], $this->declaredPublicMethodNames(new ReflectionClass(AuthorizationPolicyName::class)));
        self::assertSame(['__construct', 'equals', 'value'], $this->declaredPublicMethodNames(new ReflectionClass(AuthorizationPolicyVersion::class)));
        self::assertSame(['__construct', 'equals', 'value'], $this->declaredPublicMethodNames(new ReflectionClass(AuthorizationPolicyProvenance::class)));
        self::assertSame(['__construct', 'equals', 'name', 'provenance', 'version'], $this->declaredPublicMethodNames(new ReflectionClass(AuthorizationPolicyReference::class)));
        self::assertSame(['reference'], $this->declaredPublicMethodNames(new ReflectionClass(AuthorizationPolicyEvidence::class)));
        self::assertSame(['__construct', 'equals', 'value'], $this->declaredPublicMethodNames(new ReflectionClass(AuthorizationEnvironmentProfile::class)));
        self::assertSame(['profile'], $this->declaredPublicMethodNames(new ReflectionClass(AuthorizationEnvironmentEvidence::class)));
        self::assertSame(['__construct', 'action', 'environment', 'expectedPolicy', 'policyEvidence', 'resource', 'securityContext'], $this->declaredPublicMethodNames(new ReflectionClass(AuthorizationRequest::class)));
        self::assertSame(['evaluate', 'policy'], $this->declaredPublicMethodNames(new ReflectionClass(AuthorizationEvaluator::class)));
        self::assertSame(['cases', 'from', 'tryFrom'], $this->declaredPublicMethodNames(new ReflectionClass(AuthorizationReceiptReason::class)));
        self::assertSame(['action', 'decision', 'environmentProfile', 'evaluated', 'policy', 'policyMismatch', 'reason', 'resourceType', 'securityContextState', 'unsupportedPolicy'], $this->declaredPublicMethodNames(new ReflectionClass(AuthorizationDecisionReceipt::class)));
        self::assertSame(['__construct', 'decision', 'receipt'], $this->declaredPublicMethodNames(new ReflectionClass(AuthorizationEvaluation::class)));
        self::assertSame(['__construct', 'authorize'], $this->declaredPublicMethodNames(new ReflectionClass(AuthorizationEngine::class)));
        self::assertSame(['cases', 'category', 'context', 'from', 'remediation', 'title', 'tryFrom'], $this->declaredPublicMethodNames(new ReflectionClass(AuthorizationDiagnostic::class)));
        self::assertSame([], $this->declaredPublicMethodNames(new ReflectionClass(AuthorizationExceptionInterface::class)));
        self::assertSame(['__toString', 'diagnosticContext', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle'], $this->declaredPublicMethodNames(new ReflectionClass(AuthorizationException::class)));
        self::assertSame(['from'], $this->declaredPublicMethodNames(new ReflectionClass(AuthorizationEvaluationException::class)));
        self::assertSame(['receipt', 'registration', 'validation'], $this->declaredPublicMethodNames(new ReflectionClass(InvalidAuthorizationValueException::class)));

        self::assertSame(['action', 'decision', 'environmentProfile', 'policy', 'reason', 'resourceType', 'securityContextState'], $this->declaredPropertyNames(new ReflectionClass(AuthorizationDecisionReceipt::class)));
        self::assertSame(['registrations'], $this->declaredPropertyNames(new ReflectionClass(AuthorizationEngine::class)));
        self::assertSame(['receipt'], $this->declaredPropertyNames(new ReflectionClass(AuthorizationEvaluation::class)));
        self::assertSame(['action', 'environment', 'expectedPolicy', 'policyEvidence', 'resource', 'securityContext'], $this->declaredPropertyNames(new ReflectionClass(AuthorizationRequest::class)));

        self::assertSame(['evaluated', 'policy_mismatch', 'unsupported_policy'], array_column(AuthorizationReceiptReason::cases(), 'value'));
        self::assertSame([
            'KGN-AUTHORIZATION-1001',
            'KGN-AUTHORIZATION-1002',
            'KGN-AUTHORIZATION-1003',
            'KGN-AUTHORIZATION-1004',
            'KGN-AUTHORIZATION-1005',
            'KGN-AUTHORIZATION-1006',
        ], array_column(AuthorizationDiagnostic::cases(), 'value'));
        self::assertContains(AuthorizationExceptionInterface::class, (new ReflectionClass(AuthorizationException::class))->getInterfaceNames());
        foreach ([AuthorizationEvaluationException::class, InvalidAuthorizationValueException::class] as $failure) {
            self::assertContains(AuthorizationExceptionInterface::class, (new ReflectionClass($failure))->getInterfaceNames());
        }

        $productionCount = 0;
        $packageTestCount = 0;
        $packageDirectories = glob($root . '/packages/*', GLOB_ONLYDIR);
        self::assertIsArray($packageDirectories);
        foreach ($packageDirectories as $packageDirectory) {
            if (is_dir($packageDirectory . '/src')) {
                $productionCount += count($this->recursivePhpFiles($packageDirectory . '/src'));
            }
            if (is_dir($packageDirectory . '/tests')) {
                $packageTestCount += count($this->recursivePhpFiles($packageDirectory . '/tests'));
            }
        }
        self::assertSame(1358, $productionCount);
        self::assertSame(686, $packageTestCount);
        self::assertCount(80, $this->recursivePhpFiles($root . '/tests'));
    }

    public function testTracingPackageContractsDependenciesShapesAndIsolationAreExact(): void
    {
        $root = dirname(__DIR__, 2);
        $manifest = json_decode((string) file_get_contents($root . '/packages/tracing/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertIsArray($manifest['autoload']);
        self::assertIsArray($manifest['autoload']['psr-4']);
        self::assertIsArray($manifest['autoload-dev']);
        self::assertIsArray($manifest['autoload-dev']['psr-4']);
        self::assertSame('kongono/tracing', $manifest['name']);
        self::assertSame('Explicit operation-scoped tracing lifecycle for Kongono Framework.', $manifest['description']);
        self::assertSame([
            'kongono/api-metadata' => '*',
            'kongono/diagnostics' => '*',
            'kongono/observability' => '*',
            'php' => '>=8.5.0 <8.6.0',
        ], $manifest['require']);
        self::assertSame(['Kongono\\Tracing\\' => 'src/'], $manifest['autoload']['psr-4']);
        self::assertSame(['Kongono\\Tracing\\Tests\\' => 'tests/'], $manifest['autoload-dev']['psr-4']);

        $rootManifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($rootManifest);
        self::assertIsArray($rootManifest['require']);
        self::assertIsArray($rootManifest['autoload-dev']);
        self::assertIsArray($rootManifest['autoload-dev']['psr-4']);
        self::assertIsArray($rootManifest['scripts']);
        self::assertIsArray($rootManifest['scripts']['validate:packages']);
        self::assertSame('@dev', $rootManifest['require']['kongono/tracing']);
        self::assertSame('packages/tracing/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\Tracing\\Tests\\']);
        self::assertContains('composer --working-dir=packages/tracing validate --no-check-publish', $rootManifest['scripts']['validate:packages']);

        $packageManifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($packageManifests);
        self::assertCount(75, $packageManifests);
        foreach ($packageManifests as $packageManifestPath) {
            $packageManifest = json_decode((string) file_get_contents($packageManifestPath), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($packageManifest);
            self::assertIsArray($packageManifest['require']);
            if (!in_array($packageManifest['name'], ['kongono/observability-application', 'kongono/tracing'], true)) {
                self::assertArrayNotHasKey('kongono/tracing', $packageManifest['require']);
            }
        }

        $sourceFiles = $this->recursivePhpFiles($root . '/packages/tracing/src');
        $testFiles = $this->recursivePhpFiles($root . '/packages/tracing/tests');
        self::assertCount(23, $sourceFiles);
        self::assertCount(13, $testFiles);
        foreach ($sourceFiles as $sourceFile) {
            $source = file_get_contents($sourceFile);
            self::assertIsString($source);
            self::assertSame(1, substr_count($source, 'declare(strict_types=1);'));
            self::assertSame(1, substr_count($source, '#[Api('));
            self::assertSame(0, preg_match('/\$_(?:SERVER|ENV|COOKIE|SESSION)|\$GLOBALS|\b(?:getenv|putenv|spl_autoload_register|file_get_contents|fopen|curl_exec|stream_socket_client|serialize|unserialize|json_encode|microtime|hrtime|random_bytes|random_int)\s*\(/', $source));
            self::assertSame(0, preg_match('/\b(?:OpenTelemetry|Psr\\\\Log|register_shutdown_function|eval|ReflectionClass)\b/', $source));
            self::assertSame(0, preg_match('/\bimplements\s+(?:TraceClock|TraceIdentifierSource|TraceSampler|ObservationRecorder)\b/', $source));
            self::assertSame(0, preg_match('/\b(?:currentSpan|current_span|globalTracer|global_tracer|threadLocal|fiberLocal)\b/i', $source));
        }

        $surfaces = [
            'Kongono\\Tracing\\SpanId' => ['__construct', 'equals', 'value'],
            'Kongono\\Tracing\\TraceClock' => ['nowNanoseconds'],
            'Kongono\\Tracing\\TraceId' => ['__construct', 'equals', 'value'],
            'Kongono\\Tracing\\TraceIdentifierSource' => ['nextSpanId', 'nextTraceId'],
            'Kongono\\Tracing\\TraceLifecycleState' => ['cases', 'from', 'tryFrom'],
            'Kongono\\Tracing\\TraceOperation' => ['activeSpanCount', 'beginSpan', 'complete', 'parentSpanId', 'propagation', 'retire', 'samplingDecision', 'spanId', 'state', 'traceId'],
            'Kongono\\Tracing\\TracePropagation' => ['__construct', 'samplingDecision', 'spanId', 'traceId'],
            'Kongono\\Tracing\\TraceRuntime' => ['__construct', 'begin'],
            'Kongono\\Tracing\\TraceSampler' => ['decide'],
            'Kongono\\Tracing\\TraceSamplingDecision' => ['cases', 'from', 'tryFrom'],
            'Kongono\\Tracing\\TraceSamplingRequest' => ['__construct', 'context', 'identity', 'parent', 'traceId'],
            'Kongono\\Tracing\\TraceSpan' => ['complete', 'parentSpanId', 'propagation', 'retire', 'samplingDecision', 'spanId', 'state', 'traceId'],
            'Kongono\\Tracing\\Exception\\ExceptionInterface' => ['diagnosticContext', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle'],
            'Kongono\\Tracing\\Exception\\InvalidTracingValueException' => ['attributes', 'identifier', 'status', 'timing'],
            'Kongono\\Tracing\\Exception\\TraceRecordingException' => ['failed'],
            'Kongono\\Tracing\\Exception\\TracingCollaboratorException' => ['clock', 'identifierSource', 'sampler'],
            'Kongono\\Tracing\\Exception\\TracingException' => ['__toString', 'diagnosticContext', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle'],
            'Kongono\\Tracing\\Exception\\TracingLifecycleException' => ['capacity', 'ownership', 'state'],
        ];
        foreach ($surfaces as $type => $methods) {
            $reflection = new ReflectionClass($type);
            $fileName = $reflection->getFileName();
            self::assertIsString($fileName);
            self::assertStringStartsWith(str_replace('\\', '/', $root . '/packages/tracing/src/'), str_replace('\\', '/', $fileName));
            self::assertSame($methods, $this->declaredPublicMethodNames($reflection));
            self::assertSame([], $reflection->getStaticProperties());
        }

        foreach (['SpanId', 'TraceId', 'TracePropagation', 'TraceSamplingRequest'] as $valueType) {
            $reflection = new ReflectionClass('Kongono\\Tracing\\' . $valueType);
            self::assertTrue($reflection->isFinal());
            self::assertTrue($reflection->isReadOnly());
            foreach (['__debugInfo', '__serialize', '__unserialize', '__toString'] as $magicMethod) {
                self::assertFalse($reflection->hasMethod($magicMethod));
            }
        }
        self::assertSame(['record', 'drop'], array_column(\Kongono\Tracing\TraceSamplingDecision::cases(), 'value'));
        self::assertSame(['active', 'completed', 'retired'], array_column(\Kongono\Tracing\TraceLifecycleState::cases(), 'value'));
        self::assertSame(
            ['KGN-TRACING-1001', 'KGN-TRACING-1002', 'KGN-TRACING-1003', 'KGN-TRACING-1004'],
            array_column(\Kongono\Tracing\Diagnostic\TracingDiagnostic::cases(), 'value'),
        );
    }

    public function testMetricsPackageContractsDependenciesShapesAndIsolationAreExact(): void
    {
        $root = dirname(__DIR__, 2);
        $manifest = json_decode((string) file_get_contents($root . '/packages/metrics/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertSame('kongono/metrics', $manifest['name']);
        self::assertSame('Bounded provider-neutral metric aggregation contracts for Kongono Framework.', $manifest['description']);
        self::assertSame('library', $manifest['type']);
        self::assertSame([
            'php' => '>=8.5.0 <8.6.0',
            'kongono/api-metadata' => '*',
            'kongono/diagnostics' => '*',
            'kongono/observability' => '*',
        ], $manifest['require']);
        self::assertIsArray($manifest['autoload']);
        self::assertIsArray($manifest['autoload']['psr-4']);
        self::assertSame(['Kongono\\Metrics\\' => 'src/'], $manifest['autoload']['psr-4']);
        self::assertArrayNotHasKey('autoload-dev', $manifest);

        $rootManifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($rootManifest);
        self::assertIsArray($rootManifest['require']);
        self::assertIsArray($rootManifest['autoload-dev']);
        self::assertIsArray($rootManifest['autoload-dev']['psr-4']);
        self::assertIsArray($rootManifest['scripts']);
        self::assertIsArray($rootManifest['scripts']['validate:packages']);
        self::assertIsArray($rootManifest['repositories']);
        self::assertSame('@dev', $rootManifest['require']['kongono/metrics']);
        self::assertSame('packages/metrics/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\Metrics\\Tests\\']);
        self::assertContains('composer --working-dir=packages/metrics validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        self::assertContains(['type' => 'path', 'url' => 'packages/*'], $rootManifest['repositories']);
        foreach (['.php-cs-fixer.dist.php', 'phpstan.neon.dist', 'phpunit.xml.dist'] as $configuration) {
            $contents = file_get_contents($root . '/' . $configuration);
            self::assertIsString($contents);
            self::assertSame(1, substr_count($contents, 'packages/metrics/src'));
            self::assertSame(1, substr_count($contents, 'packages/metrics/tests'));
        }

        $packageManifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($packageManifests);
        self::assertCount(75, $packageManifests);
        $graph = [];
        $localEdges = 0;
        foreach ($packageManifests as $packageManifestPath) {
            $packageManifest = json_decode((string) file_get_contents($packageManifestPath), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($packageManifest);
            self::assertIsString($packageManifest['name']);
            self::assertIsArray($packageManifest['require']);
            $graph[$packageManifest['name']] = array_values(array_filter(
                array_keys($packageManifest['require']),
                static fn(string $dependency): bool => str_starts_with($dependency, 'kongono/'),
            ));
            $localEdges += count($graph[$packageManifest['name']]);
            if (!in_array($packageManifest['name'], ['kongono/metrics', 'kongono/observability-application'], true)) {
                self::assertArrayNotHasKey('kongono/metrics', $packageManifest['require']);
            }
        }
        self::assertCount(75, array_unique(array_keys($graph)));
        self::assertSame(289, $localEdges);
        $visit = static function (string $package, array $path = []) use (&$visit, $graph): void {
            self::assertArrayNotHasKey($package, $path, sprintf('Package graph cycle reaches %s.', $package));
            $path[$package] = true;
            foreach ($graph[$package] as $dependency) {
                if (isset($graph[$dependency])) {
                    $visit($dependency, $path);
                }
            }
        };
        foreach (array_keys($graph) as $package) {
            $visit($package);
        }

        $sourceFiles = $this->recursivePhpFiles($root . '/packages/metrics/src');
        $testFiles = $this->recursivePhpFiles($root . '/packages/metrics/tests');
        self::assertCount(30, $sourceFiles);
        self::assertCount(15, $testFiles);
        self::assertSame([
            'Diagnostic/MetricsDiagnosticTest.php',
            'Exception/MetricsExceptionTest.php',
            'HistogramBoundariesTest.php',
            'MetricAggregationTest.php',
            'MetricApiSurfaceTest.php',
            'MetricCardinalityTest.php',
            'MetricCollectionLifecycleTest.php',
            'MetricDimensionSchemaTest.php',
            'MetricInstrumentTest.php',
            'MetricIsolationTest.php',
            'MetricMeasurementTest.php',
            'MetricNumberTest.php',
            'MetricPlanTest.php',
            'MetricRecordingFailureTest.php',
            'MetricUnitTest.php',
        ], array_map(static function (string $path) use ($root): string {
            $normalizedPath = str_replace('\\', '/', $path);
            $prefix = str_replace('\\', '/', $root) . '/packages/metrics/tests/Unit/';

            return substr($normalizedPath, strlen($prefix));
        }, $testFiles));
        foreach ($sourceFiles as $sourceFile) {
            $source = file_get_contents($sourceFile);
            self::assertIsString($source);
            self::assertSame(1, substr_count($source, 'declare(strict_types=1);'));
            self::assertSame(1, substr_count($source, '#[Api('));
            self::assertSame(0, preg_match('/\$_(?:SERVER|ENV|COOKIE|SESSION)|\$GLOBALS|\b(?:getenv|putenv|time|microtime|hrtime|random_bytes|random_int|spl_autoload_register|file_get_contents|fopen|curl_exec|stream_socket_client|serialize|unserialize|eval)\s*\(/', $source));
            self::assertSame(0, preg_match('/\b(?:OpenTelemetry|Psr\\\\Log|PDO|ReflectionClass|ServiceLocator|Container|Registry|Exporter|Backend|Collector|Provider|currentMeter|currentCollection|threadLocal|fiberLocal|register_shutdown_function|__destruct)\b/i', $source));
            self::assertSame(0, preg_match('/use Kongono\\\\(?:Tracing|Kernel|Composition|Http|Console|Database|Persistence|Security|Web|PhpCgi)\\\\/', $source));
        }

        $surfaces = [
            'Kongono\\Metrics\\Diagnostic\\MetricsDiagnostic' => ['cases', 'category', 'context', 'from', 'remediation', 'title', 'tryFrom'],
            'Kongono\\Metrics\\Exception\\ExceptionInterface' => ['diagnosticContext', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle'],
            'Kongono\\Metrics\\Exception\\InvalidMetricSchemaException' => ['dimension', 'instrument', 'plan'],
            'Kongono\\Metrics\\Exception\\InvalidMetricValueException' => ['boundaries', 'measurement', 'unit'],
            'Kongono\\Metrics\\Exception\\MetricAggregationException' => ['overflow'],
            'Kongono\\Metrics\\Exception\\MetricLifecycleException' => ['ownership', 'state'],
            'Kongono\\Metrics\\Exception\\MetricRecordingException' => ['failed'],
            'Kongono\\Metrics\\Exception\\MetricsException' => ['__toString', 'diagnosticContext', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle'],
            'Kongono\\Metrics\\HistogramBoundaries' => ['__construct', 'boundaries', 'count'],
            'Kongono\\Metrics\\MetricCollection' => ['activeSeriesCount', 'collect', 'record', 'retire', 'state'],
            'Kongono\\Metrics\\MetricCollectionState' => ['cases', 'from', 'tryFrom'],
            'Kongono\\Metrics\\MetricDimension' => ['__construct', 'allowedValues', 'key', 'matches'],
            'Kongono\\Metrics\\MetricDimensionSchema' => ['__construct', 'dimensions', 'seriesCapacity'],
            'Kongono\\Metrics\\MetricInstrument' => ['__construct', 'boundaries', 'dimensions', 'identity', 'kind', 'unit'],
            'Kongono\\Metrics\\MetricInstrumentKind' => ['cases', 'from', 'tryFrom'],
            'Kongono\\Metrics\\MetricMeasurement' => ['__construct', 'dimensions', 'value'],
            'Kongono\\Metrics\\MetricNumber' => ['__construct', 'equals', 'isFloat', 'isInteger', 'value'],
            'Kongono\\Metrics\\MetricPlan' => ['__construct', 'instruments', 'seriesCapacity'],
            'Kongono\\Metrics\\MetricRuntime' => ['__construct', 'begin'],
            'Kongono\\Metrics\\MetricUnit' => ['__construct', 'equals', 'value'],
        ];
        foreach ($surfaces as $type => $methods) {
            $reflection = new ReflectionClass($type);
            self::assertSame($methods, $this->declaredPublicMethodNames($reflection));
            self::assertSame([], $reflection->getStaticProperties());
        }
        foreach (['HistogramBoundaries', 'MetricDimension', 'MetricDimensionSchema', 'MetricInstrument', 'MetricMeasurement', 'MetricNumber', 'MetricPlan', 'MetricRuntime', 'MetricUnit'] as $valueType) {
            $reflection = new ReflectionClass('Kongono\\Metrics\\' . $valueType);
            self::assertTrue($reflection->isFinal());
            self::assertTrue($reflection->isReadOnly());
        }
        self::assertSame(['counter', 'up_down_counter', 'gauge', 'histogram'], array_column(\Kongono\Metrics\MetricInstrumentKind::cases(), 'value'));
        self::assertSame(['active', 'collected', 'retired'], array_column(\Kongono\Metrics\MetricCollectionState::cases(), 'value'));
        self::assertSame(
            ['KGN-METRICS-1001', 'KGN-METRICS-1002', 'KGN-METRICS-1003', 'KGN-METRICS-1004', 'KGN-METRICS-1005'],
            array_column(\Kongono\Metrics\Diagnostic\MetricsDiagnostic::cases(), 'value'),
        );

        $productionCount = 0;
        $packageTestCount = 0;
        $packageDirectories = glob($root . '/packages/*', GLOB_ONLYDIR);
        self::assertIsArray($packageDirectories);
        foreach ($packageDirectories as $packageDirectory) {
            if (is_dir($packageDirectory . '/src')) {
                $productionCount += count($this->recursivePhpFiles($packageDirectory . '/src'));
            }
            if (is_dir($packageDirectory . '/tests')) {
                $packageTestCount += count($this->recursivePhpFiles($packageDirectory . '/tests'));
            }
        }
        self::assertSame(1358, $productionCount);
        self::assertSame(686, $packageTestCount);
    }

    public function testOpenTelemetryPackageContractsDependenciesShapesAndIsolationAreExact(): void
    {
        $root = dirname(__DIR__, 2);
        $manifest = json_decode((string) file_get_contents($root . '/packages/opentelemetry/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertSame('kongono/opentelemetry', $manifest['name']);
        self::assertSame([
            'php' => '>=8.5.0 <8.6.0',
            'kongono/api-metadata' => '*',
            'kongono/diagnostics' => '*',
            'kongono/observability' => '*',
            'open-telemetry/gen-otlp-protobuf' => '1.10.0',
            'open-telemetry/sem-conv' => '1.38.0',
        ], $manifest['require']);
        self::assertIsArray($manifest['autoload']);
        self::assertIsArray($manifest['autoload']['psr-4']);
        self::assertSame(['Kongono\\OpenTelemetry\\' => 'src/'], $manifest['autoload']['psr-4']);
        self::assertArrayNotHasKey('version', $manifest);

        $rootManifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($rootManifest);
        self::assertIsArray($rootManifest['require']);
        self::assertSame('@dev', $rootManifest['require']['kongono/opentelemetry']);
        self::assertIsArray($rootManifest['autoload-dev']);
        self::assertIsArray($rootManifest['autoload-dev']['psr-4']);
        self::assertSame('packages/opentelemetry/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\OpenTelemetry\\Tests\\']);
        self::assertIsArray($rootManifest['scripts']);
        self::assertIsArray($rootManifest['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/opentelemetry validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        self::assertArrayNotHasKey('minimum-stability', $rootManifest);

        foreach (['.php-cs-fixer.dist.php', 'phpstan.neon.dist', 'phpunit.xml.dist'] as $configuration) {
            $contents = file_get_contents($root . '/' . $configuration);
            self::assertIsString($contents);
            self::assertSame(1, substr_count($contents, 'packages/opentelemetry/src'));
            self::assertSame(1, substr_count($contents, 'packages/opentelemetry/tests'));
        }

        $packageManifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($packageManifests);
        self::assertCount(75, $packageManifests);
        $localEdges = 0;
        $names = [];
        foreach ($packageManifests as $path) {
            $package = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($package);
            self::assertIsString($package['name']);
            self::assertIsArray($package['require']);
            $names[] = $package['name'];
            $localEdges += count(array_filter(array_keys($package['require']), static fn(string $dependency): bool => str_starts_with($dependency, 'kongono/')));
            if (!in_array($package['name'], ['kongono/console-application', 'kongono/observability-application', 'kongono/observability-build', 'kongono/opentelemetry'], true)) {
                self::assertArrayNotHasKey('kongono/opentelemetry', $package['require']);
            }
        }
        self::assertCount(75, array_unique($names));
        self::assertSame(289, $localEdges);

        $lock = json_decode((string) file_get_contents($root . '/composer.lock'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($lock);
        self::assertIsArray($lock['packages']);
        $versions = [];
        foreach ($lock['packages'] as $package) {
            self::assertIsArray($package);
            self::assertIsString($package['name']);
            self::assertIsString($package['version']);
            $versions[$package['name']] = $package['version'];
        }
        self::assertSame('1.10.0', $versions['open-telemetry/gen-otlp-protobuf']);
        self::assertSame('1.38.0', $versions['open-telemetry/sem-conv']);
        self::assertSame('v5.36.0', $versions['google/protobuf']);
        foreach (['open-telemetry/opentelemetry', 'open-telemetry/api', 'open-telemetry/context', 'open-telemetry/sdk', 'open-telemetry/exporter-otlp', 'open-telemetry/sdk-configuration'] as $prohibited) {
            self::assertArrayNotHasKey($prohibited, $versions);
        }

        $sourceFiles = $this->recursivePhpFiles($root . '/packages/opentelemetry/src');
        $testFiles = $this->recursivePhpFiles($root . '/packages/opentelemetry/tests');
        self::assertCount(24, $sourceFiles);
        self::assertCount(13, $testFiles);
        foreach ($sourceFiles as $sourceFile) {
            $source = file_get_contents($sourceFile);
            self::assertIsString($source);
            self::assertSame(1, substr_count($source, 'declare(strict_types=1);'));
            self::assertSame(1, substr_count($source, '#[Api('));
            self::assertSame(0, preg_match('/\$_(?:SERVER|ENV|COOKIE|SESSION)|\$GLOBALS|\b(?:getenv|putenv|time|microtime|hrtime|random_bytes|random_int|spl_autoload_register|file_get_contents|fopen|curl_exec|stream_socket_client|serialize|unserialize|eval|register_shutdown_function)\s*\(/', $source));
            self::assertSame(0, preg_match('/\b(?:Globals|ServiceLocator|Container|Registry|WeakMap|__destruct|threadLocal|fiberLocal)\b/i', $source));
            foreach (['Tracing', 'Metrics', 'OperationalEvents', 'SecurityAudit', 'Kernel', 'Composition', 'Http', 'Console', 'Database', 'Persistence', 'Security', 'Web', 'PhpCgi'] as $prohibitedNamespace) {
                self::assertStringNotContainsString('use Kongono\\' . $prohibitedNamespace . '\\', $source);
            }
        }

        $surfaces = [
            'Kongono\\OpenTelemetry\\OpenTelemetryBatchPolicy' => ['__construct', 'maximumBatchSize', 'queueCapacity', 'transportTimeoutMilliseconds'],
            'Kongono\\OpenTelemetry\\OpenTelemetryBridge' => ['__construct', 'close', 'flush', 'queuedCount', 'record', 'state'],
            'Kongono\\OpenTelemetry\\OpenTelemetryBridgeState' => ['cases', 'from', 'tryFrom'],
            'Kongono\\OpenTelemetry\\OpenTelemetryClock' => ['epochNanoseconds'],
            'Kongono\\OpenTelemetry\\OpenTelemetryResource' => ['__construct', 'deploymentEnvironmentName', 'serviceName', 'serviceVersion'],
            'Kongono\\OpenTelemetry\\OpenTelemetryTimeAnchor' => ['__construct', 'epochNanoseconds', 'monotonicNanoseconds', 'toEpochNanoseconds'],
            'Kongono\\OpenTelemetry\\OtlpHttpTransport' => ['close', 'flush', 'send'],
            'Kongono\\OpenTelemetry\\OtlpSignal' => ['cases', 'endpointSuffix', 'from', 'tryFrom'],
        ];
        foreach ($surfaces as $type => $methods) {
            self::assertSame($methods, $this->declaredPublicMethodNames(new ReflectionClass($type)));
        }
        self::assertSame(['/v1/traces', '/v1/metrics', '/v1/logs'], array_column(('Kongono\\OpenTelemetry\\OtlpSignal')::cases(), 'value'));
        self::assertSame(['open', 'flushing', 'closing', 'closed'], array_column(('Kongono\\OpenTelemetry\\OpenTelemetryBridgeState')::cases(), 'value'));
        self::assertSame(['KGN-OTEL-1001', 'KGN-OTEL-1002', 'KGN-OTEL-1003', 'KGN-OTEL-1004', 'KGN-OTEL-1005'], array_column(('Kongono\\OpenTelemetry\\Diagnostic\\OpenTelemetryDiagnostic')::cases(), 'value'));
    }

    public function testOperationalEventAndSecurityAuditPackagesAreExactAndSeparate(): void
    {
        $root = dirname(__DIR__, 2);
        $packages = [
            'operational-events' => [
                'kongono/operational-events',
                'Structured bounded operational-event evidence for Kongono Framework.',
                'Kongono\\OperationalEvents\\',
                'Kongono\\OperationalEvents\\Tests\\',
                14,
                8,
            ],
            'security-audit' => [
                'kongono/security-audit',
                'Bounded provider-neutral security-audit evidence for Kongono Framework.',
                'Kongono\\SecurityAudit\\',
                'Kongono\\SecurityAudit\\Tests\\',
                15,
                8,
            ],
        ];
        $rootManifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($rootManifest);
        self::assertIsArray($rootManifest['require']);
        self::assertIsArray($rootManifest['autoload-dev']);
        self::assertIsArray($rootManifest['autoload-dev']['psr-4']);
        self::assertIsArray($rootManifest['scripts']);
        self::assertIsArray($rootManifest['scripts']['validate:packages']);
        foreach ($packages as $directory => [$name, $description, $namespace, $testNamespace, $sourceCount, $testCount]) {
            $manifest = json_decode((string) file_get_contents($root . '/packages/' . $directory . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($manifest);
            self::assertSame($name, $manifest['name']);
            self::assertSame($description, $manifest['description']);
            self::assertSame('library', $manifest['type']);
            self::assertSame([
                'php' => '>=8.5.0 <8.6.0',
                'kongono/api-metadata' => '*',
                'kongono/diagnostics' => '*',
                'kongono/observability' => '*',
            ], $manifest['require']);
            self::assertIsArray($manifest['autoload']);
            self::assertSame([$namespace => 'src/'], $manifest['autoload']['psr-4']);
            self::assertArrayNotHasKey('autoload-dev', $manifest);
            self::assertSame('@dev', $rootManifest['require'][$name]);
            self::assertSame('packages/' . $directory . '/tests/', $rootManifest['autoload-dev']['psr-4'][$testNamespace]);
            self::assertContains('composer --working-dir=packages/' . $directory . ' validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
            $sourceFiles = $this->recursivePhpFiles($root . '/packages/' . $directory . '/src');
            self::assertCount($sourceCount, $sourceFiles);
            self::assertCount($testCount, $this->recursivePhpFiles($root . '/packages/' . $directory . '/tests'));
            foreach ($sourceFiles as $sourceFile) {
                $source = file_get_contents($sourceFile);
                self::assertIsString($source);
                self::assertSame(1, substr_count($source, 'declare(strict_types=1);'));
                self::assertSame(1, substr_count($source, '#[Api('));
                self::assertSame(0, preg_match('/\$_(?:SERVER|ENV|COOKIE|SESSION)|\$GLOBALS|\b(?:getenv|putenv|time|microtime|hrtime|random_bytes|random_int|spl_autoload_register|file_get_contents|fopen|curl_exec|stream_socket_client|serialize|unserialize|eval)\s*\(/', $source));
                self::assertSame(0, preg_match('/\b(?:OpenTelemetry|Psr\\\\Log|PDO|ReflectionClass|ServiceLocator|Container|Registry|Exporter|Backend|Provider|Store|register_shutdown_function|__destruct|WeakMap|Fiber)\b/i', $source));
            }
        }

        $allPackageManifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($allPackageManifests);
        foreach ($allPackageManifests as $manifestPath) {
            $manifest = json_decode((string) file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($manifest);
            self::assertIsArray($manifest['require']);
            if (!in_array($manifest['name'], ['kongono/observability-application', 'kongono/operational-events', 'kongono/security-audit'], true)) {
                self::assertArrayNotHasKey('kongono/operational-events', $manifest['require']);
            }
            if (!in_array($manifest['name'], ['kongono/agent-inspection', 'kongono/model-invocation-inspection', 'kongono/observability-application', 'kongono/operational-events', 'kongono/security-audit'], true)) {
                self::assertArrayNotHasKey('kongono/security-audit', $manifest['require']);
            }
        }

        $surfaces = [
            'Kongono\\OperationalEvents\\OperationalEvent' => ['__construct', 'attributes', 'context', 'definition', 'disclosure', 'status'],
            'Kongono\\OperationalEvents\\OperationalEventDefinition' => ['__construct', 'identity', 'schema', 'severity'],
            'Kongono\\OperationalEvents\\OperationalEventField' => ['__construct', 'allowedValues', 'disclosure', 'key', 'kind', 'matches', 'required'],
            'Kongono\\OperationalEvents\\OperationalEventRuntime' => ['__construct', 'record'],
            'Kongono\\OperationalEvents\\OperationalEventSchema' => ['__construct', 'fields', 'validate'],
            'Kongono\\SecurityAudit\\SecurityAuditDefinition' => ['__construct', 'identity', 'retentionIntent', 'schema'],
            'Kongono\\SecurityAudit\\SecurityAuditEvidence' => ['__construct', 'attributes', 'context', 'definition', 'disclosure', 'outcome'],
            'Kongono\\SecurityAudit\\SecurityAuditField' => ['__construct', 'allowedValues', 'disclosure', 'key', 'kind', 'matches', 'required'],
            'Kongono\\SecurityAudit\\SecurityAuditRuntime' => ['__construct', 'record'],
            'Kongono\\SecurityAudit\\SecurityAuditSchema' => ['__construct', 'fields', 'validate'],
        ];
        foreach ($surfaces as $type => $methods) {
            self::assertSame($methods, $this->declaredPublicMethodNames(new ReflectionClass($type)));
            self::assertSame([], (new ReflectionClass($type))->getStaticProperties());
        }
        foreach (array_keys($surfaces) as $type) {
            $reflection = new ReflectionClass($type);
            self::assertTrue($reflection->isFinal());
            self::assertTrue($reflection->isReadOnly());
        }
        self::assertSame(['debug', 'info', 'notice', 'warning', 'error', 'critical'], array_column(('Kongono\\OperationalEvents\\OperationalEventSeverity')::cases(), 'value'));
        self::assertSame(['succeeded', 'denied', 'failed'], array_column(('Kongono\\SecurityAudit\\SecurityAuditOutcome')::cases(), 'value'));
        self::assertSame(['short', 'standard', 'extended'], array_column(('Kongono\\SecurityAudit\\SecurityAuditRetentionIntent')::cases(), 'value'));
        self::assertSame(['KGN-OP-EVENTS-1001', 'KGN-OP-EVENTS-1002', 'KGN-OP-EVENTS-1003'], array_column(('Kongono\\OperationalEvents\\Diagnostic\\OperationalEventsDiagnostic')::cases(), 'value'));
        self::assertSame(['KGN-SEC-AUDIT-1001', 'KGN-SEC-AUDIT-1002', 'KGN-SEC-AUDIT-1003'], array_column(('Kongono\\SecurityAudit\\Diagnostic\\SecurityAuditDiagnostic')::cases(), 'value'));

        $operationalSource = implode('', array_map(static fn(string $path): string => (string) file_get_contents($path), $this->recursivePhpFiles($root . '/packages/operational-events/src')));
        $auditSource = implode('', array_map(static fn(string $path): string => (string) file_get_contents($path), $this->recursivePhpFiles($root . '/packages/security-audit/src')));
        self::assertStringNotContainsString('Kongono\\SecurityAudit', $operationalSource);
        self::assertStringNotContainsString('Kongono\\OperationalEvents', $auditSource);
    }

    public function testSecurityRuntimePackageContractsDependenciesShapesAndIsolationAreExact(): void
    {
        $root = dirname(__DIR__, 2);
        $manifest = json_decode((string) file_get_contents($root . '/packages/security-runtime/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertSame('kongono/security-runtime', $manifest['name']);
        self::assertSame('Operation-scoped Security Context construction and generated Composition integration for Kongono Framework.', $manifest['description']);
        self::assertSame('library', $manifest['type']);
        self::assertSame([
            'kongono/api-metadata' => '*',
            'kongono/authentication' => '*',
            'kongono/composition' => '*',
            'kongono/diagnostics' => '*',
            'kongono/identity-access' => '*',
            'php' => '>=8.5.0 <8.6.0',
        ], $manifest['require']);
        $autoload = $manifest['autoload'];
        $autoloadDev = $manifest['autoload-dev'];
        self::assertIsArray($autoload);
        self::assertIsArray($autoloadDev);
        self::assertIsArray($autoload['psr-4']);
        self::assertIsArray($autoloadDev['psr-4']);
        self::assertSame(['Kongono\\SecurityRuntime\\' => 'src/'], $autoload['psr-4']);
        self::assertSame(['Kongono\\SecurityRuntime\\Tests\\' => 'tests/'], $autoloadDev['psr-4']);

        $rootManifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($rootManifest);
        $rootRequire = $rootManifest['require'];
        $rootAutoloadDev = $rootManifest['autoload-dev'];
        $rootScripts = $rootManifest['scripts'];
        $rootRepositories = $rootManifest['repositories'];
        self::assertIsArray($rootRequire);
        self::assertIsArray($rootAutoloadDev);
        self::assertIsArray($rootScripts);
        self::assertIsArray($rootRepositories);
        $rootAutoloadDevPsr4 = $rootAutoloadDev['psr-4'];
        $validatePackages = $rootScripts['validate:packages'];
        self::assertIsArray($rootAutoloadDevPsr4);
        self::assertIsArray($validatePackages);
        self::assertSame('@dev', $rootRequire['kongono/security-runtime']);
        self::assertSame('packages/security-runtime/tests/', $rootAutoloadDevPsr4['Kongono\\SecurityRuntime\\Tests\\']);
        self::assertContains('composer --working-dir=packages/security-runtime validate --no-check-publish', $validatePackages);
        self::assertContains(['type' => 'path', 'url' => 'packages/security-runtime'], $rootRepositories);

        $packageManifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($packageManifests);
        self::assertCount(75, $packageManifests);
        foreach ($packageManifests as $packageManifestPath) {
            $packageManifest = json_decode((string) file_get_contents($packageManifestPath), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($packageManifest);
            self::assertIsArray($packageManifest['require']);
            if (in_array($packageManifest['name'], ['kongono/security-http', 'kongono/security-runtime'], true)) {
                continue;
            }
            self::assertArrayNotHasKey('kongono/security-runtime', $packageManifest['require']);
        }

        $sourceFiles = $this->recursivePhpFiles($root . '/packages/security-runtime/src');
        $testFiles = $this->recursivePhpFiles($root . '/packages/security-runtime/tests');
        self::assertCount(10, $sourceFiles);
        self::assertCount(6, $testFiles);
        foreach ($sourceFiles as $sourceFile) {
            $source = file_get_contents($sourceFile);
            self::assertIsString($source);
            self::assertSame(1, substr_count($source, 'declare(strict_types=1);'));
            self::assertSame(1, substr_count($source, '#[Api('));
            self::assertSame(0, preg_match('/\$_(?:SERVER|ENV|COOKIE|SESSION)|\$GLOBALS|\b(?:getenv|putenv|time|microtime|date|class_exists|interface_exists|trait_exists|enum_exists|spl_autoload_register|file_get_contents|fopen|curl_exec|stream_socket_client|serialize|unserialize|json_encode)\s*\(/', $source));
            self::assertSame(0, preg_match('/use Kongono\\\\(?:ApplicationGraph|Authorization|Chie|Console|Database|DependencyInjection|Hantei|Http|Kernel|Knowledge|Oshie|Persistence|PhpCgi|Routing|Web)\\\\/', $source));
            self::assertSame(0, preg_match('/\b(?:ServiceLocator|Container|Registry|Middleware|guard|enforce|authorize|permit|deny|retry|fallback)\b/i', $source));
            if (!str_ends_with(str_replace('\\', '/', $sourceFile), '/GeneratedSecurityApplication.php')) {
                self::assertStringNotContainsString('Reflection', $source);
            }
        }

        self::assertSame(['anonymous', 'authenticated'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\SecurityRuntime\\SecurityContextFactory')));
        self::assertSame(['beginAnonymousOperation', 'beginAuthenticatedOperation', 'isRetired', 'shutdown', 'start'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\SecurityRuntime\\GeneratedSecurityApplication')));
        self::assertSame(['__construct', 'close', 'isClosed', 'root', 'securityContext'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\SecurityRuntime\\GeneratedSecurityOperation')));
        self::assertSame([], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\SecurityRuntime\\SecurityOperationRoot')));
        self::assertSame(['cases', 'context', 'from', 'operation', 'remediation', 'title', 'tryFrom', 'violatedInvariant'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\SecurityRuntime\\Diagnostic\\SecurityRuntimeDiagnostic')));
        self::assertSame(['diagnosticContext', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\SecurityRuntime\\Exception\\ExceptionInterface')));
        self::assertSame(['__toString', 'diagnosticContext', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\SecurityRuntime\\Exception\\SecurityRuntimeException')));
        self::assertSame(['applicationBoundary', 'applicationRoot', 'operationInputs', 'operationRootCount', 'operationRootShape', 'rootAccess'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\SecurityRuntime\\Exception\\InvalidGeneratedSecurityProfileException')));
        self::assertSame(['facts', 'outcome', 'principalIdentity'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\SecurityRuntime\\Exception\\InvalidSecurityContextVerificationException')));
        self::assertSame(['applicationRetired', 'operationClosed'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\SecurityRuntime\\Exception\\SecurityRuntimeLifecycleException')));
        self::assertSame([
            'KGN-SECURITY-RUNTIME-1001',
            'KGN-SECURITY-RUNTIME-1002',
            'KGN-SECURITY-RUNTIME-1003',
        ], array_column(('Kongono\\SecurityRuntime\\Diagnostic\\SecurityRuntimeDiagnostic')::cases(), 'value'));

        $applicationSource = file_get_contents($root . '/packages/security-runtime/src/GeneratedSecurityApplication.php');
        self::assertIsString($applicationSource);
        self::assertSame(2, substr_count($applicationSource, 'new ReflectionClass'));
        $profileValidation = strpos($applicationSource, '$rootMethod = self::validateProfile($wiring);');
        $generatedStart = strpos($applicationSource, '$boundary = $wiring->start(...$applicationInputs);');
        self::assertIsInt($profileValidation);
        self::assertIsInt($generatedStart);
        self::assertLessThan($generatedStart, $profileValidation);
    }

    public function testPhase9fSecurityPackagesHaveExactDependenciesSurfacesAndIsolation(): void
    {
        $root = dirname(__DIR__, 2);
        $enforcement = json_decode((string) file_get_contents($root . '/packages/security-enforcement/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        $http = json_decode((string) file_get_contents($root . '/packages/security-http/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($enforcement);
        self::assertIsArray($http);
        self::assertSame('kongono/security-enforcement', $enforcement['name']);
        self::assertSame([
            'php' => '>=8.5.0 <8.6.0',
            'kongono/api-metadata' => '*',
            'kongono/authorization' => '*',
            'kongono/diagnostics' => '*',
            'kongono/identity-access' => '*',
        ], $enforcement['require']);
        self::assertSame('kongono/security-http', $http['name']);
        self::assertSame([
            'php' => '>=8.5.0 <8.6.0',
            'kongono/api-metadata' => '*',
            'kongono/authentication' => '*',
            'kongono/diagnostics' => '*',
            'kongono/security-runtime' => '*',
            'psr/http-message' => '^2.0',
        ], $http['require']);

        $rootManifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($rootManifest);
        $rootRequire = $rootManifest['require'];
        $rootAutoloadDev = $rootManifest['autoload-dev'];
        $rootScripts = $rootManifest['scripts'];
        self::assertIsArray($rootRequire);
        self::assertIsArray($rootAutoloadDev);
        self::assertIsArray($rootScripts);
        $rootAutoloadDevPsr4 = $rootAutoloadDev['psr-4'];
        $validatePackages = $rootScripts['validate:packages'];
        self::assertIsArray($rootAutoloadDevPsr4);
        self::assertIsArray($validatePackages);
        self::assertSame('@dev', $rootRequire['kongono/security-enforcement']);
        self::assertSame('@dev', $rootRequire['kongono/security-http']);
        self::assertSame('packages/security-enforcement/tests/', $rootAutoloadDevPsr4['Kongono\\SecurityEnforcement\\Tests\\']);
        self::assertSame('packages/security-http/tests/', $rootAutoloadDevPsr4['Kongono\\SecurityHttp\\Tests\\']);
        self::assertContains('composer --working-dir=packages/security-enforcement validate --no-check-publish', $validatePackages);
        self::assertContains('composer --working-dir=packages/security-http validate --no-check-publish', $validatePackages);

        self::assertSame(['action', 'execute', 'resource'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\SecurityEnforcement\\ProtectedCapability')));
        self::assertSame(['enforce'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\SecurityEnforcement\\ProtectedCapabilityEnforcer')));
        self::assertSame(['expire', 'issue'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\SecurityHttp\\BrowserSessionCookieEmitter')));
        self::assertSame(['__construct', 'begin'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\SecurityHttp\\BrowserSessionSecurityGateway')));
        self::assertSame(['__construct', 'close', 'isClosed', 'root', 'securityContext'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\SecurityHttp\\BrowserSessionSecurityOperation')));
        self::assertSame(['NAME', 'COOKIE_NAME', 'CSRF_HEADER_NAME'], array_keys((new ReflectionClass('Kongono\\SecurityHttp\\BrowserSessionProfile'))->getConstants()));

        foreach ([
            ...$this->recursivePhpFiles($root . '/packages/security-enforcement/src'),
            ...$this->recursivePhpFiles($root . '/packages/security-http/src'),
        ] as $sourceFile) {
            $source = file_get_contents($sourceFile);
            self::assertIsString($source);
            self::assertSame(0, preg_match('/\$_(?:SERVER|ENV|COOKIE|SESSION)|\$GLOBALS|\b(?:getenv|putenv|time|microtime|date|random_bytes|hash|password_hash|setcookie|serialize|unserialize|eval)\s*\(/', $source));
            self::assertStringNotContainsString('RequestAttribute', $source);
            self::assertStringNotContainsString('ServiceLocator', $source);
        }
    }

    public function testPersistencePackageContractsDependenciesDiagnosticsAndIsolationAreExact(): void
    {
        $root = dirname(__DIR__, 2);
        $manifest = json_decode((string) file_get_contents($root . '/packages/persistence/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertSame('kongono/persistence', $manifest['name']);
        self::assertSame('Neutral operation-scoped persistence contracts for Kongono Framework.', $manifest['description']);
        self::assertSame('library', $manifest['type']);
        self::assertSame([
            'kongono/api-metadata' => '*',
            'kongono/database' => '*',
            'kongono/diagnostics' => '*',
            'php' => '>=8.5.0 <8.6.0',
        ], $manifest['require']);
        $autoload = $manifest['autoload'];
        $autoloadDev = $manifest['autoload-dev'];
        self::assertIsArray($autoload);
        self::assertIsArray($autoloadDev);
        self::assertSame(['Kongono\\Persistence\\' => 'src/'], $autoload['psr-4']);
        self::assertSame(['Kongono\\Persistence\\Tests\\' => 'tests/'], $autoloadDev['psr-4']);

        $rootManifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($rootManifest);
        $rootRequire = $rootManifest['require'];
        $rootAutoloadDev = $rootManifest['autoload-dev'];
        $rootScripts = $rootManifest['scripts'];
        self::assertIsArray($rootRequire);
        self::assertIsArray($rootAutoloadDev);
        self::assertIsArray($rootScripts);
        $rootAutoloadDevPsr4 = $rootAutoloadDev['psr-4'];
        $validatePackages = $rootScripts['validate:packages'];
        self::assertIsArray($rootAutoloadDevPsr4);
        self::assertIsArray($validatePackages);
        self::assertSame('@dev', $rootRequire['kongono/persistence']);
        self::assertSame('packages/persistence/tests/', $rootAutoloadDevPsr4['Kongono\\Persistence\\Tests\\']);
        self::assertContains('composer --working-dir=packages/persistence validate --no-check-publish', $validatePackages);

        self::assertSame(
            array_map(static fn(int $ordinal): string => sprintf('KGN-PERSISTENCE-%04d', $ordinal), range(1001, 1009)),
            array_map(static fn(PersistenceDiagnostic $diagnostic): string => $diagnostic->value, PersistenceDiagnostic::cases()),
        );
        self::assertSame([
            'Kongono\\Persistence\\AggregateCascadeExecutor' => ['execute'],
            'Kongono\\Persistence\\AggregateCascadePlan' => ['__construct', 'aggregateName', 'maxChanges', 'policies', 'rootType'],
            'Kongono\\Persistence\\AggregateLoadExecutor' => ['load'],
            'Kongono\\Persistence\\AggregateLoadPlan' => ['__construct', 'aggregateName', 'maxTotalObjects', 'methodName', 'relationships', 'rootType'],
            'Kongono\\Persistence\\AggregateRepository' => ['aggregateName', 'cascadePlan', 'loadPlans', 'rootType'],
            'Kongono\\Persistence\\CascadeAction' => ['cases', 'from', 'tryFrom'],
            'Kongono\\Persistence\\ChangeExecutionOutcome' => ['cases'],
            'Kongono\\Persistence\\ChangeKind' => ['cases'],
            'Kongono\\Persistence\\ChangeSet' => ['__construct', 'changes', 'count', 'isEmpty'],
            'Kongono\\Persistence\\ConcurrencyToken' => ['__construct', 'equals', 'scheme', 'value'],
            'Kongono\\Persistence\\ContextState' => ['cases'],
            'Kongono\\Persistence\\EntityIdentity' => ['__construct', 'entityType', 'equals', 'key'],
            'Kongono\\Persistence\\EntityState' => ['cases'],
            'Kongono\\Persistence\\FlushOutcome' => ['cases'],
            'Kongono\\Persistence\\FlushResult' => ['__construct', 'absentCount', 'appliedCount', 'attemptedCount', 'conflictCount', 'outcome'],
            'Kongono\\Persistence\\LoadedAggregate' => ['__construct', 'relationship', 'relationships', 'root'],
            'Kongono\\Persistence\\OperationPersistenceContext' => ['changes', 'entityForIdentity', 'entityState', 'flush', 'forManaged', 'forNew', 'identityOf', 'markRemoved', 'retire', 'state'],
            'Kongono\\Persistence\\PersistenceChange' => ['__construct', 'changedFields', 'entity', 'expectedConcurrencyToken', 'identity', 'kind'],
            'Kongono\\Persistence\\PersistenceChangeExecutor' => ['execute'],
            'Kongono\\Persistence\\PersistenceContext' => ['changes', 'entityForIdentity', 'entityState', 'identityOf', 'retire', 'state'],
            'Kongono\\Persistence\\PersistenceStateAdapter' => ['changes', 'deletion', 'entityType', 'identity', 'insertion', 'snapshot'],
            'Kongono\\Persistence\\PersistenceUnitOfWork' => ['flush', 'markRemoved'],
            'Kongono\\Persistence\\RelationshipCardinality' => ['cases', 'from', 'tryFrom'],
            'Kongono\\Persistence\\RelationshipCascadePolicy' => ['__construct', 'persist', 'relationshipName', 'remove', 'targetType'],
            'Kongono\\Persistence\\RelationshipLoad' => ['__construct', 'cardinality', 'maxRelated', 'nullable', 'order', 'relationshipName', 'targetType'],
        ], array_combine(
            $surfaceTypes = [
                'Kongono\\Persistence\\AggregateCascadeExecutor',
                'Kongono\\Persistence\\AggregateCascadePlan',
                'Kongono\\Persistence\\AggregateLoadExecutor',
                'Kongono\\Persistence\\AggregateLoadPlan',
                'Kongono\\Persistence\\AggregateRepository',
                'Kongono\\Persistence\\CascadeAction',
                'Kongono\\Persistence\\ChangeExecutionOutcome',
                'Kongono\\Persistence\\ChangeKind',
                'Kongono\\Persistence\\ChangeSet',
                'Kongono\\Persistence\\ConcurrencyToken',
                'Kongono\\Persistence\\ContextState',
                'Kongono\\Persistence\\EntityIdentity',
                'Kongono\\Persistence\\EntityState',
                'Kongono\\Persistence\\FlushOutcome',
                'Kongono\\Persistence\\FlushResult',
                'Kongono\\Persistence\\LoadedAggregate',
                'Kongono\\Persistence\\OperationPersistenceContext',
                'Kongono\\Persistence\\PersistenceChange',
                'Kongono\\Persistence\\PersistenceChangeExecutor',
                'Kongono\\Persistence\\PersistenceContext',
                'Kongono\\Persistence\\PersistenceStateAdapter',
                'Kongono\\Persistence\\PersistenceUnitOfWork',
                'Kongono\\Persistence\\RelationshipCardinality',
                'Kongono\\Persistence\\RelationshipCascadePolicy',
                'Kongono\\Persistence\\RelationshipLoad',
            ],
            array_map(fn(string $type): array => $this->declaredPublicMethodNames(new ReflectionClass($type)), $surfaceTypes),
        ));

        $context = new ReflectionClass('Kongono\\Persistence\\PersistenceContext');
        self::assertTrue($context->isInterface());
        self::assertSame(['Kongono\\Persistence\\OperationPersistenceContext'], array_values(array_filter(get_declared_classes(), static fn(string $class): bool => is_subclass_of($class, $context->getName()))));
        foreach (['flush', 'persist', 'remove', 'find', 'attach', 'detach', 'clear', 'execute', 'beginTransaction'] as $forbiddenMethod) {
            self::assertFalse($context->hasMethod($forbiddenMethod));
        }

        $sourceFiles = $this->recursivePhpFiles($root . '/packages/persistence/src');
        $testFiles = $this->recursivePhpFiles($root . '/packages/persistence/tests');
        self::assertCount(36, $sourceFiles);
        self::assertCount(22, $testFiles);
        $statuses = array_count_values(array_map(
            static fn(string $type): string => self::EXPECTED_STATUS[$type]->value,
            array_filter(array_keys(self::EXPECTED_STATUS), static fn(string $type): bool => str_starts_with($type, 'Kongono\\Persistence\\')),
        ));
        self::assertSame(['experimental' => 32, 'internal' => 3, 'public' => 1], $statuses);
        foreach ($sourceFiles as $sourceFile) {
            $source = file_get_contents($sourceFile);
            self::assertIsString($source);
            self::assertSame(0, preg_match('/Kongono\\\\(?:DatabaseSql|DatabaseMapping|DatabaseApplication|Composition|Kernel|DependencyInjection|Http)|\\bPDO\\b|Reflection|Proxy|Generator|ServiceLocator|Container|IdentityMap|ActiveRecord|LazyLoad|eval\\s*\\(/i', $source));
            self::assertStringNotContainsString('JsonSerializable', $source);
            self::assertStringNotContainsString('Stringable', $source);
        }

        $packageManifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($packageManifests);
        self::assertCount(75, $packageManifests);
        foreach ($packageManifests as $packageManifest) {
            if (
                str_ends_with(str_replace('\\', '/', $packageManifest), '/persistence/composer.json')
                || str_ends_with(str_replace('\\', '/', $packageManifest), '/persistence-build/composer.json')
            ) {
                continue;
            }
            $other = json_decode((string) file_get_contents($packageManifest), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($other);
            $otherRequire = $other['require'];
            self::assertIsArray($otherRequire);
            self::assertArrayNotHasKey('kongono/persistence', $otherRequire);
        }

        foreach (['.php-cs-fixer.dist.php', 'phpstan.neon.dist', 'phpunit.xml.dist'] as $configuration) {
            $contents = file_get_contents($root . '/' . $configuration);
            self::assertIsString($contents);
            self::assertSame(1, substr_count($contents, 'packages/persistence/src'));
            self::assertSame(1, substr_count($contents, 'packages/persistence/tests'));
        }

        $productionCount = 0;
        $packageTestCount = 0;
        $packageDirectories = glob($root . '/packages/*', GLOB_ONLYDIR);
        self::assertIsArray($packageDirectories);
        foreach ($packageDirectories as $packageDirectory) {
            if (is_dir($packageDirectory . '/src')) {
                $productionCount += count($this->recursivePhpFiles($packageDirectory . '/src'));
            }
            if (is_dir($packageDirectory . '/tests')) {
                $packageTestCount += count($this->recursivePhpFiles($packageDirectory . '/tests'));
            }
        }
        self::assertSame(1358, $productionCount);
        self::assertSame(686, $packageTestCount);
        self::assertCount(80, $this->recursivePhpFiles($root . '/tests'));

        foreach ([
            'Kongono\\Persistence\\Exception\\InvalidPersistenceValueException',
            'Kongono\\Persistence\\Exception\\InvalidPersistenceStateException',
            'Kongono\\Persistence\\Exception\\InvalidAggregatePersistenceException',
            'Kongono\\Persistence\\Exception\\PersistenceOperationException',
            'Kongono\\Persistence\\Exception\\PersistenceCleanupException',
            'Kongono\\Persistence\\Exception\\CombinedPersistenceFailureException',
            'Kongono\\Persistence\\Exception\\PersistenceUncertainStateException',
        ] as $failure) {
            $reflection = new ReflectionClass($failure);
            self::assertTrue($reflection->isFinal());
            self::assertTrue($reflection->implementsInterface(PersistenceExceptionInterface::class));
            $parent = $reflection->getParentClass();
            self::assertNotFalse($parent);
            self::assertSame('Kongono\\Persistence\\Exception\\PersistenceException', $parent->getName());
        }
    }

    public function testPersistenceBuildPackageBoundaryStatusesAndToolingAreExact(): void
    {
        $root = dirname(__DIR__, 2);
        $manifest = json_decode((string) file_get_contents($root . '/packages/persistence-build/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertSame('kongono/persistence-build', $manifest['name']);
        self::assertSame('library', $manifest['type']);
        self::assertSame([
            'ext-hash' => '*',
            'ext-json' => '*',
            'kongono/api-metadata' => '*',
            'kongono/database' => '*',
            'kongono/database-mapping' => '*',
            'kongono/diagnostics' => '*',
            'kongono/persistence' => '*',
            'php' => '>=8.5.0 <8.6.0',
        ], $manifest['require']);
        $autoload = $manifest['autoload'];
        $autoloadDev = $manifest['autoload-dev'];
        self::assertIsArray($autoload);
        self::assertIsArray($autoloadDev);
        self::assertIsArray($autoload['psr-4']);
        self::assertIsArray($autoloadDev['psr-4']);
        self::assertSame(['Kongono\\PersistenceBuild\\' => 'src/'], $autoload['psr-4']);
        self::assertSame(['Kongono\\PersistenceBuild\\Tests\\' => 'tests/'], $autoloadDev['psr-4']);
        self::assertArrayNotHasKey('scripts', $manifest);

        $rootManifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($rootManifest);
        $rootRequire = $rootManifest['require'];
        $rootAutoloadDev = $rootManifest['autoload-dev'];
        $rootScripts = $rootManifest['scripts'];
        self::assertIsArray($rootRequire);
        self::assertIsArray($rootAutoloadDev);
        self::assertIsArray($rootScripts);
        $rootAutoloadDevPsr4 = $rootAutoloadDev['psr-4'];
        $validatePackages = $rootScripts['validate:packages'];
        self::assertIsArray($rootAutoloadDevPsr4);
        self::assertIsArray($validatePackages);
        self::assertSame('@dev', $rootRequire['kongono/persistence-build']);
        self::assertSame('packages/persistence-build/tests/', $rootAutoloadDevPsr4['Kongono\\PersistenceBuild\\Tests\\']);
        self::assertContains('composer --working-dir=packages/persistence-build validate --no-check-publish', $validatePackages);

        $productionFiles = $this->recursivePhpFiles($root . '/packages/persistence-build/src');
        $testFiles = $this->recursivePhpFiles($root . '/packages/persistence-build/tests');
        self::assertCount(52, $productionFiles);
        self::assertCount(28, $testFiles);
        $statuses = array_count_values(array_map(
            static fn(string $type): string => self::EXPECTED_STATUS[$type]->value,
            array_filter(array_keys(self::EXPECTED_STATUS), static fn(string $type): bool => str_starts_with($type, 'Kongono\\PersistenceBuild\\')),
        ));
        self::assertSame(['experimental' => 38, 'internal' => 13, 'public' => 1], $statuses);
        self::assertSame(
            array_map(static fn(int $ordinal): string => sprintf('KGN-PERSISTENCE-BUILD-%04d', $ordinal), range(1001, 1020)),
            array_map(static fn(PersistenceBuildDiagnostic $diagnostic): string => $diagnostic->value, PersistenceBuildDiagnostic::cases()),
        );

        foreach ([
            'Kongono\\PersistenceBuild\\Generated\\GeneratedAggregateRepositoryArtifact' => ['__construct', 'aggregateRepositoryGeneratorImplementationSha256', 'canonicalBytes', 'classMap', 'generatedFileSha256', 'generatedNamespace', 'persistenceArtifactSha256', 'persistenceModelSha256', 'persistenceStateGeneratorImplementationSha256', 'sourcePath', 'sourceSha256'],
            'Kongono\\PersistenceBuild\\Generated\\GeneratedAggregateRepositoryGenerator' => ['generate'],
            'Kongono\\PersistenceBuild\\Generated\\GeneratedAggregateRepositoryPublisher' => ['publish'],
            'Kongono\\PersistenceBuild\\Generated\\GeneratedAggregateRepositoryValidator' => ['validateCurrent', 'validateStructure'],
            'Kongono\\PersistenceBuild\\Generated\\GeneratedPersistenceStateArtifact' => ['__construct', 'canonicalBytes', 'classMap', 'generatedFileSha256', 'generatedNamespace', 'persistenceArtifactSha256', 'persistenceBuilderImplementationSha256', 'persistenceGeneratorImplementationSha256', 'persistenceModelSha256'],
            'Kongono\\PersistenceBuild\\Generated\\GeneratedPersistenceStateGenerator' => ['generate'],
            'Kongono\\PersistenceBuild\\Generated\\GeneratedPersistenceStatePublisher' => ['publish'],
            'Kongono\\PersistenceBuild\\Generated\\GeneratedPersistenceStateValidator' => ['validateCurrent', 'validateStructure'],
            'Kongono\\PersistenceBuild\\Runtime\\GeneratedPersistenceLoader' => ['loadCurrent'],
            'Kongono\\PersistenceBuild\\Runtime\\LoadedGeneratedPersistence' => ['__construct', 'aggregateRepositoryClasses', 'entityStateClasses', 'generatedRepositoryNamespace', 'generatedRepositoryPath', 'generatedRepositorySha256', 'generatedStateNamespace', 'generatedStatePath', 'generatedStateSha256', 'persistenceArtifactSha256'],
        ] as $type => $methods) {
            $reflection = new ReflectionClass($type);
            self::assertTrue($reflection->isFinal());
            self::assertSame($methods, $this->declaredPublicMethodNames($reflection));
        }

        $packageManifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($packageManifests);
        self::assertCount(75, $packageManifests);
        foreach ($packageManifests as $packageManifest) {
            if (str_ends_with(str_replace('\\', '/', $packageManifest), '/persistence-build/composer.json')
                || str_ends_with(str_replace('\\', '/', $packageManifest), '/persistence-application/composer.json')) {
                continue;
            }
            $other = json_decode((string) file_get_contents($packageManifest), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($other);
            $otherRequire = $other['require'];
            self::assertIsArray($otherRequire);
            self::assertArrayNotHasKey('kongono/persistence-build', $otherRequire);
        }
        foreach (['.php-cs-fixer.dist.php', 'phpstan.neon.dist', 'phpunit.xml.dist'] as $configuration) {
            $contents = file_get_contents($root . '/' . $configuration);
            self::assertIsString($contents);
            self::assertSame(1, substr_count($contents, 'packages/persistence-build/src'));
            self::assertSame(1, substr_count($contents, 'packages/persistence-build/tests'));
        }
    }

    public function testDatabaseSqlPackagesContractsDependenciesAndForbiddenBoundariesAreExact(): void
    {
        $root = dirname(__DIR__, 2);
        $neutralManifest = json_decode((string) file_get_contents($root . '/packages/database-sql/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        $compilerManifest = json_decode((string) file_get_contents($root . '/packages/database-sql-pgsql/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($neutralManifest);
        self::assertIsArray($compilerManifest);
        $neutralRequire = $neutralManifest['require'];
        $compilerRequire = $compilerManifest['require'];
        $neutralAutoload = $neutralManifest['autoload'];
        $compilerAutoload = $compilerManifest['autoload'];
        self::assertIsArray($neutralRequire);
        self::assertIsArray($compilerRequire);
        self::assertIsArray($neutralAutoload);
        self::assertIsArray($compilerAutoload);
        $neutralPsr4 = $neutralAutoload['psr-4'];
        $compilerPsr4 = $compilerAutoload['psr-4'];
        self::assertIsArray($neutralPsr4);
        self::assertIsArray($compilerPsr4);
        self::assertSame([
            'kongono/api-metadata' => '*',
            'kongono/database' => '*',
            'kongono/diagnostics' => '*',
            'php' => '>=8.5.0 <8.6.0',
        ], $neutralRequire);
        self::assertSame([
            'kongono/database' => '*',
            'kongono/database-sql' => '*',
            'php' => '>=8.5.0 <8.6.0',
        ], $compilerRequire);
        self::assertSame(['Kongono\\DatabaseSql\\' => 'src/'], $neutralPsr4);
        self::assertSame(['Kongono\\DatabaseSqlPgsql\\' => 'src/'], $compilerPsr4);

        $databaseManifest = json_decode((string) file_get_contents($root . '/packages/database/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        $adapterManifest = json_decode((string) file_get_contents($root . '/packages/database-pdo-pgsql/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($databaseManifest);
        self::assertIsArray($adapterManifest);
        $databaseRequire = $databaseManifest['require'];
        $adapterRequire = $adapterManifest['require'];
        self::assertIsArray($databaseRequire);
        self::assertIsArray($adapterRequire);
        self::assertArrayNotHasKey('kongono/database-sql', $databaseRequire);
        self::assertArrayNotHasKey('kongono/database-sql', $adapterRequire);

        $rootManifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($rootManifest);
        $rootRequire = $rootManifest['require'];
        $rootAutoloadDev = $rootManifest['autoload-dev'];
        $rootScripts = $rootManifest['scripts'];
        self::assertIsArray($rootRequire);
        self::assertIsArray($rootAutoloadDev);
        self::assertIsArray($rootScripts);
        $rootAutoloadDevPsr4 = $rootAutoloadDev['psr-4'];
        $validatePackages = $rootScripts['validate:packages'];
        self::assertIsArray($rootAutoloadDevPsr4);
        self::assertIsArray($validatePackages);
        self::assertSame('@dev', $rootRequire['kongono/database-sql']);
        self::assertSame('@dev', $rootRequire['kongono/database-sql-pgsql']);
        self::assertSame('packages/database-sql/tests/', $rootAutoloadDevPsr4['Kongono\\DatabaseSql\\Tests\\']);
        self::assertSame('packages/database-sql-pgsql/tests/', $rootAutoloadDevPsr4['Kongono\\DatabaseSqlPgsql\\Tests\\']);
        self::assertCount(75, $validatePackages);

        self::assertSame(
            ['KGN-SQL-1001', 'KGN-SQL-1002', 'KGN-SQL-1003'],
            array_map(static fn(SqlDiagnostic $diagnostic): string => $diagnostic->value, SqlDiagnostic::cases()),
        );
        self::assertSame(
            ['Select', 'Insert', 'Update', 'Delete', 'Returning', 'LimitOffset', 'NullOrdering', 'Like', 'InList'],
            array_map(static fn(\Kongono\DatabaseSql\SqlDialectCapability $capability): string => $capability->name, \Kongono\DatabaseSql\SqlDialectCapability::cases()),
        );

        $surfaces = [
            'Kongono\\DatabaseSql\\SqlPlan' => ['kind', 'requiredCapabilities'],
            'Kongono\\DatabaseSql\\SqlDialectCompiler' => ['capabilities', 'compile'],
            'Kongono\\DatabaseSql\\SqlIdentifier' => ['__construct', 'value'],
            'Kongono\\DatabaseSql\\SqlQualifiedName' => ['__construct', 'segments'],
            'Kongono\\DatabaseSql\\SqlMutationScope' => ['allRows', 'forPredicate', 'isAllRows', 'predicate'],
            'Kongono\\DatabaseSqlPgsql\\PostgreSql17Compiler' => ['capabilities', 'compile'],
        ];
        foreach ($surfaces as $type => $methods) {
            self::assertSame($methods, $this->declaredPublicMethodNames(new ReflectionClass($type)));
        }
        $compiler = new ReflectionClass('Kongono\\DatabaseSqlPgsql\\PostgreSql17Compiler');
        self::assertTrue($compiler->isFinal());
        self::assertTrue($compiler->isReadOnly());

        $neutralFiles = $this->recursivePhpFiles($root . '/packages/database-sql/src');
        $compilerFiles = $this->recursivePhpFiles($root . '/packages/database-sql-pgsql/src');
        self::assertCount(34, $neutralFiles);
        self::assertCount(2, $compilerFiles);
        foreach ([...$neutralFiles, ...$compilerFiles] as $file) {
            $source = file_get_contents($file);
            self::assertIsString($source);
            self::assertSame(0, preg_match('/\\bPDO(?:Statement|Exception)?\\b|pdo_pgsql|pgsql:/i', $source));
            self::assertSame(0, preg_match('/RawPlan|RawExpression|LiteralExpression|UnsafeFragment|Interpolation|SchemaDefinition|Migration|Mapper|Repository|IdentityMap|UnitOfWork|ActiveRecord|LazyLoad/i', $source));
        }
    }

    public function testDatabaseMappingPackageContractAndBoundariesAreExact(): void
    {
        $root = dirname(__DIR__, 2);
        $manifest = json_decode((string) file_get_contents($root . '/packages/database-mapping/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertSame('kongono/database-mapping', $manifest['name']);
        self::assertSame('Compile-time mapping artifacts and generated typed data access for Kongono Framework.', $manifest['description']);
        self::assertSame('library', $manifest['type']);
        self::assertSame([
            'ext-hash' => '*',
            'ext-json' => '*',
            'kongono/api-metadata' => '*',
            'kongono/database' => '*',
            'kongono/database-sql' => '*',
            'kongono/diagnostics' => '*',
            'php' => '>=8.5.0 <8.6.0',
        ], $manifest['require']);
        self::assertIsArray($manifest['autoload']);
        self::assertIsArray($manifest['autoload']['psr-4']);
        self::assertIsArray($manifest['autoload-dev']);
        self::assertIsArray($manifest['autoload-dev']['psr-4']);
        self::assertSame(['Kongono\\DatabaseMapping\\' => 'src/'], $manifest['autoload']['psr-4']);
        self::assertSame(['Kongono\\DatabaseMapping\\Tests\\' => 'tests/'], $manifest['autoload-dev']['psr-4']);
        foreach (['kongono/database-pdo-pgsql', 'kongono/database-migration', 'kongono/database-migration-pgsql', 'kongono/database-sql-pgsql', 'kongono/composition'] as $forbiddenDependency) {
            self::assertArrayNotHasKey($forbiddenDependency, $manifest['require']);
        }

        $rootManifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($rootManifest);
        self::assertIsArray($rootManifest['require']);
        self::assertIsArray($rootManifest['autoload-dev']);
        self::assertIsArray($rootManifest['autoload-dev']['psr-4']);
        self::assertIsArray($rootManifest['scripts']);
        self::assertIsArray($rootManifest['scripts']['validate:packages']);
        self::assertSame('@dev', $rootManifest['require']['kongono/database-mapping']);
        self::assertSame('packages/database-mapping/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\DatabaseMapping\\Tests\\']);
        self::assertContains('composer --working-dir=packages/database-mapping validate --no-check-publish', $rootManifest['scripts']['validate:packages']);

        self::assertSame(
            array_map(static fn(int $ordinal): string => sprintf('KGN-MAPPING-%04d', $ordinal), range(1001, 1009)),
            array_map(static fn(\Kongono\DatabaseMapping\Diagnostic\MappingDiagnostic $diagnostic): string => $diagnostic->value, \Kongono\DatabaseMapping\Diagnostic\MappingDiagnostic::cases()),
        );
        self::assertSame(['build'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\DatabaseMapping\\Build\\MappingArtifactBuilder')));
        self::assertSame(['publish'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\DatabaseMapping\\Build\\MappingArtifactPublisher')));
        self::assertSame(['validateCurrent', 'validateStructure'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\DatabaseMapping\\Build\\MappingArtifactValidator')));
        self::assertSame(['generate'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\DatabaseMapping\\Generated\\GeneratedDataAccessGenerator')));
        self::assertSame(['publish'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\DatabaseMapping\\Generated\\GeneratedDataAccessPublisher')));
        self::assertSame(['validateCurrent', 'validateStructure'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\DatabaseMapping\\Generated\\GeneratedDataAccessValidator')));
        self::assertSame(['loadCurrent'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\DatabaseMapping\\Runtime\\GeneratedDataAccessLoader')));
        self::assertSame(['__construct', 'classes', 'generatedFileSha256', 'generatedNamespace', 'mappingArtifactSha256'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\DatabaseMapping\\Runtime\\LoadedGeneratedDataAccess')));

        $sourceFiles = $this->recursivePhpFiles($root . '/packages/database-mapping/src');
        self::assertCount(31, $sourceFiles);
        foreach ($sourceFiles as $file) {
            $source = file_get_contents($file);
            self::assertIsString($source);
            self::assertSame(0, preg_match('/\\bPDO(?:Statement|Exception)?\\b|pdo_pgsql|pgsql:/i', $source));
            self::assertSame(0, preg_match('/IdentityMap|UnitOfWork|ActiveRecord|LazyLoad|SchemaDefinition|Migration|Container|ServiceLocator|ReflectionProperty/i', $source));
        }
    }

    public function testDatabaseApplicationPackageAndDataSkeletonBoundariesAreExact(): void
    {
        $root = dirname(__DIR__, 2);
        $manifest = json_decode((string) file_get_contents($root . '/packages/database-application/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertSame('kongono/database-application', $manifest['name']);
        self::assertSame([
            'kongono/api-metadata' => '*',
            'kongono/composition' => '*',
            'kongono/database' => '*',
            'kongono/database-mapping' => '*',
            'kongono/diagnostics' => '*',
            'php' => '>=8.5.0 <8.6.0',
        ], $manifest['require']);
        $autoload = $manifest['autoload'] ?? null;
        $autoloadDev = $manifest['autoload-dev'] ?? null;
        self::assertIsArray($autoload);
        self::assertIsArray($autoloadDev);
        self::assertSame(['Kongono\\DatabaseApplication\\' => 'src/'], $autoload['psr-4'] ?? null);
        self::assertSame(['Kongono\\DatabaseApplication\\Tests\\' => 'tests/'], $autoloadDev['psr-4'] ?? null);
        foreach (['ext-pdo', 'ext-pdo_pgsql', 'kongono/database-pdo-pgsql', 'kongono/database-sql', 'kongono/database-sql-pgsql', 'kongono/kernel', 'kongono/web-application'] as $forbiddenDependency) {
            self::assertArrayNotHasKey($forbiddenDependency, $manifest['require']);
        }

        self::assertSame(['__construct', 'begin', 'capabilities', 'close', 'execute', 'isClosed'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\DatabaseApplication\\OperationDatabaseSession')));
        self::assertSame([], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\DatabaseApplication\\DataOperationRoot')));
        self::assertSame(['beginOperation', 'isRetired', 'shutdown', 'start'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\DatabaseApplication\\GeneratedDataApplication')));
        self::assertSame(['__construct', 'close', 'isClosed', 'root'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\DatabaseApplication\\GeneratedDataOperation')));
        self::assertSame(['build', 'check'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\DatabaseApplication\\Workflow\\DataApplicationBuildWorkflow')));
        self::assertSame(['build', 'check', 'loadPrepared', 'prepareBuild', 'prepareCheck', 'reverify'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\DatabaseApplication\\Workflow\\DataAccessBuildWorkflow')));
        self::assertSame(['__construct', 'applicationRoot', 'generatedDataAccessPath', 'generatedDataAccessSha256', 'generatedSnapshot', 'loadedDataAccess', 'mappingArtifactBytes', 'mappingArtifactPath', 'mappingArtifactSha256', 'mappingSnapshot'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\DatabaseApplication\\Workflow\\CurrentDataAccessBuild')));
        self::assertSame(['__construct', 'applicationRoot', 'generatedDataAccessSha256', 'generatedSnapshot', 'mappingArtifactSha256', 'mappingSnapshot', 'mappingSourceRelativePath'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\DatabaseApplication\\Workflow\\PreparedDataAccessBuild')));
        self::assertSame(
            ['KGN-DATABASE-APPLICATION-1001', 'KGN-DATABASE-APPLICATION-1002', 'KGN-DATABASE-APPLICATION-1003', 'KGN-DATABASE-APPLICATION-1004'],
            array_map(static fn(\Kongono\DatabaseApplication\Diagnostic\DatabaseApplicationDiagnostic $diagnostic): string => $diagnostic->value, \Kongono\DatabaseApplication\Diagnostic\DatabaseApplicationDiagnostic::cases()),
        );

        $sourceFiles = $this->recursivePhpFiles($root . '/packages/database-application/src');
        self::assertCount(16, $sourceFiles);
        foreach ($sourceFiles as $file) {
            $source = file_get_contents($file);
            self::assertIsString($source);
            self::assertSame(0, preg_match('/\\bPDO(?:Statement|Exception)?\\b|pdo_pgsql|pgsql:|getenv|putenv|register_shutdown_function|__destruct|ServiceLocator|Container|IdentityMap|UnitOfWork|ActiveRecord|LazyLoad|Migration/i', $source));
        }

        $skeletonManifest = json_decode((string) file_get_contents($root . '/skeletons/data-minimal/kongono.skeleton.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($skeletonManifest);
        self::assertSame('kongono.application-skeleton', $skeletonManifest['schema']);
        self::assertSame(1, $skeletonManifest['version']);
        $templatePaths = $skeletonManifest['template_paths'] ?? null;
        $derivedOutputPatterns = $skeletonManifest['derived_output_patterns'] ?? null;
        self::assertIsArray($templatePaths);
        self::assertIsArray($derivedOutputPatterns);
        self::assertContains('kongono.data-mapping.json', $templatePaths);
        self::assertContains('kongono.composition.json', $templatePaths);
        self::assertContains('var/kongono/build/kongono-data-access-v1-*.php', $derivedOutputPatterns);
        self::assertContains('var/kongono/build/kongono-wiring-v1-*.php', $derivedOutputPatterns);
        self::assertFileExists($root . '/skeletons/data-minimal/composer.lock');

        $minimalBytes = '';
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/skeletons/minimal', FilesystemIterator::SKIP_DOTS)) as $file) {
            if ($file instanceof SplFileInfo && $file->isFile()) {
                $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root . '/skeletons/minimal') + 1));
                if (!str_starts_with($relative, 'vendor/') && !str_starts_with($relative, 'var/kongono/build/')) {
                    $minimalBytes .= $relative . "\t" . hash_file('sha256', $file->getPathname()) . "\n";
                }
            }
        }
        self::assertNotSame('', $minimalBytes);
    }

    public function testDatabaseMigrationPackagesContractsDependenciesAndForbiddenBoundariesAreExact(): void
    {
        $root = dirname(__DIR__, 2);
        $neutralManifest = json_decode((string) file_get_contents($root . '/packages/database-migration/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        $postgresManifest = json_decode((string) file_get_contents($root . '/packages/database-migration-pgsql/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($neutralManifest);
        self::assertIsArray($postgresManifest);
        self::assertIsArray($neutralManifest['require']);
        self::assertIsArray($neutralManifest['autoload']);
        self::assertIsArray($neutralManifest['autoload']['psr-4']);
        self::assertIsArray($postgresManifest['require']);
        self::assertIsArray($postgresManifest['autoload']);
        self::assertIsArray($postgresManifest['autoload']['psr-4']);
        self::assertSame([
            'ext-hash' => '*',
            'ext-json' => '*',
            'kongono/api-metadata' => '*',
            'kongono/database' => '*',
            'kongono/database-sql' => '*',
            'kongono/diagnostics' => '*',
            'php' => '>=8.5.0 <8.6.0',
        ], $neutralManifest['require']);
        self::assertSame([
            'kongono/api-metadata' => '*',
            'kongono/database' => '*',
            'kongono/database-migration' => '*',
            'kongono/database-sql' => '*',
            'php' => '>=8.5.0 <8.6.0',
        ], $postgresManifest['require']);
        self::assertSame(['Kongono\\DatabaseMigration\\' => 'src/'], $neutralManifest['autoload']['psr-4']);
        self::assertSame(['Kongono\\DatabaseMigrationPgsql\\' => 'src/'], $postgresManifest['autoload']['psr-4']);

        $rootManifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($rootManifest);
        self::assertIsArray($rootManifest['require']);
        self::assertIsArray($rootManifest['autoload-dev']);
        self::assertIsArray($rootManifest['autoload-dev']['psr-4']);
        self::assertIsArray($rootManifest['scripts']);
        self::assertIsArray($rootManifest['scripts']['validate:packages']);
        self::assertSame('@dev', $rootManifest['require']['kongono/database-migration']);
        self::assertSame('@dev', $rootManifest['require']['kongono/database-migration-pgsql']);
        self::assertSame('packages/database-migration/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\DatabaseMigration\\Tests\\']);
        self::assertSame('packages/database-migration-pgsql/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\DatabaseMigrationPgsql\\Tests\\']);
        self::assertContains('composer --working-dir=packages/database-migration validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/database-migration-pgsql validate --no-check-publish', $rootManifest['scripts']['validate:packages']);

        self::assertSame(
            array_map(static fn(int $ordinal): string => sprintf('KGN-MIGRATION-%04d', $ordinal), range(1001, 1007)),
            array_map(static fn(MigrationDiagnostic $diagnostic): string => $diagnostic->value, MigrationDiagnostic::cases()),
        );

        $surfaces = [
            'Kongono\\DatabaseMigration\\MigrationCatalog' => ['__construct', 'canonicalBytes', 'definitions', 'sha256'],
            'Kongono\\DatabaseMigration\\MigrationDefinition' => ['__construct', 'apply', 'canonicalBytes', 'id', 'ordinal', 'revert', 'sha256'],
            'Kongono\\DatabaseMigration\\MigrationDirectionPlan' => ['__construct', 'operations', 'risk'],
            'Kongono\\DatabaseMigration\\MigrationOperation' => ['kind', 'risk'],
            'Kongono\\DatabaseMigration\\MigrationHistory' => ['__construct', 'count', 'records'],
            'Kongono\\DatabaseMigration\\MigrationPlanner' => ['applyPending', 'revertLast'],
            'Kongono\\DatabaseMigration\\MigrationExecutor' => ['applyPending', 'revertLast'],
            'Kongono\\DatabaseMigrationPgsql\\PostgreSql17MigrationCompiler' => ['compile'],
            'Kongono\\DatabaseMigrationPgsql\\PostgreSql17MigrationExecutor' => ['__construct', 'applyPending', 'revertLast'],
            'Kongono\\DatabaseMigrationPgsql\\PostgreSqlMigrationConfiguration' => ['__construct', 'advisoryLockKey', 'historyTable'],
        ];
        foreach ($surfaces as $type => $methods) {
            self::assertSame($methods, $this->declaredPublicMethodNames(new ReflectionClass($type)));
        }

        $neutralFiles = $this->recursivePhpFiles($root . '/packages/database-migration/src');
        $postgresFiles = $this->recursivePhpFiles($root . '/packages/database-migration-pgsql/src');
        self::assertCount(36, $neutralFiles);
        self::assertCount(4, $postgresFiles);
        foreach ($neutralFiles as $file) {
            $source = file_get_contents($file);
            self::assertIsString($source);
            self::assertSame(0, preg_match('/\\bPDO(?:Statement|Exception)?\\b|pdo_pgsql|pgsql:|PostgreSql/i', $source));
        }
        foreach ([...$neutralFiles, ...$postgresFiles] as $file) {
            $source = file_get_contents($file);
            self::assertIsString($source);
            self::assertSame(0, preg_match('/FilesystemIterator|RecursiveDirectoryIterator|ReflectionClass|glob\\s*\\(|scandir\\s*\\(|getenv\\s*\\(|service.?locator|live.?schema/i', $source));
            self::assertSame(0, preg_match('/MigrationDiscovery|MigrationLoader|MigrationCli|MigrationRepository|SchemaIntrospector|AutomaticRollback/i', $source));
        }
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

    public function testApplicationGraphContractPackageBoundaryAndIntegrationAreExact(): void
    {
        $root = dirname(__DIR__, 2);
        $package = $root . '/packages/application-graph-contract';
        $manifest = json_decode((string) file_get_contents($package . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame([
            'name' => 'kongono/application-graph-contract',
            'description' => "Versioned language-neutral contracts for Kongono's authoritative Application Graph.",
            'type' => 'library',
            'require' => [
                'kongono/api-metadata' => '*',
                'kongono/diagnostics' => '*',
                'php' => '>=8.5.0 <8.6.0',
            ],
            'autoload' => ['psr-4' => ['Kongono\\ApplicationGraphContract\\' => 'src/']],
            'autoload-dev' => ['psr-4' => ['Kongono\\ApplicationGraphContract\\Tests\\' => 'tests/']],
        ], $manifest);

        $sourceFiles = array_map(
            static fn(string $path): string => str_replace('\\', '/', substr($path, strlen($package . '/src/'))),
            $this->recursivePhpFiles($package . '/src'),
        );
        self::assertSame([
            'ContractVersion.php',
            'Diagnostic/GraphContractDiagnostic.php',
            'Exception/ExceptionInterface.php',
            'Exception/GraphContractException.php',
            'Exception/InvalidGraphContractException.php',
            'Identity/NodeIdentity.php',
            'Identity/ProducerIdentity.php',
            'Identity/RelationIdentity.php',
            'Identity/SourceIdentity.php',
            'Internal/IdentityGuard.php',
            'Provenance/DerivationVersion.php',
            'Provenance/GraphProvenance.php',
            'Provenance/ProducerContract.php',
            'Schema/FactVisibility.php',
            'Schema/FieldDisclosure.php',
            'Schema/FieldName.php',
            'Schema/FieldSchema.php',
            'Schema/FieldType.php',
            'Schema/NodeSchema.php',
            'Schema/RelationSchema.php',
            'Schema/SchemaCatalog.php',
            'Vocabulary/NodeType.php',
            'Vocabulary/RelationType.php',
        ], $sourceFiles);
        $testFiles = array_map(
            static fn(string $path): string => str_replace('\\', '/', substr($path, strlen($package . '/tests/'))),
            $this->recursivePhpFiles($package . '/tests'),
        );
        self::assertSame([
            'Unit/Diagnostic/GraphContractDiagnosticTest.php',
            'Unit/Exception/GraphContractExceptionTest.php',
            'Unit/Identity/IdentityTest.php',
            'Unit/Provenance/GraphProvenanceTest.php',
            'Unit/Schema/SchemaTest.php',
            'Unit/Vocabulary/VocabularyTest.php',
        ], $testFiles);
        $packageEntries = scandir($package);
        self::assertIsArray($packageEntries);
        self::assertSame(['README.md', 'composer.json', 'src', 'tests'], array_values(array_diff($packageEntries, ['.', '..'])));

        $rootManifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($rootManifest);
        $rootRequire = $rootManifest['require'];
        $rootAutoloadDev = $rootManifest['autoload-dev'];
        $rootScripts = $rootManifest['scripts'];
        self::assertIsArray($rootRequire);
        self::assertIsArray($rootAutoloadDev);
        self::assertIsArray($rootScripts);
        $rootAutoloadDevPsr4 = $rootAutoloadDev['psr-4'];
        $validatePackages = $rootScripts['validate:packages'];
        self::assertIsArray($rootAutoloadDevPsr4);
        self::assertIsArray($validatePackages);
        self::assertSame('@dev', $rootRequire['kongono/application-graph-contract']);
        self::assertSame(
            'packages/application-graph-contract/tests/',
            $rootAutoloadDevPsr4['Kongono\\ApplicationGraphContract\\Tests\\'],
        );
        $validation = 'composer --working-dir=packages/application-graph-contract validate --no-check-publish';
        self::assertSame(1, count(array_keys($validatePackages, $validation, true)));

        foreach (['.php-cs-fixer.dist.php', 'phpstan.neon.dist'] as $configuration) {
            $contents = file_get_contents($root . '/' . $configuration);
            self::assertIsString($contents);
            self::assertSame(1, substr_count($contents, 'packages/application-graph-contract/src'));
            self::assertSame(1, substr_count($contents, 'packages/application-graph-contract/tests'));
        }

        $packageManifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($packageManifests);
        self::assertCount(75, $packageManifests);
        $graph = [];
        $localEdges = 0;
        foreach ($packageManifests as $packageManifestPath) {
            $candidate = json_decode((string) file_get_contents($packageManifestPath), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($candidate);
            $name = $candidate['name'];
            $require = $candidate['require'];
            self::assertIsString($name);
            self::assertIsArray($require);
            $graph[$name] = [];
            foreach ($require as $dependency => $_constraint) {
                self::assertIsString($dependency);
                if (str_starts_with($dependency, 'kongono/')) {
                    $graph[$name][] = $dependency;
                    ++$localEdges;
                }
            }
            if (!in_array($name, ['kongono/application-graph-artifact', 'kongono/application-graph-build', 'kongono/application-graph-composition', 'kongono/application-graph-contract', 'kongono/application-graph-intelligence', 'kongono/extension-admission'], true)) {
                self::assertArrayNotHasKey('kongono/application-graph-contract', $require);
            }
        }
        self::assertSame(289, $localEdges);
        $visit = static function (string $packageName, array $path = []) use (&$visit, $graph): void {
            self::assertArrayNotHasKey($packageName, $path, sprintf('Package graph cycle reaches %s.', $packageName));
            $path[$packageName] = true;
            foreach ($graph[$packageName] as $dependency) {
                $visit($dependency, $path);
            }
        };
        foreach (array_keys($graph) as $packageName) {
            $visit($packageName);
        }

        $phaseZeroFiles = [
            ...$this->recursivePhpFiles($root . '/packages/application-graph/src'),
            ...$this->recursivePhpFiles($root . '/packages/application-graph/tests'),
            $root . '/packages/application-graph/README.md',
            $root . '/packages/application-graph/composer.json',
        ];
        $phaseZeroRecords = [];
        foreach ($phaseZeroFiles as $path) {
            $relative = str_replace('\\', '/', substr($path, strlen($root) + 1));
            $fileHash = hash_file('sha256', $path);
            self::assertIsString($fileHash);
            $phaseZeroRecords[] = $relative . '|' . filesize($path) . '|' . strtoupper($fileHash);
        }
        sort($phaseZeroRecords, SORT_STRING);
        self::assertSame(
            '2c53a41d97dfffc73f3524235ba04ccd447a0437ba6caf4253b6b6879e413042',
            hash('sha256', implode("\n", $phaseZeroRecords)),
        );

        $combinedSource = implode('', array_map(static fn(string $path): string => (string) file_get_contents($path), $this->recursivePhpFiles($package . '/src')));
        foreach (['file_get_contents(', 'file_put_contents(', 'fopen(', 'getenv(', 'putenv(', 'microtime(', 'random_', 'Reflection', 'serialize(', 'json_encode(', 'PDO', 'curl_', '$GLOBALS', '$_ENV', '$_SERVER', 'Registry', 'Cache'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $combinedSource);
        }
    }

    public function testApplicationGraphBuildPackageBoundaryAndIntegrationAreExact(): void
    {
        $root = dirname(__DIR__, 2);
        $package = $root . '/packages/application-graph-build';
        $manifest = json_decode((string) file_get_contents($package . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame([
            'name' => 'kongono/application-graph-build',
            'description' => 'Language-neutral IR admission and deterministic authoritative Graph construction for Kongono.',
            'type' => 'library',
            'require' => [
                'kongono/api-metadata' => '*',
                'kongono/application-graph-contract' => '*',
                'kongono/diagnostics' => '*',
                'php' => '>=8.5.0 <8.6.0',
            ],
            'autoload' => ['psr-4' => ['Kongono\\ApplicationGraphBuild\\' => 'src/']],
            'autoload-dev' => ['psr-4' => ['Kongono\\ApplicationGraphBuild\\Tests\\' => 'tests/']],
        ], $manifest);

        $sourceFiles = array_map(
            static fn(string $path): string => str_replace('\\', '/', substr($path, strlen($package . '/src/'))),
            $this->recursivePhpFiles($package . '/src'),
        );
        self::assertSame([
            'Build/ApplicationGraphBuilder.php',
            'Diagnostic/GraphBuildDiagnostic.php',
            'Exception/ExceptionInterface.php',
            'Exception/GraphBuildException.php',
            'Exception/InvalidGraphBuildException.php',
            'Graph/AuthoritativeApplicationGraph.php',
            'Graph/GraphField.php',
            'Graph/GraphNode.php',
            'Graph/GraphRelation.php',
            'Internal/GraphConstructionValidator.php',
            'Ir/GraphFieldRecord.php',
            'Ir/GraphNodeRecord.php',
            'Ir/GraphRelationRecord.php',
        ], $sourceFiles);
        $testFiles = array_map(
            static fn(string $path): string => str_replace('\\', '/', substr($path, strlen($package . '/tests/'))),
            $this->recursivePhpFiles($package . '/tests'),
        );
        self::assertSame([
            'Unit/Diagnostic/GraphBuildDiagnosticTest.php',
            'Unit/Exception/GraphBuildExceptionTest.php',
            'Unit/Graph/ApplicationGraphBuilderTest.php',
            'Unit/Graph/ApplicationGraphDeterminismTest.php',
            'Unit/Graph/ApplicationGraphValidationTest.php',
            'Unit/Ir/GraphFieldRecordTest.php',
            'Unit/Ir/GraphRecordTest.php',
        ], $testFiles);

        $rootManifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($rootManifest);
        $rootRequire = $rootManifest['require'] ?? null;
        self::assertIsArray($rootRequire);
        $rootAutoloadDev = $rootManifest['autoload-dev'] ?? null;
        self::assertIsArray($rootAutoloadDev);
        $rootAutoloadDevPsr4 = $rootAutoloadDev['psr-4'] ?? null;
        self::assertIsArray($rootAutoloadDevPsr4);
        $rootScripts = $rootManifest['scripts'] ?? null;
        self::assertIsArray($rootScripts);
        $rootPackageValidation = $rootScripts['validate:packages'] ?? null;
        self::assertIsArray($rootPackageValidation);
        self::assertSame('@dev', $rootRequire['kongono/application-graph-build']);
        self::assertSame('packages/application-graph-build/tests/', $rootAutoloadDevPsr4['Kongono\\ApplicationGraphBuild\\Tests\\']);
        $validation = 'composer --working-dir=packages/application-graph-build validate --no-check-publish';
        self::assertSame(1, count(array_keys($rootPackageValidation, $validation, true)));
        foreach (['.php-cs-fixer.dist.php', 'phpstan.neon.dist'] as $configuration) {
            $contents = file_get_contents($root . '/' . $configuration);
            self::assertIsString($contents);
            self::assertSame(1, substr_count($contents, 'packages/application-graph-build/src'));
            self::assertSame(1, substr_count($contents, 'packages/application-graph-build/tests'));
        }

        $surfaces = [
            'Kongono\\ApplicationGraphBuild\\Build\\ApplicationGraphBuilder' => ['build'],
            'Kongono\\ApplicationGraphBuild\\Diagnostic\\GraphBuildDiagnostic' => ['cases', 'context', 'contextSchema', 'from', 'remediation', 'title', 'tryFrom'],
            'Kongono\\ApplicationGraphBuild\\Exception\\ExceptionInterface' => ['diagnosticContext', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle'],
            'Kongono\\ApplicationGraphBuild\\Exception\\GraphBuildException' => ['__toString', 'diagnosticContext', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle'],
            'Kongono\\ApplicationGraphBuild\\Exception\\InvalidGraphBuildException' => ['duplicate', 'endpoint', 'fieldSet', 'input', 'limit'],
            'Kongono\\ApplicationGraphBuild\\Graph\\AuthoritativeApplicationGraph' => ['__construct', 'nodes', 'relations', 'version'],
            'Kongono\\ApplicationGraphBuild\\Graph\\GraphField' => ['__construct', 'disclosure', 'isRequired', 'name', 'type', 'value', 'visibility'],
            'Kongono\\ApplicationGraphBuild\\Graph\\GraphNode' => ['__construct', 'fields', 'identity', 'provenance', 'type'],
            'Kongono\\ApplicationGraphBuild\\Graph\\GraphRelation' => ['__construct', 'fields', 'identity', 'provenance', 'source', 'target', 'type'],
            'Kongono\\ApplicationGraphBuild\\Internal\\GraphConstructionValidator' => ['construct'],
            'Kongono\\ApplicationGraphBuild\\Ir\\GraphFieldRecord' => ['__construct', 'name', 'value'],
            'Kongono\\ApplicationGraphBuild\\Ir\\GraphNodeRecord' => ['__construct', 'fields', 'identity', 'provenance', 'type', 'version'],
            'Kongono\\ApplicationGraphBuild\\Ir\\GraphRelationRecord' => ['__construct', 'fields', 'identity', 'provenance', 'source', 'target', 'type', 'version'],
        ];
        foreach ($surfaces as $type => $methods) {
            self::assertSame($methods, $this->declaredPublicMethodNames(new ReflectionClass($type)));
        }
        self::assertSame(4096, constant('Kongono\\ApplicationGraphBuild\\Ir\\GraphFieldRecord::MAX_VALUE_BYTES'));
        self::assertSame(65536, constant('Kongono\\ApplicationGraphBuild\\Build\\ApplicationGraphBuilder::MAX_NODES'));
        self::assertSame(262144, constant('Kongono\\ApplicationGraphBuild\\Build\\ApplicationGraphBuilder::MAX_RELATIONS'));

        $packageManifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($packageManifests);
        self::assertCount(75, $packageManifests);
        $localEdges = 0;
        foreach ($packageManifests as $packageManifestPath) {
            $candidate = json_decode((string) file_get_contents($packageManifestPath), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($candidate);
            self::assertIsString($candidate['name']);
            self::assertIsArray($candidate['require']);
            foreach ($candidate['require'] as $dependency => $_constraint) {
                if (str_starts_with($dependency, 'kongono/')) {
                    ++$localEdges;
                }
            }
            if (!in_array($candidate['name'], ['kongono/application-graph-artifact', 'kongono/application-graph-build', 'kongono/application-graph-composition', 'kongono/extension-admission'], true)) {
                self::assertArrayNotHasKey('kongono/application-graph-build', $candidate['require']);
            }
        }
        self::assertSame(289, $localEdges);

        $combinedSource = implode('', array_map(static fn(string $path): string => (string) file_get_contents($path), $this->recursivePhpFiles($package . '/src')));
        foreach (['file_get_contents(', 'file_put_contents(', 'fopen(', 'glob(', 'scandir(', 'getenv(', 'putenv(', 'Reflection', 'serialize(', 'json_encode(', 'PDO', 'curl_', '$GLOBALS', '$_ENV', '$_SERVER', 'Composition', 'ApplicationGraph\\Model', 'Chie', 'Hantei', 'Oshie'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $combinedSource);
        }
    }

    public function testExtensionContractPackageBoundaryAndIntegrationAreExact(): void
    {
        $root = dirname(__DIR__, 2);
        $package = $root . '/packages/extension-contract';
        $manifest = json_decode((string) file_get_contents($package . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame([
            'name' => 'kongono/extension-contract',
            'description' => 'Neutral versioned contracts for Kongono extension declarations.',
            'type' => 'library',
            'require' => [
                'kongono/api-metadata' => '*',
                'kongono/diagnostics' => '*',
                'php' => '>=8.5.0 <8.6.0',
            ],
            'require-dev' => ['phpunit/phpunit' => '^13.0'],
            'autoload' => ['psr-4' => ['Kongono\\ExtensionContract\\' => 'src/']],
            'autoload-dev' => ['psr-4' => ['Kongono\\ExtensionContract\\Tests\\' => 'tests/']],
        ], $manifest);

        $sourceFiles = array_map(
            static fn(string $path): string => str_replace('\\', '/', substr($path, strlen($package . '/src/'))),
            $this->recursivePhpFiles($package . '/src'),
        );
        self::assertSame([
            'Capability/CapabilityIdentity.php',
            'Capability/CapabilityPurpose.php',
            'Capability/CapabilityRequest.php',
            'Capability/CapabilityRequirement.php',
            'ContractVersion.php',
            'Contribution/ContributionDeclaration.php',
            'Contribution/ContributionIdentity.php',
            'Contribution/ContributionSchema.php',
            'Contribution/ContributionVisibility.php',
            'Declaration/ExtensionDeclaration.php',
            'Diagnostic/ExtensionContractDiagnostic.php',
            'Exception/ExceptionInterface.php',
            'Exception/ExtensionContractException.php',
            'Exception/InvalidExtensionContractException.php',
            'ExtensionCategory.php',
            'Identity/ExtensionIdentity.php',
            'Internal/ContractGuard.php',
            'Lifecycle/ExtensionLifecycle.php',
            'Provenance/ExtensionProvenance.php',
            'Provenance/ExtensionSourceIdentity.php',
            'Version/ExtensionVersion.php',
        ], $sourceFiles);
        $testFiles = array_map(
            static fn(string $path): string => str_replace('\\', '/', substr($path, strlen($package . '/tests/'))),
            $this->recursivePhpFiles($package . '/tests'),
        );
        self::assertSame([
            'Architecture/ExtensionContractBoundaryTest.php',
            'Unit/Capability/CapabilityRequestTest.php',
            'Unit/ContractVersionTest.php',
            'Unit/Contribution/ContributionDeclarationTest.php',
            'Unit/Declaration/ExtensionDeclarationTest.php',
            'Unit/Diagnostic/ExtensionContractDiagnosticTest.php',
            'Unit/Exception/ExtensionContractExceptionTest.php',
            'Unit/ExtensionCategoryTest.php',
            'Unit/Identity/ExtensionIdentityTest.php',
            'Unit/Lifecycle/ExtensionLifecycleTest.php',
            'Unit/Provenance/ExtensionProvenanceTest.php',
            'Unit/Version/ExtensionVersionTest.php',
        ], $testFiles);

        $rootManifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($rootManifest);
        $rootRequire = $rootManifest['require'];
        $rootAutoloadDev = $rootManifest['autoload-dev'];
        $rootScripts = $rootManifest['scripts'];
        self::assertIsArray($rootRequire);
        self::assertIsArray($rootAutoloadDev);
        self::assertIsArray($rootScripts);
        $rootAutoloadDevPsr4 = $rootAutoloadDev['psr-4'];
        $validatePackages = $rootScripts['validate:packages'];
        self::assertIsArray($rootAutoloadDevPsr4);
        self::assertIsArray($validatePackages);
        self::assertSame('@dev', $rootRequire['kongono/extension-contract']);
        self::assertSame('packages/extension-contract/tests/', $rootAutoloadDevPsr4['Kongono\\ExtensionContract\\Tests\\']);
        $validation = 'composer --working-dir=packages/extension-contract validate --no-check-publish';
        self::assertSame(1, count(array_keys($validatePackages, $validation, true)));

        foreach (['.php-cs-fixer.dist.php', 'phpstan.neon.dist', 'phpunit.xml.dist'] as $configuration) {
            $contents = file_get_contents($root . '/' . $configuration);
            self::assertIsString($contents);
            self::assertSame(1, substr_count($contents, 'packages/extension-contract/src'));
            self::assertSame(1, substr_count($contents, 'packages/extension-contract/tests'));
        }

        $packageManifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($packageManifests);
        self::assertCount(75, $packageManifests);
        $localEdges = 0;
        foreach ($packageManifests as $packageManifestPath) {
            $candidate = json_decode((string) file_get_contents($packageManifestPath), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($candidate);
            self::assertIsArray($candidate['require']);
            foreach ($candidate['require'] as $dependency => $_constraint) {
                if (str_starts_with($dependency, 'kongono/')) {
                    ++$localEdges;
                }
            }
            if (!in_array($candidate['name'], ['kongono/extension-admission', 'kongono/extension-build', 'kongono/extension-composition', 'kongono/extension-contract', 'kongono/extension-inspection'], true)) {
                self::assertArrayNotHasKey('kongono/extension-contract', $candidate['require']);
            }
        }
        self::assertSame(289, $localEdges);

        $combinedSource = implode('', array_map(static fn(string $path): string => (string) file_get_contents($path), $this->recursivePhpFiles($package . '/src')));
        foreach (['file_get_contents(', 'file_put_contents(', 'fopen(', 'glob(', 'scandir(', 'getenv(', 'putenv(', 'Reflection', 'serialize(', 'json_encode(', 'PDO', 'curl_', '$GLOBALS', '$_ENV', '$_SERVER', 'ApplicationGraph', 'Composition', 'Registry', 'Loader', 'Host', 'callable', 'Closure'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $combinedSource);
        }
        self::assertFalse((new ReflectionClass('Kongono\\ExtensionContract\\Exception\\ExceptionInterface'))->implementsInterface('Kongono\\Diagnostics\\Exception\\ExceptionInterface'));
    }

    public function testAgentPrincipalContractPackageBoundaryAndIntegrationAreExact(): void
    {
        $root = dirname(__DIR__, 2);
        $package = $root . '/packages/agent-principal-contract';
        $manifest = json_decode((string) file_get_contents($package . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame([
            'name' => 'kongono/agent-principal-contract',
            'description' => 'Neutral versioned Agent Principal contracts for Kongono Framework.',
            'type' => 'library',
            'require' => [
                'kongono/api-metadata' => '*',
                'kongono/diagnostics' => '*',
                'kongono/identity-access' => '*',
                'php' => '>=8.5.0 <8.6.0',
            ],
            'require-dev' => ['phpunit/phpunit' => '^13.0'],
            'autoload' => ['psr-4' => ['Kongono\\AgentPrincipalContract\\' => 'src/']],
            'autoload-dev' => ['psr-4' => ['Kongono\\AgentPrincipalContract\\Tests\\' => 'tests/']],
        ], $manifest);

        $sourceFiles = array_map(
            static fn(string $path): string => str_replace('\\', '/', substr($path, strlen($package . '/src/'))),
            $this->recursivePhpFiles($package . '/src'),
        );
        self::assertSame([
            'Capability/CapabilityIdentity.php',
            'Capability/CapabilityPurpose.php',
            'Capability/CapabilityRequest.php',
            'Capability/CapabilityRequirement.php',
            'Constraint/AgentConstraint.php',
            'Constraint/ConstraintKind.php',
            'ContractVersion.php',
            'Diagnostic/AgentPrincipalContractDiagnostic.php',
            'Evidence/EvidenceIdentity.php',
            'Evidence/EvidenceKind.php',
            'Evidence/EvidenceReference.php',
            'Exception/AgentPrincipalContractException.php',
            'Exception/ExceptionInterface.php',
            'Exception/InvalidAgentPrincipalContractException.php',
            'Identity/AgentPrincipalReference.php',
            'Identity/OperationReference.php',
            'Intent/AgentIntent.php',
            'Intent/IntentIdentity.php',
            'Intent/IntentPurpose.php',
            'Internal/ContractGuard.php',
            'Proposal/ActionProposal.php',
            'Proposal/ProposalIdentity.php',
            'Provenance/AgentProvenance.php',
            'Provenance/ProvenanceSource.php',
            'Receipt/AgentReceipt.php',
            'Receipt/ReceiptIdentity.php',
            'Receipt/ReceiptOutcome.php',
            'Visibility/AgentVisibility.php',
        ], $sourceFiles);

        $testFiles = array_map(
            static fn(string $path): string => str_replace('\\', '/', substr($path, strlen($package . '/tests/'))),
            $this->recursivePhpFiles($package . '/tests'),
        );
        self::assertSame([
            'Architecture/AgentPrincipalContractBoundaryTest.php',
            'Unit/Capability/CapabilityRequestTest.php',
            'Unit/Constraint/AgentConstraintTest.php',
            'Unit/ContractVersionTest.php',
            'Unit/Diagnostic/AgentPrincipalContractDiagnosticTest.php',
            'Unit/Evidence/EvidenceReferenceTest.php',
            'Unit/Exception/AgentPrincipalContractExceptionTest.php',
            'Unit/Identity/AgentPrincipalReferenceTest.php',
            'Unit/Intent/AgentIntentTest.php',
            'Unit/Proposal/ActionProposalTest.php',
            'Unit/Provenance/AgentProvenanceTest.php',
            'Unit/Receipt/AgentReceiptTest.php',
            'Unit/Visibility/AgentVisibilityTest.php',
        ], $testFiles);

        $rootManifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($rootManifest);
        $rootRequire = $rootManifest['require'] ?? null;
        self::assertIsArray($rootRequire);
        self::assertSame('@dev', $rootRequire['kongono/agent-principal-contract']);
        $rootAutoloadDev = $rootManifest['autoload-dev'] ?? null;
        self::assertIsArray($rootAutoloadDev);
        $rootAutoloadDevPsr4 = $rootAutoloadDev['psr-4'] ?? null;
        self::assertIsArray($rootAutoloadDevPsr4);
        self::assertSame('packages/agent-principal-contract/tests/', $rootAutoloadDevPsr4['Kongono\\AgentPrincipalContract\\Tests\\']);
        $validation = 'composer --working-dir=packages/agent-principal-contract validate --no-check-publish';
        $rootScripts = $rootManifest['scripts'] ?? null;
        self::assertIsArray($rootScripts);
        $packageValidation = $rootScripts['validate:packages'] ?? null;
        self::assertIsArray($packageValidation);
        self::assertSame(1, count(array_keys($packageValidation, $validation, true)));
        $phpstan = (string) file_get_contents($root . '/phpstan.neon.dist');
        self::assertSame(1, substr_count($phpstan, 'packages/agent-principal-contract/src'));
        self::assertSame(1, substr_count($phpstan, 'packages/agent-principal-contract/tests'));

        $packageManifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($packageManifests);
        self::assertCount(75, $packageManifests);
        $localEdges = 0;
        foreach ($packageManifests as $packageManifestPath) {
            $candidate = json_decode((string) file_get_contents($packageManifestPath), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($candidate);
            self::assertIsArray($candidate['require']);
            foreach (array_keys($candidate['require']) as $dependency) {
                if (str_starts_with($dependency, 'kongono/')) {
                    ++$localEdges;
                }
            }
            if (!in_array($candidate['name'], ['kongono/agent-admission', 'kongono/agent-application', 'kongono/agent-declaration-build', 'kongono/agent-inspection', 'kongono/agent-principal-contract', 'kongono/agent-runtime', 'kongono/model-invocation-agent-bridge'], true)) {
                self::assertArrayNotHasKey('kongono/agent-principal-contract', $candidate['require']);
            }
        }
        self::assertSame(289, $localEdges);

        $combinedSource = implode('', array_map(
            fn(string $path): string => $this->sourceWithoutComments((string) file_get_contents($path)),
            $this->recursivePhpFiles($package . '/src'),
        ));
        foreach (['file_get_contents(', 'file_put_contents(', 'fopen(', 'glob(', 'scandir(', 'getenv(', 'putenv(', 'Reflection', 'serialize(', 'json_encode(', 'PDO', 'curl_', '$GLOBALS', '$_ENV', '$_SERVER', 'ApplicationGraph', 'Composition', 'ExtensionHost', 'Registry', 'Loader', 'callable', 'Closure', 'Fiber'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $combinedSource);
        }
    }

    public function testAgentDeclarationBuildPackageBoundaryAndIntegrationAreExact(): void
    {
        $root = dirname(__DIR__, 2);
        $package = $root . '/packages/agent-declaration-build';
        $manifest = json_decode((string) file_get_contents($package . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame([
            'name' => 'kongono/agent-declaration-build',
            'description' => 'Deterministic declaration artifact build and publication for Kongono Agent Principals.',
            'type' => 'library',
            'require' => [
                'php' => '>=8.5.0 <8.6.0',
                'kongono/agent-principal-contract' => '*',
                'kongono/api-metadata' => '*',
                'kongono/diagnostics' => '*',
            ],
            'require-dev' => ['phpunit/phpunit' => '^13.0'],
            'autoload' => ['psr-4' => ['Kongono\\AgentDeclarationBuild\\' => 'src/']],
            'autoload-dev' => ['psr-4' => ['Kongono\\AgentDeclarationBuild\\Tests\\' => 'tests/']],
        ], $manifest);

        $sourceFiles = array_map(
            static fn(string $path): string => str_replace('\\', '/', substr($path, strlen($package . '/src/'))),
            $this->recursivePhpFiles($package . '/src'),
        );
        self::assertSame([
            'Artifact/AgentDeclarationArtifact.php',
            'Artifact/AgentDeclarationArtifactBuilder.php',
            'Artifact/AgentDeclarationArtifactPublisher.php',
            'Artifact/AgentDeclarationArtifactValidator.php',
            'Declaration/AgentDeclaration.php',
            'Declaration/AgentDeclarationIdentity.php',
            'Diagnostic/AgentDeclarationBuildDiagnostic.php',
            'Exception/AgentDeclarationBuildException.php',
            'Exception/AgentDeclarationPublicationException.php',
            'Exception/ExceptionInterface.php',
            'Exception/InvalidAgentDeclarationArtifactException.php',
            'Exception/InvalidAgentDeclarationException.php',
            'Exception/StaleAgentDeclarationArtifactException.php',
            'Internal/CanonicalJson.php',
            'Internal/PathGuard.php',
        ], $sourceFiles);
        self::assertCount(9, $this->recursivePhpFiles($package . '/tests'));

        $rootManifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($rootManifest);
        $rootRequire = $rootManifest['require'];
        $rootAutoloadDev = $rootManifest['autoload-dev'];
        $rootScripts = $rootManifest['scripts'];
        self::assertIsArray($rootRequire);
        self::assertIsArray($rootAutoloadDev);
        self::assertIsArray($rootScripts);
        $rootAutoloadDevPsr4 = $rootAutoloadDev['psr-4'];
        $validatePackages = $rootScripts['validate:packages'];
        self::assertIsArray($rootAutoloadDevPsr4);
        self::assertIsArray($validatePackages);
        self::assertSame('@dev', $rootRequire['kongono/agent-declaration-build']);
        self::assertSame(
            'packages/agent-declaration-build/tests/',
            $rootAutoloadDevPsr4['Kongono\\AgentDeclarationBuild\\Tests\\'],
        );
        $validation = 'composer --working-dir=packages/agent-declaration-build validate --no-check-publish';
        self::assertSame(1, count(array_keys($validatePackages, $validation, true)));
        foreach (['.php-cs-fixer.dist.php', 'phpstan.neon.dist', 'phpunit.xml.dist'] as $configuration) {
            $contents = (string) file_get_contents($root . '/' . $configuration);
            self::assertSame(1, substr_count($contents, 'packages/agent-declaration-build/src'));
            self::assertSame(1, substr_count($contents, 'packages/agent-declaration-build/tests'));
        }

        $packageManifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($packageManifests);
        self::assertCount(75, $packageManifests);
        $edges = 0;
        foreach ($packageManifests as $manifestPath) {
            $candidate = json_decode((string) file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($candidate);
            self::assertIsString($candidate['name']);
            self::assertIsArray($candidate['require']);
            foreach (array_keys($candidate['require']) as $dependency) {
                if (str_starts_with($dependency, 'kongono/')) {
                    ++$edges;
                }
            }
            if (!in_array($candidate['name'], ['kongono/agent-admission', 'kongono/agent-application', 'kongono/agent-declaration-build', 'kongono/agent-inspection', 'kongono/agent-runtime'], true)) {
                self::assertArrayNotHasKey('kongono/agent-declaration-build', $candidate['require']);
            }
        }
        self::assertSame(289, $edges);
        $sourceDirectories = glob($root . '/packages/*/src');
        $testDirectories = glob($root . '/packages/*/tests');
        self::assertIsArray($sourceDirectories);
        self::assertIsArray($testDirectories);
        self::assertCount(1358, array_merge(...array_map($this->recursivePhpFiles(...), $sourceDirectories)));
        self::assertCount(686, array_merge(...array_map($this->recursivePhpFiles(...), $testDirectories)));

        self::assertFalse(is_a(
            'Kongono\\AgentDeclarationBuild\\Exception\\ExceptionInterface',
            'Kongono\\AgentPrincipalContract\\Exception\\ExceptionInterface',
            true,
        ));
        self::assertSame(['build'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\AgentDeclarationBuild\\Artifact\\AgentDeclarationArtifactBuilder')));
        self::assertSame(['validateCurrent', 'validateStructure'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\AgentDeclarationBuild\\Artifact\\AgentDeclarationArtifactValidator')));
        self::assertSame(['publish'], $this->declaredPublicMethodNames(new ReflectionClass('Kongono\\AgentDeclarationBuild\\Artifact\\AgentDeclarationArtifactPublisher')));

        $combinedSource = implode('', array_map(
            fn(string $path): string => $this->sourceWithoutComments((string) file_get_contents($path)),
            $this->recursivePhpFiles($package . '/src'),
        ));
        foreach (['ApplicationGraph', 'ExtensionHost', 'Composition', 'Authorization', 'ModelProvider', 'Command', 'Registry', 'Loader', 'scandir(', 'glob(', 'getenv(', 'putenv(', 'curl_', 'PDO'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $combinedSource);
        }
    }

    public function testAgentAdmissionPackageBoundaryAndIntegrationAreExact(): void
    {
        $root = dirname(__DIR__, 2);
        $package = $root . '/packages/agent-admission';
        $manifest = json_decode((string) file_get_contents($package . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame([
            'name' => 'kongono/agent-admission',
            'description' => 'Deterministic application-owned admission and inert authority-plan evidence for Kongono Agent Principals.',
            'type' => 'library',
            'require' => [
                'php' => '>=8.5.0 <8.6.0',
                'kongono/agent-declaration-build' => '*',
                'kongono/agent-principal-contract' => '*',
                'kongono/api-metadata' => '*',
                'kongono/authentication' => '*',
                'kongono/authorization' => '*',
                'kongono/diagnostics' => '*',
                'kongono/identity-access' => '*',
            ],
            'require-dev' => ['phpunit/phpunit' => '^13.0'],
            'autoload' => ['psr-4' => ['Kongono\\AgentAdmission\\' => 'src/']],
            'autoload-dev' => ['psr-4' => ['Kongono\\AgentAdmission\\Tests\\' => 'tests/']],
        ], $manifest);

        $sourceFiles = array_map(
            static fn(string $path): string => str_replace('\\', '/', substr($path, strlen($package . '/src/'))),
            $this->recursivePhpFiles($package . '/src'),
        );
        self::assertSame([
            'Admission/AgentAdmissionBuilder.php',
            'Admission/AgentAdmissionDecision.php',
            'Admission/AgentAdmissionRequest.php',
            'Authority/AgentAuthorityPlan.php',
            'Authority/CapabilityAuthorityDecision.php',
            'Authority/CapabilityAuthorityStatus.php',
            'Diagnostic/AgentAdmissionDiagnostic.php',
            'Evidence/CapabilityAuthorizationEvidence.php',
            'Exception/AgentAdmissionException.php',
            'Exception/ExceptionInterface.php',
            'Exception/InvalidAgentAdmissionException.php',
            'Identity/AgentAdmissionIdentity.php',
            'Internal/AdmissionGuard.php',
            'Internal/CanonicalAdmission.php',
        ], $sourceFiles);
        self::assertCount(9, $this->recursivePhpFiles($package . '/tests'));

        $rootManifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($rootManifest);
        self::assertIsArray($rootManifest['require']);
        self::assertIsArray($rootManifest['autoload-dev']);
        self::assertIsArray($rootManifest['autoload-dev']['psr-4']);
        self::assertIsArray($rootManifest['scripts']);
        self::assertIsArray($rootManifest['scripts']['validate:packages']);
        self::assertSame('@dev', $rootManifest['require']['kongono/agent-admission']);
        self::assertSame('packages/agent-admission/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\AgentAdmission\\Tests\\']);
        $validation = 'composer --working-dir=packages/agent-admission validate --no-check-publish';
        self::assertSame(1, count(array_keys($rootManifest['scripts']['validate:packages'], $validation, true)));
        foreach (['.php-cs-fixer.dist.php', 'phpstan.neon.dist', 'phpunit.xml.dist'] as $configuration) {
            $contents = (string) file_get_contents($root . '/' . $configuration);
            self::assertSame(1, substr_count($contents, 'packages/agent-admission/src'));
            self::assertSame(1, substr_count($contents, 'packages/agent-admission/tests'));
        }

        $packageManifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($packageManifests);
        self::assertCount(75, $packageManifests);
        $edges = 0;
        foreach ($packageManifests as $manifestPath) {
            $candidate = json_decode((string) file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($candidate);
            self::assertIsString($candidate['name']);
            self::assertIsArray($candidate['require']);
            foreach (array_keys($candidate['require']) as $dependency) {
                if (str_starts_with($dependency, 'kongono/')) {
                    ++$edges;
                }
            }
            if (!in_array($candidate['name'], ['kongono/agent-admission', 'kongono/agent-application', 'kongono/agent-inspection', 'kongono/agent-runtime'], true)) {
                self::assertArrayNotHasKey('kongono/agent-admission', $candidate['require']);
            }
        }
        self::assertSame(289, $edges);

        $combinedSource = implode('', array_map(
            fn(string $path): string => $this->sourceWithoutComments((string) file_get_contents($path)),
            $this->recursivePhpFiles($package . '/src'),
        ));
        foreach (['AuthenticationOrchestrator', 'AuthenticationVerifier', 'AuthorizationEngine', 'AuthorizationEvaluator', 'ApplicationGraph', 'ExtensionHost', 'Composition', 'Registry', 'Loader', 'Reflection', 'scandir(', 'glob(', 'getenv(', 'putenv(', 'curl_', 'PDO', 'random_', 'microtime(', 'time('] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $combinedSource);
        }
    }

    public function testExtensionAdmissionPackageBoundaryInventoryAndIntegrationAreExact(): void
    {
        $root = dirname(__DIR__, 2);
        $package = $root . '/packages/extension-admission';
        $manifest = json_decode((string) file_get_contents($package . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertIsArray($manifest['require']);
        self::assertSame('kongono/extension-admission', $manifest['name']);
        self::assertSame([
            'kongono/api-metadata' => '*',
            'kongono/application-graph-build' => '*',
            'kongono/application-graph-contract' => '*',
            'kongono/diagnostics' => '*',
            'kongono/extension-build' => '*',
            'kongono/extension-contract' => '*',
            'php' => '>=8.5.0 <8.6.0',
        ], $manifest['require']);

        $sourceFiles = array_map(static fn(string $path): string => str_replace('\\', '/', substr($path, strlen($package . '/src') + 1)), $this->recursivePhpFiles($package . '/src'));
        self::assertSame([
            'Admission/AdmittedExtension.php',
            'Admission/AdmittedExtensionGraphInput.php',
            'Admission/ExtensionAdmissionBuilder.php',
            'Conflict/ContributionConflictResolution.php',
            'Contribution/ExtensionGraphContribution.php',
            'Diagnostic/ExtensionAdmissionDiagnostic.php',
            'Exception/ExceptionInterface.php',
            'Exception/ExtensionAdmissionException.php',
            'Exception/InvalidExtensionAdmissionException.php',
            'Exception/UnresolvedExtensionConflictException.php',
            'Identity/AdmissionIdentity.php',
            'Internal/AdmissionGuard.php',
            'Internal/AdmissionProjector.php',
            'Internal/CanonicalAdmission.php',
            'Internal/GraphContributionValidator.php',
            'Projection/ExtensionGraphProvenance.php',
            'Selection/ExtensionAdmissionPlan.php',
            'Selection/ExtensionSelection.php',
        ], $sourceFiles);
        self::assertCount(10, $this->recursivePhpFiles($package . '/tests'));
        self::assertSame(['kongono.composition/2', 'kongono.extension/1'], array_column(('Kongono\\ApplicationGraphContract\\Provenance\\ProducerContract')::cases(), 'value'));

        $rootManifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($rootManifest);
        self::assertIsArray($rootManifest['require']);
        self::assertIsArray($rootManifest['autoload-dev']);
        self::assertIsArray($rootManifest['autoload-dev']['psr-4']);
        self::assertIsArray($rootManifest['scripts']);
        self::assertIsArray($rootManifest['scripts']['validate:packages']);
        self::assertSame('@dev', $rootManifest['require']['kongono/extension-admission']);
        self::assertSame('packages/extension-admission/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\ExtensionAdmission\\Tests\\']);
        self::assertContains('composer --working-dir=packages/extension-admission validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        foreach (['.php-cs-fixer.dist.php', 'phpstan.neon.dist', 'phpunit.xml.dist'] as $configuration) {
            $contents = (string) file_get_contents($root . '/' . $configuration);
            self::assertSame(1, substr_count($contents, 'packages/extension-admission/src'));
            self::assertSame(1, substr_count($contents, 'packages/extension-admission/tests'));
        }
    }

    public function testExtensionBuildPackageBoundaryArtifactContractAndIntegrationAreExact(): void
    {
        $root = dirname(__DIR__, 2);
        $package = $root . '/packages/extension-build';
        $manifest = json_decode((string) file_get_contents($package . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame([
            'name' => 'kongono/extension-build',
            'description' => 'Deterministic declaration artifact build and publication for Kongono extensions.',
            'type' => 'library',
            'require' => [
                'kongono/api-metadata' => '*',
                'kongono/diagnostics' => '*',
                'kongono/extension-contract' => '*',
                'php' => '>=8.5.0 <8.6.0',
            ],
            'require-dev' => ['phpunit/phpunit' => '^13.0'],
            'autoload' => ['psr-4' => ['Kongono\\ExtensionBuild\\' => 'src/']],
            'autoload-dev' => ['psr-4' => ['Kongono\\ExtensionBuild\\Tests\\' => 'tests/']],
        ], $manifest);

        $sourceFiles = array_map(
            static fn(string $path): string => str_replace('\\', '/', substr($path, strlen($package . '/src/'))),
            $this->recursivePhpFiles($package . '/src'),
        );
        self::assertSame([
            'ArtifactVersion.php',
            'Artifact/ExtensionDeclarationArtifact.php',
            'Artifact/ExtensionDeclarationArtifactBuilder.php',
            'Artifact/ExtensionDeclarationArtifactPublisher.php',
            'Artifact/ExtensionDeclarationArtifactValidator.php',
            'Diagnostic/ExtensionBuildDiagnostic.php',
            'Exception/ExceptionInterface.php',
            'Exception/ExtensionBuildException.php',
            'Exception/ExtensionDeclarationPublicationException.php',
            'Exception/InvalidExtensionBuildException.php',
            'Exception/StaleExtensionDeclarationArtifactException.php',
            'Identity/DeclarationSetIdentity.php',
            'Internal/CanonicalJson.php',
            'Internal/DeclarationArtifactCodec.php',
            'Internal/PathGuard.php',
        ], $sourceFiles);
        $testFiles = array_map(
            static fn(string $path): string => str_replace('\\', '/', substr($path, strlen($package . '/tests/'))),
            $this->recursivePhpFiles($package . '/tests'),
        );
        self::assertSame([
            'Architecture/ExtensionBuildBoundaryTest.php',
            'Unit/ArtifactVersionTest.php',
            'Unit/Artifact/ExtensionDeclarationArtifactBuilderTest.php',
            'Unit/Artifact/ExtensionDeclarationArtifactCurrentnessTest.php',
            'Unit/Artifact/ExtensionDeclarationArtifactPublicationTest.php',
            'Unit/Artifact/ExtensionDeclarationArtifactValidationTest.php',
            'Unit/Diagnostic/ExtensionBuildDiagnosticTest.php',
            'Unit/Exception/ExtensionBuildExceptionTest.php',
            'Unit/Identity/DeclarationSetIdentityTest.php',
        ], $testFiles);

        $statuses = [
            ApiStatus::Public->value => ['Kongono\\ExtensionBuild\\Exception\\ExceptionInterface'],
            ApiStatus::Experimental->value => [
                'Kongono\\ExtensionBuild\\ArtifactVersion',
                'Kongono\\ExtensionBuild\\Artifact\\ExtensionDeclarationArtifact',
                'Kongono\\ExtensionBuild\\Artifact\\ExtensionDeclarationArtifactBuilder',
                'Kongono\\ExtensionBuild\\Artifact\\ExtensionDeclarationArtifactPublisher',
                'Kongono\\ExtensionBuild\\Artifact\\ExtensionDeclarationArtifactValidator',
                'Kongono\\ExtensionBuild\\Exception\\ExtensionDeclarationPublicationException',
                'Kongono\\ExtensionBuild\\Exception\\InvalidExtensionBuildException',
                'Kongono\\ExtensionBuild\\Exception\\StaleExtensionDeclarationArtifactException',
                'Kongono\\ExtensionBuild\\Identity\\DeclarationSetIdentity',
            ],
            ApiStatus::Internal->value => [
                'Kongono\\ExtensionBuild\\Diagnostic\\ExtensionBuildDiagnostic',
                'Kongono\\ExtensionBuild\\Exception\\ExtensionBuildException',
                'Kongono\\ExtensionBuild\\Internal\\CanonicalJson',
                'Kongono\\ExtensionBuild\\Internal\\DeclarationArtifactCodec',
                'Kongono\\ExtensionBuild\\Internal\\PathGuard',
            ],
        ];
        foreach ($statuses as $expected => $classes) {
            foreach ($classes as $class) {
                $attributes = (new ReflectionClass($class))->getAttributes(Api::class);
                self::assertCount(1, $attributes);
                self::assertSame($expected, $attributes[0]->newInstance()->status->value);
            }
        }

        $builder = new ReflectionClass('Kongono\\ExtensionBuild\\Artifact\\ExtensionDeclarationArtifactBuilder');
        self::assertSame(1, $builder->getConstant('CONTRACT_VERSION'));
        self::assertSame(256, $builder->getConstant('MAX_DECLARATIONS'));
        self::assertSame(4194304, $builder->getConstant('MAX_ARTIFACT_BYTES'));
        self::assertSame(['build'], array_map(static fn(ReflectionMethod $method): string => $method->name, $builder->getMethods(ReflectionMethod::IS_PUBLIC)));
        $validator = new ReflectionClass('Kongono\\ExtensionBuild\\Artifact\\ExtensionDeclarationArtifactValidator');
        self::assertSame(['validateStructure', 'validateCurrent'], array_map(static fn(ReflectionMethod $method): string => $method->name, $validator->getMethods(ReflectionMethod::IS_PUBLIC)));
        $publisher = new ReflectionClass('Kongono\\ExtensionBuild\\Artifact\\ExtensionDeclarationArtifactPublisher');
        self::assertSame(['publish'], array_map(static fn(ReflectionMethod $method): string => $method->name, $publisher->getMethods(ReflectionMethod::IS_PUBLIC)));
        self::assertFalse((new ReflectionClass('Kongono\\ExtensionBuild\\Exception\\ExceptionInterface'))->implementsInterface('Kongono\\Diagnostics\\Exception\\ExceptionInterface'));

        $rootManifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($rootManifest);
        $rootRequire = $rootManifest['require'] ?? null;
        self::assertIsArray($rootRequire);
        self::assertSame('@dev', $rootRequire['kongono/extension-build']);
        $rootAutoloadDev = $rootManifest['autoload-dev'] ?? null;
        self::assertIsArray($rootAutoloadDev);
        $rootAutoloadDevPsr4 = $rootAutoloadDev['psr-4'] ?? null;
        self::assertIsArray($rootAutoloadDevPsr4);
        self::assertSame('packages/extension-build/tests/', $rootAutoloadDevPsr4['Kongono\\ExtensionBuild\\Tests\\']);
        $validation = 'composer --working-dir=packages/extension-build validate --no-check-publish';
        $rootScripts = $rootManifest['scripts'] ?? null;
        self::assertIsArray($rootScripts);
        $packageValidation = $rootScripts['validate:packages'] ?? null;
        self::assertIsArray($packageValidation);
        self::assertSame(1, count(array_keys($packageValidation, $validation, true)));
        foreach (['.php-cs-fixer.dist.php', 'phpstan.neon.dist', 'phpunit.xml.dist'] as $configuration) {
            $contents = (string) file_get_contents($root . '/' . $configuration);
            self::assertSame(1, substr_count($contents, 'packages/extension-build/src'));
            self::assertSame(1, substr_count($contents, 'packages/extension-build/tests'));
        }

        $packageManifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($packageManifests);
        self::assertCount(75, $packageManifests);
        $localEdges = 0;
        foreach ($packageManifests as $packageManifestPath) {
            $candidate = json_decode((string) file_get_contents($packageManifestPath), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($candidate);
            self::assertIsArray($candidate['require']);
            foreach ($candidate['require'] as $dependency => $_constraint) {
                if (str_starts_with($dependency, 'kongono/')) {
                    ++$localEdges;
                }
            }
            if (!in_array($candidate['name'], ['kongono/extension-admission', 'kongono/extension-build', 'kongono/extension-composition', 'kongono/extension-inspection'], true)) {
                self::assertArrayNotHasKey('kongono/extension-build', $candidate['require']);
            }
        }
        self::assertSame(289, $localEdges);

        $combinedSource = implode('', array_map(static fn(string $path): string => (string) file_get_contents($path), $this->recursivePhpFiles($package . '/src')));
        foreach (['ApplicationGraph', 'Composition', 'Registry', 'Loader', 'Host', 'callable', 'Closure', 'Reflection', 'getenv(', 'putenv(', 'scandir(', 'glob(', 'serialize(', 'include ', 'require ', 'PDO', 'curl_', '$GLOBALS', '$_ENV', '$_SERVER'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $combinedSource);
        }
    }

    public function testExtensionCompositionPackageBoundaryAndIntegrationAreExact(): void
    {
        $root = dirname(__DIR__, 2);
        $package = $root . '/packages/extension-composition';
        $manifest = json_decode((string) file_get_contents($package . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertSame([
            'name' => 'kongono/extension-composition',
            'description' => 'Deterministic capability grants and generated wiring for trusted Kongono extensions.',
            'type' => 'library',
            'require' => [
                'kongono/api-metadata' => '*',
                'kongono/diagnostics' => '*',
                'kongono/extension-admission' => '*',
                'kongono/extension-build' => '*',
                'kongono/extension-contract' => '*',
                'php' => '>=8.5.0 <8.6.0',
            ],
            'require-dev' => ['phpunit/phpunit' => '^13.0'],
            'autoload' => ['psr-4' => ['Kongono\\ExtensionComposition\\' => 'src/']],
            'autoload-dev' => ['psr-4' => ['Kongono\\ExtensionComposition\\Tests\\' => 'tests/']],
        ], $manifest);
        self::assertCount(23, $this->recursivePhpFiles($package . '/src'));
        self::assertCount(10, $this->recursivePhpFiles($package . '/tests'));

        $rootManifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($rootManifest);
        self::assertIsArray($rootManifest['require']);
        self::assertIsArray($rootManifest['autoload-dev']);
        self::assertIsArray($rootManifest['autoload-dev']['psr-4']);
        self::assertIsArray($rootManifest['scripts']);
        self::assertIsArray($rootManifest['scripts']['validate:packages']);
        self::assertSame('@dev', $rootManifest['require']['kongono/extension-composition']);
        self::assertSame('packages/extension-composition/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\ExtensionComposition\\Tests\\']);
        self::assertContains('composer --working-dir=packages/extension-composition validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        foreach (['.php-cs-fixer.dist.php', 'phpstan.neon.dist', 'phpunit.xml.dist'] as $configuration) {
            $contents = (string) file_get_contents($root . '/' . $configuration);
            self::assertSame(1, substr_count($contents, 'packages/extension-composition/src'));
            self::assertSame(1, substr_count($contents, 'packages/extension-composition/tests'));
        }

        $manifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($manifests);
        self::assertCount(75, $manifests);
        $edges = 0;
        foreach ($manifests as $path) {
            $candidate = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($candidate);
            self::assertIsArray($candidate['require']);
            foreach ($candidate['require'] as $dependency => $_constraint) {
                if (str_starts_with($dependency, 'kongono/')) {
                    ++$edges;
                }
            }
            if (!in_array($candidate['name'], ['kongono/extension-composition', 'kongono/extension-host', 'kongono/extension-inspection'], true)) {
                self::assertArrayNotHasKey('kongono/extension-composition', $candidate['require']);
            }
        }
        self::assertSame(289, $edges);

        $source = implode('', array_map(static fn(string $path): string => (string) file_get_contents($path), $this->recursivePhpFiles($package . '/src')));
        foreach (['Kongono\\Composition', 'Kongono\\ApplicationGraph', 'Security\\Permit', 'Principal'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }

    public function testExtensionHostPackageBoundaryAndIntegrationAreExact(): void
    {
        $root = dirname(__DIR__, 2);
        $package = $root . '/packages/extension-host';
        $manifest = json_decode((string) file_get_contents($package . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertSame([
            'name' => 'kongono/extension-host',
            'description' => 'Bounded trusted in-process extension loading and lifecycle isolation for Kongono.',
            'type' => 'library',
            'require' => [
                'kongono/api-metadata' => '*',
                'kongono/diagnostics' => '*',
                'kongono/extension-composition' => '*',
                'php' => '>=8.5.0 <8.6.0',
            ],
            'require-dev' => ['phpunit/phpunit' => '^13.0'],
            'autoload' => ['psr-4' => ['Kongono\\ExtensionHost\\' => 'src/']],
            'autoload-dev' => ['psr-4' => ['Kongono\\ExtensionHost\\Tests\\' => 'tests/']],
        ], $manifest);
        self::assertCount(15, $this->recursivePhpFiles($package . '/src'));
        self::assertCount(9, $this->recursivePhpFiles($package . '/tests'));

        $rootManifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($rootManifest);
        self::assertIsArray($rootManifest['require']);
        self::assertSame('@dev', $rootManifest['require']['kongono/extension-host']);
        self::assertIsArray($rootManifest['autoload-dev']);
        self::assertIsArray($rootManifest['autoload-dev']['psr-4']);
        self::assertSame('packages/extension-host/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\ExtensionHost\\Tests\\']);
        self::assertIsArray($rootManifest['scripts']);
        self::assertIsArray($rootManifest['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/extension-host validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        foreach (['.php-cs-fixer.dist.php', 'phpstan.neon.dist', 'phpunit.xml.dist'] as $configuration) {
            $contents = (string) file_get_contents($root . '/' . $configuration);
            self::assertSame(1, substr_count($contents, 'packages/extension-host/src'));
            self::assertSame(1, substr_count($contents, 'packages/extension-host/tests'));
        }

        $manifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($manifests);
        self::assertCount(75, $manifests);
        $edges = 0;
        foreach ($manifests as $path) {
            $candidate = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($candidate);
            self::assertIsArray($candidate['require']);
            foreach ($candidate['require'] as $dependency => $_constraint) {
                if (str_starts_with($dependency, 'kongono/')) {
                    ++$edges;
                }
            }
            if ('kongono/extension-host' !== $candidate['name']) {
                self::assertArrayNotHasKey('kongono/extension-host', $candidate['require']);
            }
        }
        self::assertSame(289, $edges);

        $source = implode('', array_map(static fn(string $path): string => (string) file_get_contents($path), $this->recursivePhpFiles($package . '/src')));
        foreach (['Kongono\\ApplicationGraph', 'Kongono\\Composition', 'Security\\Permit', 'Principal', 'register_shutdown_function', '__destruct'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
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
            DatabaseExceptionInterface::class,
            MigrationExceptionInterface::class,
            SqlExceptionInterface::class,
            PersistenceExceptionInterface::class,
            PersistenceBuildExceptionInterface::class,
            AuthenticationExceptionInterface::class,
            IdentityAccessExceptionInterface::class,
            'Kongono\\Console\\Exception\\ExceptionInterface',
            'Kongono\\ConsoleApplication\\Exception\\ExceptionInterface',
            'Kongono\\ConsoleBuild\\Exception\\ExceptionInterface',
            'Kongono\\ConsoleCommands\\Exception\\ExceptionInterface',
            'Kongono\\ConsoleRuntime\\Exception\\ExceptionInterface',
            'Kongono\\ConsolePresentation\\Exception\\ExceptionInterface',
            'Kongono\\OperationalEvents\\Exception\\ExceptionInterface',
            'Kongono\\SecurityAudit\\Exception\\ExceptionInterface',
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
            [AuthenticationCleanupException::class, AuthenticationExceptionInterface::class],
            [AuthenticationOperationAndCleanupException::class, AuthenticationExceptionInterface::class],
            [InvalidAuthenticationValueException::class, AuthenticationExceptionInterface::class],
            [InvalidIdentityAccessValueException::class, IdentityAccessExceptionInterface::class],
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
            ['Kongono\\Database\\Exception\\InvalidDatabaseValueException', DatabaseExceptionInterface::class],
            ['Kongono\\Database\\Exception\\InvalidDatabaseStateException', DatabaseExceptionInterface::class],
            ['Kongono\\Database\\Exception\\UnsupportedDatabaseCapabilityException', DatabaseExceptionInterface::class],
            ['Kongono\\Database\\Exception\\DatabaseConnectionException', DatabaseExceptionInterface::class],
            ['Kongono\\Database\\Exception\\DatabaseBindingException', DatabaseExceptionInterface::class],
            ['Kongono\\Database\\Exception\\DatabaseExecutionException', DatabaseExceptionInterface::class],
            ['Kongono\\Database\\Exception\\DatabaseResultException', DatabaseExceptionInterface::class],
            ['Kongono\\Database\\Exception\\DatabaseTransactionBeginException', DatabaseExceptionInterface::class],
            ['Kongono\\Database\\Exception\\DatabaseTransactionCommitException', DatabaseExceptionInterface::class],
            ['Kongono\\Database\\Exception\\DatabaseTransactionRollbackException', DatabaseExceptionInterface::class],
            ['Kongono\\Database\\Exception\\DatabaseCleanupException', DatabaseExceptionInterface::class],
            ['Kongono\\Database\\Exception\\CombinedDatabaseFailureException', DatabaseExceptionInterface::class],
            ['Kongono\\DatabaseMigration\\Exception\\InvalidMigrationDefinitionException', MigrationExceptionInterface::class],
            ['Kongono\\DatabaseMigration\\Exception\\MigrationHistoryMismatchException', MigrationExceptionInterface::class],
            ['Kongono\\DatabaseMigration\\Exception\\DestructiveMigrationReviewRequiredException', MigrationExceptionInterface::class],
            ['Kongono\\DatabaseMigration\\Exception\\MigrationNotReversibleException', MigrationExceptionInterface::class],
            ['Kongono\\DatabaseMigration\\Exception\\MigrationLockUnavailableException', MigrationExceptionInterface::class],
            ['Kongono\\DatabaseMigration\\Exception\\MigrationExecutionException', MigrationExceptionInterface::class],
            ['Kongono\\DatabaseMigration\\Exception\\MigrationUncertainStateException', MigrationExceptionInterface::class],
            ['Kongono\\DatabaseMigration\\Exception\\CombinedMigrationFailureException', MigrationExceptionInterface::class],
            ['Kongono\\DatabaseSql\\Exception\\InvalidSqlPlanException', SqlExceptionInterface::class],
            ['Kongono\\DatabaseSql\\Exception\\UnsupportedDialectCapabilityException', SqlExceptionInterface::class],
            ['Kongono\\DatabaseSql\\Exception\\SqlCompilationException', SqlExceptionInterface::class],
            ['Kongono\\Persistence\\Exception\\InvalidPersistenceValueException', PersistenceExceptionInterface::class],
            ['Kongono\\Persistence\\Exception\\InvalidPersistenceStateException', PersistenceExceptionInterface::class],
            ['Kongono\\Persistence\\Exception\\PersistenceOperationException', PersistenceExceptionInterface::class],
            ['Kongono\\Persistence\\Exception\\PersistenceCleanupException', PersistenceExceptionInterface::class],
            ['Kongono\\Persistence\\Exception\\CombinedPersistenceFailureException', PersistenceExceptionInterface::class],
            ['Kongono\\PersistenceBuild\\Exception\\InvalidPersistenceSourceException', PersistenceBuildExceptionInterface::class],
            ['Kongono\\PersistenceBuild\\Exception\\InvalidPersistenceArtifactException', PersistenceBuildExceptionInterface::class],
            ['Kongono\\PersistenceBuild\\Exception\\StalePersistenceArtifactException', PersistenceBuildExceptionInterface::class],
            ['Kongono\\PersistenceBuild\\Exception\\PersistenceArtifactPublicationException', PersistenceBuildExceptionInterface::class],
            ['Kongono\\PersistenceBuild\\Exception\\InvalidGeneratedPersistenceStateException', PersistenceBuildExceptionInterface::class],
            ['Kongono\\PersistenceBuild\\Exception\\GeneratedPersistenceStatePublicationException', PersistenceBuildExceptionInterface::class],
            ['Kongono\\Console\\Exception\\InvalidConsoleValueException', 'Kongono\\Console\\Exception\\ExceptionInterface'],
            ['Kongono\\ConsoleApplication\\Exception\\InvalidConsoleApplicationValueException', 'Kongono\\ConsoleApplication\\Exception\\ExceptionInterface'],
            ['Kongono\\ConsoleBuild\\Exception\\InvalidConsoleSourceException', 'Kongono\\ConsoleBuild\\Exception\\ExceptionInterface'],
            ['Kongono\\ConsoleBuild\\Exception\\InvalidConsoleArtifactException', 'Kongono\\ConsoleBuild\\Exception\\ExceptionInterface'],
            ['Kongono\\ConsoleBuild\\Exception\\StaleConsoleArtifactException', 'Kongono\\ConsoleBuild\\Exception\\ExceptionInterface'],
            ['Kongono\\ConsoleCommands\\Exception\\InvalidConsoleCommandsValueException', 'Kongono\\ConsoleCommands\\Exception\\ExceptionInterface'],
            ['Kongono\\ConsoleRuntime\\Exception\\InvalidCommandRuntimeValueException', 'Kongono\\ConsoleRuntime\\Exception\\ExceptionInterface'],
            ['Kongono\\ConsoleRuntime\\Exception\\CommandExecutionException', 'Kongono\\ConsoleRuntime\\Exception\\ExceptionInterface'],
            ['Kongono\\ConsolePresentation\\Exception\\InvalidConsolePresentationException', 'Kongono\\ConsolePresentation\\Exception\\ExceptionInterface'],
            ['Kongono\\ConsolePresentation\\Exception\\CommandOutputException', 'Kongono\\ConsolePresentation\\Exception\\ExceptionInterface'],
            ['Kongono\\OperationalEvents\\Exception\\InvalidOperationalEventSchemaException', 'Kongono\\OperationalEvents\\Exception\\ExceptionInterface'],
            ['Kongono\\OperationalEvents\\Exception\\InvalidOperationalEventValueException', 'Kongono\\OperationalEvents\\Exception\\ExceptionInterface'],
            ['Kongono\\OperationalEvents\\Exception\\OperationalEventRecordingException', 'Kongono\\OperationalEvents\\Exception\\ExceptionInterface'],
            ['Kongono\\SecurityAudit\\Exception\\InvalidSecurityAuditSchemaException', 'Kongono\\SecurityAudit\\Exception\\ExceptionInterface'],
            ['Kongono\\SecurityAudit\\Exception\\InvalidSecurityAuditValueException', 'Kongono\\SecurityAudit\\Exception\\ExceptionInterface'],
            ['Kongono\\SecurityAudit\\Exception\\SecurityAuditRecordingException', 'Kongono\\SecurityAudit\\Exception\\ExceptionInterface'],
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
                ...\Kongono\Console\Diagnostic\ConsoleDiagnostic::cases(),
                ...\Kongono\ConsoleApplication\Diagnostic\ConsoleApplicationDiagnostic::cases(),
                ...\Kongono\ConsoleBuild\Diagnostic\ConsoleBuildDiagnostic::cases(),
                ...\Kongono\ConsoleRuntime\Diagnostic\ConsoleRuntimeDiagnostic::cases(),
                ...\Kongono\ConsolePresentation\Diagnostic\ConsolePresentationDiagnostic::cases(),
                ...DatabaseDiagnostic::cases(),
                ...MappingDiagnostic::cases(),
                ...MigrationDiagnostic::cases(),
                ...SqlDiagnostic::cases(),
                ...PersistenceDiagnostic::cases(),
                ...PersistenceBuildDiagnostic::cases(),
                ...PersistenceApplicationDiagnostic::cases(),
                ...('Kongono\\SecurityRuntime\\Diagnostic\\SecurityRuntimeDiagnostic')::cases(),
                ...('Kongono\\OperationalEvents\\Diagnostic\\OperationalEventsDiagnostic')::cases(),
                ...('Kongono\\SecurityAudit\\Diagnostic\\SecurityAuditDiagnostic')::cases(),
                ...('Kongono\\ApplicationGraphIntelligence\\Diagnostic\\ApplicationGraphIntelligenceDiagnostic')::cases(),
                ...('Kongono\\ExtensionInspection\\Diagnostic\\ExtensionInspectionDiagnostic')::cases(),
                ...('Kongono\\AgentInspection\\Diagnostic\\AgentInspectionDiagnostic')::cases(),
                ...('Kongono\\ModelInvocationApplication\\Diagnostic\\ModelInvocationApplicationDiagnostic')::cases(),
            ],
        );

        self::assertCount(310, $identifiers);
        self::assertCount(310, array_unique($identifiers));
        self::assertCount(5, array_filter($identifiers, static fn(string $identifier): bool => 1 === preg_match('/^KGN-AGENT-INSPECTION-1\\d{3}$/D', $identifier)));
        self::assertCount(7, array_filter($identifiers, static fn(string $identifier): bool => 1 === preg_match('/^KGN-MODEL-APPLICATION-1\\d{3}$/D', $identifier)));
        self::assertCount(7, array_filter($identifiers, static fn(string $identifier): bool => 1 === preg_match('/^KGN-EXTENSION-INSPECTION-1\d{3}$/D', $identifier)));
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
        self::assertCount(3, array_filter($identifiers, static fn(string $identifier): bool => 1 === preg_match('/^KGN-SQL-1\\d{3}$/D', $identifier)));
        self::assertCount(19, array_filter($identifiers, static fn(string $identifier): bool => 1 === preg_match('/^KGN-COMPOSITION-1\\d{3}$/D', $identifier)));
        self::assertCount(3, array_filter($identifiers, static fn(string $identifier): bool => 1 === preg_match('/^KGN-SECURITY-RUNTIME-1\\d{3}$/D', $identifier)));
        self::assertCount(3, array_filter($identifiers, static fn(string $identifier): bool => 1 === preg_match('/^KGN-OP-EVENTS-1\\d{3}$/D', $identifier)));
        self::assertCount(3, array_filter($identifiers, static fn(string $identifier): bool => 1 === preg_match('/^KGN-SEC-AUDIT-1\\d{3}$/D', $identifier)));
        self::assertCount(10, array_filter($identifiers, static fn(string $identifier): bool => 1 === preg_match('/^KGN-CONSOLE-1\\d{3}$/D', $identifier)));
        self::assertCount(5, array_filter($identifiers, static fn(string $identifier): bool => 1 === preg_match('/^KGN-CONSOLE-APPLICATION-1\\d{3}$/D', $identifier)));
        self::assertCount(5, array_filter($identifiers, static fn(string $identifier): bool => 1 === preg_match('/^KGN-CONSOLE-BUILD-1\\d{3}$/D', $identifier)));
        self::assertCount(6, array_filter($identifiers, static fn(string $identifier): bool => 1 === preg_match('/^KGN-CONSOLE-RUNTIME-1\\d{3}$/D', $identifier)));
        self::assertCount(11, array_filter($identifiers, static fn(string $identifier): bool => 1 === preg_match('/^KGN-DATABASE-1\\d{3}$/D', $identifier)));
        self::assertCount(9, array_filter($identifiers, static fn(string $identifier): bool => 1 === preg_match('/^KGN-MAPPING-1\\d{3}$/D', $identifier)));
        self::assertCount(7, array_filter($identifiers, static fn(string $identifier): bool => 1 === preg_match('/^KGN-MIGRATION-1\\d{3}$/D', $identifier)));
        self::assertCount(9, array_filter($identifiers, static fn(string $identifier): bool => 1 === preg_match('/^KGN-PERSISTENCE-1\\d{3}$/D', $identifier)));
        self::assertCount(20, array_filter($identifiers, static fn(string $identifier): bool => 1 === preg_match('/^KGN-PERSISTENCE-BUILD-1\\d{3}$/D', $identifier)));
        self::assertCount(1, array_filter($identifiers, static fn(string $identifier): bool => 1 === preg_match('/^KGN-PERSISTENCE-APPLICATION-1\\d{3}$/D', $identifier)));

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
            DatabaseExceptionInterface::class,
            MigrationExceptionInterface::class,
            SqlExceptionInterface::class,
            PersistenceExceptionInterface::class,
            PersistenceBuildExceptionInterface::class,
            'Kongono\\Console\\Exception\\ExceptionInterface',
            'Kongono\\ConsoleApplication\\Exception\\ExceptionInterface',
            'Kongono\\ConsoleBuild\\Exception\\ExceptionInterface',
            'Kongono\\ConsoleCommands\\Exception\\ExceptionInterface',
            'Kongono\\ConsoleRuntime\\Exception\\ExceptionInterface',
            'Kongono\\ConsolePresentation\\Exception\\ExceptionInterface',
            'Kongono\\OperationalEvents\\Exception\\ExceptionInterface',
            'Kongono\\SecurityAudit\\Exception\\ExceptionInterface',
        ] as $marker) {
            $reflection = new ReflectionClass($marker);
            $methods = array_values(array_filter(
                $reflection->getMethods(),
                static fn(ReflectionMethod $method): bool => $marker === $method->getDeclaringClass()->getName(),
            ));
            $methodNames = array_map(static fn(ReflectionMethod $method): string => $method->getName(), $methods);
            sort($methodNames);

            self::assertSame([Throwable::class, \Stringable::class], $reflection->getInterfaceNames());
            $expectedMethods = in_array($marker, [KernelExceptionInterface::class, GraphExceptionInterface::class, DependencyInjectionExceptionInterface::class, HttpMessageExceptionInterface::class, HttpServerExceptionInterface::class, RoutingExceptionInterface::class, WebApplicationExceptionInterface::class, WebRuntimeExceptionInterface::class, PhpCgiExceptionInterface::class, KnowledgeExceptionInterface::class, ChieExceptionInterface::class, CompositionExceptionInterface::class, DatabaseExceptionInterface::class, MigrationExceptionInterface::class, SqlExceptionInterface::class, PersistenceExceptionInterface::class, PersistenceBuildExceptionInterface::class, 'Kongono\\Console\\Exception\\ExceptionInterface', 'Kongono\\ConsoleApplication\\Exception\\ExceptionInterface', 'Kongono\\ConsoleBuild\\Exception\\ExceptionInterface', 'Kongono\\ConsoleCommands\\Exception\\ExceptionInterface', 'Kongono\\ConsoleRuntime\\Exception\\ExceptionInterface', 'Kongono\\ConsolePresentation\\Exception\\ExceptionInterface', 'Kongono\\OperationalEvents\\Exception\\ExceptionInterface', 'Kongono\\SecurityAudit\\Exception\\ExceptionInterface'], true)
                ? ['diagnosticContext', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle']
                : ['diagnosticId', 'diagnosticRemediation', 'diagnosticTitle'];
            self::assertSame($expectedMethods, $methodNames);

            foreach ($methods as $method) {
                $expectedReturnType = in_array($marker, [KernelExceptionInterface::class, GraphExceptionInterface::class, DependencyInjectionExceptionInterface::class, HttpMessageExceptionInterface::class, HttpServerExceptionInterface::class, RoutingExceptionInterface::class, WebApplicationExceptionInterface::class, WebRuntimeExceptionInterface::class, PhpCgiExceptionInterface::class, KnowledgeExceptionInterface::class, ChieExceptionInterface::class, CompositionExceptionInterface::class, DatabaseExceptionInterface::class, MigrationExceptionInterface::class, SqlExceptionInterface::class, PersistenceExceptionInterface::class, PersistenceBuildExceptionInterface::class, 'Kongono\\Console\\Exception\\ExceptionInterface', 'Kongono\\ConsoleApplication\\Exception\\ExceptionInterface', 'Kongono\\ConsoleBuild\\Exception\\ExceptionInterface', 'Kongono\\ConsoleCommands\\Exception\\ExceptionInterface', 'Kongono\\ConsoleRuntime\\Exception\\ExceptionInterface', 'Kongono\\ConsolePresentation\\Exception\\ExceptionInterface', 'Kongono\\OperationalEvents\\Exception\\ExceptionInterface', 'Kongono\\SecurityAudit\\Exception\\ExceptionInterface'], true)
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
            DatabaseExceptionInterface::class,
            MigrationExceptionInterface::class,
            SqlExceptionInterface::class,
            PersistenceExceptionInterface::class,
            PersistenceBuildExceptionInterface::class,
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
        foreach (['src' => 39, 'tests' => 35] as $directory => $expectedCount) {
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
        self::assertSame(['src' => 39, 'tests' => 35], $compositionCounts);

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
            'kongono/agent-admission' => '@dev',
            'kongono/agent-application' => '@dev',
            'kongono/agent-declaration-build' => '@dev',
            'kongono/agent-inspection' => '@dev',
            'kongono/agent-principal-contract' => '@dev',
            'kongono/agent-runtime' => '@dev',
            'kongono/api-metadata' => '@dev',
            'kongono/application-graph' => '@dev',
            'kongono/application-graph-artifact' => '@dev',
            'kongono/application-graph-build' => '@dev',
            'kongono/application-graph-composition' => '@dev',
            'kongono/application-graph-contract' => '@dev',
            'kongono/application-graph-intelligence' => '@dev',
            'kongono/authentication' => '@dev',
            'kongono/authorization' => '@dev',
            'kongono/chie' => '@dev',
            'kongono/composition' => '@dev',
            'kongono/console' => '@dev',
            'kongono/console-application' => '@dev',
            'kongono/console-build' => '@dev',
            'kongono/console-commands' => '@dev',
            'kongono/console-presentation' => '@dev',
            'kongono/console-runtime' => '@dev',
            'kongono/database' => '@dev',
            'kongono/database-application' => '@dev',
            'kongono/database-mapping' => '@dev',
            'kongono/database-migration' => '@dev',
            'kongono/database-migration-pgsql' => '@dev',
            'kongono/database-pdo-pgsql' => '@dev',
            'kongono/database-sql' => '@dev',
            'kongono/database-sql-pgsql' => '@dev',
            'kongono/dependency-injection' => '@dev',
            'kongono/diagnostic-activation' => '@dev',
            'kongono/diagnostic-handler' => '@dev',
            'kongono/diagnostic-presentation' => '@dev',
            'kongono/diagnostics' => '@dev',
            'kongono/extension-admission' => '@dev',
            'kongono/extension-build' => '@dev',
            'kongono/extension-composition' => '@dev',
            'kongono/extension-contract' => '@dev',
            'kongono/extension-host' => '@dev',
            'kongono/extension-inspection' => '@dev',
            'kongono/hantei' => '@dev',
            'kongono/http-message' => '@dev',
            'kongono/http-server' => '@dev',
            'kongono/identity-access' => '@dev',
            'kongono/kernel' => '@dev',
            'kongono/knowledge' => '@dev',
            'kongono/metrics' => '@dev',
            'kongono/model-invocation-agent-bridge' => '@dev',
            'kongono/model-invocation-application' => '@dev',
            'kongono/model-invocation-build' => '@dev',
            'kongono/model-invocation-contract' => '@dev',
            'kongono/model-invocation-inspection' => '@dev',
            'kongono/model-invocation-runtime' => '@dev',
            'kongono/observability' => '@dev',
            'kongono/observability-application' => '@dev',
            'kongono/observability-build' => '@dev',
            'kongono/opentelemetry' => '@dev',
            'kongono/operational-events' => '@dev',
            'kongono/oshie' => '@dev',
            'kongono/persistence' => '@dev',
            'kongono/persistence-application' => '@dev',
            'kongono/persistence-build' => '@dev',
            'kongono/php-cgi' => '@dev',
            'kongono/routing' => '@dev',
            'kongono/security-application' => '@dev',
            'kongono/security-audit' => '@dev',
            'kongono/security-enforcement' => '@dev',
            'kongono/security-http' => '@dev',
            'kongono/security-policy-build' => '@dev',
            'kongono/security-runtime' => '@dev',
            'kongono/tracing' => '@dev',
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
        self::assertSame('packages/console/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\Console\\Tests\\']);
        self::assertSame('packages/console-application/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\ConsoleApplication\\Tests\\']);
        self::assertSame('packages/console-build/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\ConsoleBuild\\Tests\\']);
        self::assertSame('packages/console-commands/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\ConsoleCommands\\Tests\\']);
        self::assertSame('packages/console-presentation/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\ConsolePresentation\\Tests\\']);
        self::assertSame('packages/console-runtime/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\ConsoleRuntime\\Tests\\']);
        self::assertSame('packages/database-application/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\DatabaseApplication\\Tests\\']);
        self::assertSame('packages/database-mapping/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\DatabaseMapping\\Tests\\']);
        self::assertSame('packages/knowledge/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\Knowledge\\Tests\\']);
        self::assertSame('packages/metrics/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\Metrics\\Tests\\']);
        self::assertSame('packages/observability-application/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\ObservabilityApplication\\Tests\\']);
        self::assertSame('packages/observability-build/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\ObservabilityBuild\\Tests\\']);
        self::assertSame('packages/opentelemetry/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\OpenTelemetry\\Tests\\']);
        self::assertSame('packages/operational-events/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\OperationalEvents\\Tests\\']);
        self::assertSame('packages/oshie/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\Oshie\\Tests\\']);
        self::assertSame('packages/persistence/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\Persistence\\Tests\\']);
        self::assertSame('packages/persistence-build/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\PersistenceBuild\\Tests\\']);
        self::assertSame('packages/persistence-application/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\PersistenceApplication\\Tests\\']);
        self::assertSame('packages/hantei/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\Hantei\\Tests\\']);
        self::assertSame('packages/routing/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\Routing\\Tests\\']);
        self::assertSame('packages/security-enforcement/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\SecurityEnforcement\\Tests\\']);
        self::assertSame('packages/security-audit/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\SecurityAudit\\Tests\\']);
        self::assertSame('packages/security-http/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\SecurityHttp\\Tests\\']);
        self::assertSame('packages/security-runtime/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\SecurityRuntime\\Tests\\']);
        self::assertSame('packages/web-application/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\Web\\Application\\Tests\\']);
        self::assertSame('packages/web-runtime/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\Web\\Runtime\\Tests\\']);
        self::assertSame('packages/php-cgi/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\PhpCgi\\Tests\\']);
        self::assertSame('skeletons/minimal/src/', $rootManifest['autoload-dev']['psr-4']['App\\']);
        self::assertSame('skeletons/data-minimal/src/', $rootManifest['autoload-dev']['psr-4']['KongonoDataMinimal\\']);
        self::assertSame('skeletons/observability-minimal/src/', $rootManifest['autoload-dev']['psr-4']['KongonoObservabilityMinimal\\']);
        self::assertIsArray($rootManifest['scripts']);
        self::assertIsArray($rootManifest['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/http-message validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/http-server validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/chie validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/composition validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/console validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/console-application validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/console-build validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/console-commands validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/console-presentation validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/console-runtime validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/database-application validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/database-mapping validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/knowledge validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/metrics validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/observability-application validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/observability-build validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/opentelemetry validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/operational-events validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/oshie validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/persistence validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/persistence-build validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/persistence-application validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/hantei validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/routing validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/security-enforcement validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/security-audit validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/security-http validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/security-runtime validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        self::assertContains('composer --working-dir=packages/php-cgi validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        $validatePackages = array_values($rootManifest['scripts']['validate:packages']);
        self::assertNotSame([], $validatePackages);
        self::assertSame('composer --working-dir=packages/web-runtime validate --no-check-publish', $validatePackages[count($validatePackages) - 1]);
        self::assertSame([
            'composer --working-dir=skeletons/data-minimal validate --no-check-publish',
            'composer --working-dir=skeletons/minimal validate --no-check-publish',
            'composer --working-dir=skeletons/observability-minimal validate --no-check-publish',
            'composer --working-dir=skeletons/persistence-minimal validate --no-check-publish',
            'composer --working-dir=skeletons/security-minimal validate --no-check-publish',
        ], $rootManifest['scripts']['validate:skeleton']);
        self::assertIsArray($rootManifest['scripts']['quality']);
        self::assertSame('@validate:skeleton', $rootManifest['scripts']['quality'][2]);

        $allPackageManifests = glob(dirname(__DIR__, 2) . '/packages/*/composer.json');
        self::assertIsArray($allPackageManifests);
        self::assertCount(75, $allPackageManifests);
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
            '.php-cs-fixer.dist.php' => ['packages/console/src', 'packages/console/tests', 'packages/console-build/src', 'packages/console-build/tests', 'packages/console-runtime/src', 'packages/console-runtime/tests', 'packages/routing/src', 'packages/routing/tests', 'packages/web-application/src', 'packages/web-application/tests', 'packages/web-runtime/src', 'packages/web-runtime/tests', 'packages/php-cgi/src', 'packages/php-cgi/tests', 'packages/persistence/src', 'packages/persistence/tests', 'packages/persistence-build/src', 'packages/persistence-build/tests', 'packages/persistence-application/src', 'packages/persistence-application/tests', 'skeletons/persistence-minimal/src'],
            'phpstan.neon.dist' => ['packages/console/src', 'packages/console/tests', 'packages/console-build/src', 'packages/console-build/tests', 'packages/console-runtime/src', 'packages/console-runtime/tests', 'packages/routing/src', 'packages/routing/tests', 'packages/http-server/src', 'packages/http-server/tests', 'packages/web-application/src', 'packages/web-application/tests', 'packages/web-runtime/src', 'packages/web-runtime/tests', 'packages/php-cgi/src', 'packages/php-cgi/tests', 'packages/persistence/src', 'packages/persistence/tests', 'packages/persistence-build/src', 'packages/persistence-build/tests', 'packages/persistence-application/src', 'packages/persistence-application/tests', 'skeletons/persistence-minimal/src'],
            'phpunit.xml.dist' => ['packages/console/src', 'packages/console/tests', 'packages/console-build/src', 'packages/console-build/tests', 'packages/console-runtime/src', 'packages/console-runtime/tests', 'packages/routing/src', 'packages/routing/tests', 'packages/http-server/src', 'packages/http-server/tests', 'packages/web-application/src', 'packages/web-application/tests', 'packages/web-runtime/src', 'packages/web-runtime/tests', 'packages/php-cgi/src', 'packages/php-cgi/tests', 'packages/persistence/src', 'packages/persistence/tests', 'packages/persistence-build/src', 'packages/persistence-build/tests', 'packages/persistence-application/src', 'packages/persistence-application/tests'],
        ] as $configuration => $needles) {
            $contents = file_get_contents(dirname(__DIR__, 2) . '/' . $configuration);
            self::assertIsString($contents);
            foreach ($needles as $needle) {
                self::assertStringContainsString($needle, $contents);
                $expectedOccurrences = 'phpstan.neon.dist' === $configuration && 'packages/persistence-application/tests' === $needle ? 2 : 1;
                self::assertSame($expectedOccurrences, substr_count($contents, $needle));
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
        self::assertCount(75, $packageManifests);
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
        self::assertCount(75, $sourceDirectories);
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
        self::assertCount(1358, $productionFiles);
        foreach ($productionFiles as $productionFile) {
            $source = file_get_contents($productionFile);
            self::assertIsString($source);
            foreach (['Kongono\\Tests\\External', 'FastCgiRecordCodec', 'FastCgiClient', 'FastCgiResponse', 'PhpCgiProcess', 'Phase3AcceptanceRunner', 'KONGONO_PHASE3_ACCEPTANCE_JOURNAL'] as $forbidden) {
                self::assertStringNotContainsString($forbidden, $source);
            }
        }

        $manifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($manifests);
        self::assertCount(75, $manifests);
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

    /** @return list<string> */
    private function recursivePhpFiles(string $root): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && 'php' === $file->getExtension()) {
                $files[] = $file->getPathname();
            }
        }
        sort($files);

        return $files;
    }

    private function sourceWithoutComments(string $source): string
    {
        $code = '';
        foreach (token_get_all($source) as $token) {
            if (is_array($token)) {
                if (T_COMMENT === $token[0] || T_DOC_COMMENT === $token[0]) {
                    continue;
                }
                $code .= $token[1];

                continue;
            }
            $code .= $token;
        }

        return $code;
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
        self::assertCount(75, $packageSourceDirectories);
        $packageManifests = glob(dirname(__DIR__, 2) . '/packages/*/composer.json');
        self::assertIsArray($packageManifests);
        self::assertCount(75, $packageManifests);
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

    public function testApplicationGraphCompositionPackageBoundaryAndIntegrationAreExact(): void
    {
        $root = dirname(__DIR__, 2);
        $package = $root . '/packages/application-graph-composition';
        $manifest = json_decode((string) file_get_contents($package . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertIsArray($manifest['require']);
        self::assertIsArray($manifest['require-dev']);
        self::assertSame([
            'ext-hash' => '*',
            'ext-json' => '*',
            'kongono/api-metadata' => '*',
            'kongono/application-graph-build' => '*',
            'kongono/application-graph-contract' => '*',
            'kongono/composition' => '*',
            'kongono/diagnostics' => '*',
            'php' => '>=8.5.0 <8.6.0',
        ], $manifest['require']);
        self::assertSame(['phpunit/phpunit' => '^13.0'], $manifest['require-dev']);

        $sourceFiles = array_map(
            static fn(string $path): string => str_replace('\\', '/', substr($path, strlen($package . '/src/'))),
            $this->recursivePhpFiles($package . '/src'),
        );
        self::assertSame([
            'Build/CompositionApplicationGraphBuilder.php',
            'Diagnostic/CompositionGraphDiagnostic.php',
            'Exception/CompositionGraphException.php',
            'Exception/ExceptionInterface.php',
            'Exception/InvalidCompositionGraphProjectionException.php',
            'Internal/CanonicalJson.php',
            'Internal/CompositionArtifactGuard.php',
            'Internal/CompositionModelProjector.php',
            'Internal/FramedIdentityDeriver.php',
            'Projection/CompositionIrProjection.php',
            'Projection/CompositionIrProjector.php',
        ], $sourceFiles);
        self::assertCount(7, $this->recursivePhpFiles($package . '/tests'));

        $rootManifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($rootManifest);
        self::assertIsArray($rootManifest['require']);
        self::assertIsArray($rootManifest['autoload-dev']);
        self::assertIsArray($rootManifest['autoload-dev']['psr-4']);
        self::assertIsArray($rootManifest['scripts']);
        self::assertIsArray($rootManifest['scripts']['validate:packages']);
        self::assertSame('@dev', $rootManifest['require']['kongono/application-graph-composition']);
        self::assertSame('packages/application-graph-composition/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\ApplicationGraphComposition\\Tests\\']);
        self::assertContains('composer --working-dir=packages/application-graph-composition validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        foreach (['packages/application-graph-build', 'packages/application-graph-composition', 'packages/application-graph-contract'] as $graphPackage) {
            self::assertSame(1, substr_count((string) file_get_contents($root . '/phpunit.xml.dist'), '<directory>' . $graphPackage . '/tests</directory>'));
            self::assertSame(1, substr_count((string) file_get_contents($root . '/phpunit.xml.dist'), '<directory>' . $graphPackage . '/src</directory>'));
        }
    }

    public function testApplicationGraphArtifactPackageAndArtifactBoundaryAreExact(): void
    {
        $root = dirname(__DIR__, 2);
        $package = $root . '/packages/application-graph-artifact';
        $manifest = json_decode((string) file_get_contents($package . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertIsArray($manifest['require']);
        self::assertIsArray($manifest['require-dev']);
        self::assertSame([
            'php' => '>=8.5.0 <8.6.0',
            'ext-hash' => '*',
            'ext-json' => '*',
            'kongono/api-metadata' => '*',
            'kongono/application-graph-build' => '*',
            'kongono/application-graph-contract' => '*',
            'kongono/diagnostics' => '*',
        ], $manifest['require']);
        self::assertSame(['phpunit/phpunit' => '^13.0'], $manifest['require-dev']);

        $sourceFiles = array_map(
            static fn(string $path): string => str_replace('\\', '/', substr($path, strlen($package . '/src/'))),
            $this->recursivePhpFiles($package . '/src'),
        );
        self::assertSame([
            'Artifact/ApplicationGraphArtifact.php',
            'Artifact/ApplicationGraphArtifactBuilder.php',
            'Artifact/ApplicationGraphArtifactLoader.php',
            'Artifact/ApplicationGraphArtifactPublisher.php',
            'Artifact/ApplicationGraphArtifactValidator.php',
            'Diagnostic/ApplicationGraphArtifactDiagnostic.php',
            'Exception/ApplicationGraphArtifactException.php',
            'Exception/ApplicationGraphArtifactLoadException.php',
            'Exception/ApplicationGraphArtifactPublicationException.php',
            'Exception/ExceptionInterface.php',
            'Exception/InvalidApplicationGraphArtifactException.php',
            'Exception/StaleApplicationGraphArtifactException.php',
            'Identity/GraphIdentity.php',
            'Inspection/ApplicationGraphArtifactInspection.php',
            'Internal/CanonicalJson.php',
            'Internal/GraphArtifactCodec.php',
            'Internal/PathGuard.php',
            'Internal/StrictJsonDecoder.php',
        ], $sourceFiles);
        self::assertCount(9, $this->recursivePhpFiles($package . '/tests'));

        $rootManifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($rootManifest);
        self::assertIsArray($rootManifest['require']);
        self::assertIsArray($rootManifest['autoload-dev']);
        self::assertIsArray($rootManifest['autoload-dev']['psr-4']);
        self::assertIsArray($rootManifest['scripts']);
        self::assertIsArray($rootManifest['scripts']['validate:packages']);
        self::assertSame('@dev', $rootManifest['require']['kongono/application-graph-artifact']);
        self::assertSame('packages/application-graph-artifact/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\ApplicationGraphArtifact\\Tests\\']);
        self::assertContains('composer --working-dir=packages/application-graph-artifact validate --no-check-publish', $rootManifest['scripts']['validate:packages']);
        foreach (['phpstan.neon.dist', 'phpunit.xml.dist'] as $configuration) {
            $contents = (string) file_get_contents($root . '/' . $configuration);
            self::assertSame(1, substr_count($contents, 'packages/application-graph-artifact/src'));
            self::assertSame(1, substr_count($contents, 'packages/application-graph-artifact/tests'));
        }

        $packageManifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($packageManifests);
        self::assertCount(75, $packageManifests);
        $names = [];
        $localEdges = 0;
        foreach ($packageManifests as $candidatePath) {
            $candidate = json_decode((string) file_get_contents($candidatePath), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($candidate);
            self::assertIsString($candidate['name']);
            self::assertIsArray($candidate['require']);
            $names[$candidate['name']] = true;
        }
        foreach ($packageManifests as $candidatePath) {
            $candidate = json_decode((string) file_get_contents($candidatePath), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($candidate);
            self::assertIsString($candidate['name']);
            self::assertIsArray($candidate['require']);
            foreach (array_keys($candidate['require']) as $dependency) {
                if (isset($names[$dependency])) {
                    ++$localEdges;
                }
            }
            if (!in_array($candidate['name'], ['kongono/application-graph-artifact', 'kongono/application-graph-intelligence', 'kongono/console-application'], true)) {
                self::assertArrayNotHasKey('kongono/application-graph-artifact', $candidate['require']);
            }
        }
        self::assertSame(289, $localEdges);

        $inspection = new ReflectionClass('Kongono\\ApplicationGraphArtifact\\Inspection\\ApplicationGraphArtifactInspection');
        self::assertTrue($inspection->isFinal());
        self::assertTrue($inspection->isReadOnly());
        self::assertSame(
            ['__construct', 'artifactSha256', 'contractVersion', 'graphIdentity', 'nodeCount', 'nodeTypeCount', 'relationCount', 'relationTypeCount'],
            $this->declaredPublicMethodNames($inspection),
        );

        foreach ($sourceFiles as $sourceFile) {
            $contents = (string) file_get_contents($package . '/src/' . $sourceFile);
            if (!str_contains($sourceFile, 'Loader.php') && !str_contains($sourceFile, 'Publisher.php') && 'Internal/PathGuard.php' !== $sourceFile) {
                self::assertDoesNotMatchRegularExpression('/\b(?:fopen|fread|file_get_contents|hash_file|realpath|stat|lstat|is_file|is_dir|is_link)\s*\(/', $contents);
            }
            self::assertStringNotContainsString('implementation_sha256', $contents);
        }
    }

    public function testApplicationGraphIntelligencePackageAndBoundaryAreExact(): void
    {
        $root = dirname(__DIR__, 2);
        $package = $root . '/packages/application-graph-intelligence';
        $manifest = json_decode((string) file_get_contents($package . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertSame([
            'kongono/api-metadata' => '*',
            'kongono/application-graph-artifact' => '*',
            'kongono/application-graph-contract' => '*',
            'kongono/diagnostics' => '*',
            'php' => '>=8.5.0 <8.6.0',
        ], $manifest['require']);
        self::assertSame(['psr-4' => ['Kongono\\ApplicationGraphIntelligence\\' => 'src/']], $manifest['autoload']);
        self::assertSame(['psr-4' => ['Kongono\\ApplicationGraphIntelligence\\Tests\\' => 'tests/']], $manifest['autoload-dev']);

        $sourceFiles = array_map(
            static fn(string $path): string => str_replace('\\', '/', substr($path, strlen($package . '/src/'))),
            $this->recursivePhpFiles($package . '/src'),
        );
        self::assertSame([
            'Derivation/ApplicationGraphIntelligenceDeriver.php',
            'Derivation/ApplicationGraphIntelligenceView.php',
            'Diagnostic/ApplicationGraphIntelligenceDiagnostic.php',
            'Exception/ApplicationGraphIntelligenceException.php',
            'Exception/ExceptionInterface.php',
            'Exception/InvalidApplicationGraphIntelligenceValueException.php',
            'Role/DeveloperIntelligenceRole.php',
            'Verification/ApplicationGraphIntelligenceVerdict.php',
            'Verification/ApplicationGraphIntelligenceVerification.php',
            'Verification/ApplicationGraphIntelligenceVerifier.php',
        ], $sourceFiles);
        self::assertCount(6, $this->recursivePhpFiles($package . '/tests'));
        self::assertSame(['README.md', 'composer.json', 'src', 'tests'], array_values(array_diff((array) scandir($package), ['.', '..'])));

        $rootManifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($rootManifest);
        $rootRequire = $rootManifest['require'];
        $rootAutoloadDev = $rootManifest['autoload-dev'];
        $rootScripts = $rootManifest['scripts'];
        self::assertIsArray($rootRequire);
        self::assertIsArray($rootAutoloadDev);
        self::assertIsArray($rootScripts);
        $rootAutoloadDevPsr4 = $rootAutoloadDev['psr-4'];
        $validatePackages = $rootScripts['validate:packages'];
        self::assertIsArray($rootAutoloadDevPsr4);
        self::assertIsArray($validatePackages);
        self::assertSame('@dev', $rootRequire['kongono/application-graph-intelligence']);
        self::assertSame(
            'packages/application-graph-intelligence/tests/',
            $rootAutoloadDevPsr4['Kongono\\ApplicationGraphIntelligence\\Tests\\'],
        );
        self::assertContains(
            'composer --working-dir=packages/application-graph-intelligence validate --no-check-publish',
            $validatePackages,
        );
        foreach (['.php-cs-fixer.dist.php', 'phpstan.neon.dist', 'phpunit.xml.dist'] as $configuration) {
            $contents = (string) file_get_contents($root . '/' . $configuration);
            self::assertSame(1, substr_count($contents, 'packages/application-graph-intelligence/src'));
            self::assertSame(1, substr_count($contents, 'packages/application-graph-intelligence/tests'));
        }

        $packageManifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($packageManifests);
        self::assertCount(75, $packageManifests);
        $edges = [];
        $localEdgeCount = 0;
        foreach ($packageManifests as $manifestPath) {
            $candidate = json_decode((string) file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($candidate);
            self::assertIsString($candidate['name']);
            self::assertIsArray($candidate['require']);
            foreach (array_keys($candidate['require']) as $dependency) {
                if (str_starts_with($dependency, 'kongono/')) {
                    ++$localEdgeCount;
                    if ('kongono/application-graph-intelligence' === $candidate['name']) {
                        $edges[] = $dependency;
                    }
                }
            }
            if (!in_array($candidate['name'], ['kongono/agent-inspection', 'kongono/application-graph-intelligence', 'kongono/model-invocation-inspection'], true)) {
                self::assertArrayNotHasKey('kongono/application-graph-intelligence', $candidate['require']);
            }
        }
        self::assertSame([
            'kongono/api-metadata',
            'kongono/application-graph-artifact',
            'kongono/application-graph-contract',
            'kongono/diagnostics',
        ], $edges);
        self::assertSame(289, $localEdgeCount);

        $deriver = new ReflectionClass('Kongono\\ApplicationGraphIntelligence\\Derivation\\ApplicationGraphIntelligenceDeriver');
        $verifier = new ReflectionClass('Kongono\\ApplicationGraphIntelligence\\Verification\\ApplicationGraphIntelligenceVerifier');
        self::assertSame(['derive'], $this->declaredPublicMethodNames($deriver));
        self::assertSame(['verify'], $this->declaredPublicMethodNames($verifier));
        foreach ($sourceFiles as $sourceFile) {
            $contents = (string) file_get_contents($package . '/src/' . $sourceFile);
            self::assertDoesNotMatchRegularExpression('/\b(?:fopen|fread|file_get_contents|hash_file|realpath|stat|lstat|is_file|is_dir|is_link|getenv|exec|system|shell_exec|proc_open|curl_init|ReflectionClass|eval)\s*\(/', $contents);
            self::assertDoesNotMatchRegularExpression('/Kongono\\\\(?:Chie|Hantei|Oshie|Knowledge|Console|Kernel|Tracing|Metrics|Security|Database|Persistence)/', $contents);
        }
    }

    public function testPhase11ExternalApplicationGraphAcceptanceRemainsRootOwnedAndBounded(): void
    {
        $root = dirname(__DIR__, 2);
        $external = $root . '/tests/External/ApplicationGraph';
        $files = array_map(
            static fn(string $path): string => str_replace('\\', '/', substr($path, strlen($external) + 1)),
            $this->recursivePhpFiles($external),
        );
        self::assertSame([
            'Phase11ApplicationGraphAcceptance.php',
            'ProcessResult.php',
            'TemporaryApplicationGraphWorkspace.php',
            'run.php',
        ], $files);

        $manifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertIsArray($manifest['scripts']);
        self::assertSame('@php tests/External/ApplicationGraph/run.php', $manifest['scripts']['acceptance:phase11']);

        $runner = (string) file_get_contents($external . '/Phase11ApplicationGraphAcceptance.php');
        $workspace = (string) file_get_contents($external . '/TemporaryApplicationGraphWorkspace.php');
        self::assertStringContainsString("'profile' => 'kongono-phase11-application-graph'", $runner);
        self::assertStringContainsString('phase11_acceptance_failed', $runner);
        self::assertStringContainsString("'kongono phase11 graph-'", $workspace);
        self::assertStringContainsString('finally', $runner);
        foreach (['curl_init(', 'fsockopen(', 'stream_socket_client(', 'shell_exec(', 'eval('] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $runner . $workspace);
        }

        $sourceDirectories = glob($root . '/packages/*/src');
        self::assertIsArray($sourceDirectories);
        foreach ($sourceDirectories as $sourceDirectory) {
            $contents = implode('', array_map(static fn(string $path): string => (string) file_get_contents($path), $this->recursivePhpFiles($sourceDirectory)));
            self::assertStringNotContainsString('Kongono\\Tests\\External\\ApplicationGraph', $contents);
        }
    }

    public function testPhase12ExternalExtensionPlatformAcceptanceRemainsRootOwnedAndBounded(): void
    {
        $root = dirname(__DIR__, 2);
        $external = $root . '/tests/External/ExtensionPlatform';
        $files = array_map(
            static fn(string $path): string => str_replace('\\', '/', substr($path, strlen($external) + 1)),
            $this->recursivePhpFiles($external),
        );
        self::assertSame([
            'Phase12ExtensionPlatformAcceptance.php',
            'ProcessResult.php',
            'TemporaryExtensionPlatformWorkspace.php',
            'run.php',
        ], $files);

        $manifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertIsArray($manifest['scripts']);
        self::assertSame('@php tests/External/ExtensionPlatform/run.php', $manifest['scripts']['acceptance:phase12']);

        $runner = (string) file_get_contents($external . '/Phase12ExtensionPlatformAcceptance.php');
        $workspace = (string) file_get_contents($external . '/TemporaryExtensionPlatformWorkspace.php');
        self::assertStringContainsString("'profile' => 'kongono-phase12-extension-platform'", $runner);
        self::assertStringContainsString('phase12_acceptance_failed', $runner);
        self::assertStringContainsString('kongono phase12 extension-', $workspace);
        self::assertStringContainsString('finally', $runner);
        foreach (['curl_init(', 'fsockopen(', 'stream_socket_client(', 'shell_exec(', 'eval('] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $runner . $workspace);
        }

        $sourceDirectories = glob($root . '/packages/*/src');
        self::assertIsArray($sourceDirectories);
        foreach ($sourceDirectories as $sourceDirectory) {
            $contents = implode('', array_map(static fn(string $path): string => (string) file_get_contents($path), $this->recursivePhpFiles($sourceDirectory)));
            self::assertStringNotContainsString('Kongono\\Tests\\External\\ExtensionPlatform', $contents);
        }
    }

    public function testModelInvocationContractPackageBoundaryAndRootIntegrationAreExact(): void
    {
        $root = dirname(__DIR__, 2);
        $package = $root . '/packages/model-invocation-contract';
        $manifest = json_decode((string) file_get_contents($package . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame([
            'name' => 'kongono/model-invocation-contract',
            'description' => 'Neutral versioned model-invocation contracts for Kongono Framework.',
            'type' => 'library',
            'require' => ['kongono/api-metadata' => '*', 'kongono/diagnostics' => '*', 'php' => '>=8.5.0 <8.6.0'],
            'require-dev' => ['phpunit/phpunit' => '^13.0'],
            'autoload' => ['psr-4' => ['Kongono\\ModelInvocationContract\\' => 'src/']],
            'autoload-dev' => ['psr-4' => ['Kongono\\ModelInvocationContract\\Tests\\' => 'tests/']],
        ], $manifest);
        self::assertCount(41, $this->recursivePhpFiles($package . '/src'));
        self::assertCount(17, $this->recursivePhpFiles($package . '/tests'));

        $rootManifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($rootManifest);
        self::assertIsArray($rootManifest['require']);
        self::assertIsArray($rootManifest['autoload-dev']);
        self::assertIsArray($rootManifest['autoload-dev']['psr-4']);
        self::assertIsArray($rootManifest['scripts']);
        self::assertIsArray($rootManifest['scripts']['validate:packages']);
        self::assertSame('@dev', $rootManifest['require']['kongono/model-invocation-contract']);
        self::assertSame('packages/model-invocation-contract/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\ModelInvocationContract\\Tests\\']);
        self::assertSame(1, count(array_keys($rootManifest['scripts']['validate:packages'], 'composer --working-dir=packages/model-invocation-contract validate --no-check-publish', true)));

        $manifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($manifests);
        foreach ($manifests as $path) {
            $candidate = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($candidate);
            self::assertIsString($candidate['name']);
            self::assertIsArray($candidate['require']);
            if (!in_array($candidate['name'], ['kongono/model-invocation-agent-bridge', 'kongono/model-invocation-application', 'kongono/model-invocation-contract', 'kongono/model-invocation-build', 'kongono/model-invocation-inspection', 'kongono/model-invocation-runtime'], true)) {
                self::assertArrayNotHasKey('kongono/model-invocation-contract', $candidate['require']);
            }
        }

        $phpstan = (string) file_get_contents($root . '/phpstan.neon.dist');
        self::assertSame(1, substr_count($phpstan, 'packages/model-invocation-contract/src'));
        self::assertSame(1, substr_count($phpstan, 'packages/model-invocation-contract/tests'));
    }

    public function testModelInvocationBuildPackageBoundaryAndRootIntegrationAreExact(): void
    {
        $root = dirname(__DIR__, 2);
        $package = $root . '/packages/model-invocation-build';
        $manifest = json_decode((string) file_get_contents($package . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame([
            'name' => 'kongono/model-invocation-build',
            'description' => 'Deterministic invocation-plan artifact build and publication for Kongono model invocation.',
            'type' => 'library',
            'require' => [
                'php' => '>=8.5.0 <8.6.0',
                'kongono/api-metadata' => '*',
                'kongono/diagnostics' => '*',
                'kongono/model-invocation-contract' => '*',
            ],
            'require-dev' => ['phpunit/phpunit' => '^13.0'],
            'autoload' => ['psr-4' => ['Kongono\\ModelInvocationBuild\\' => 'src/']],
            'autoload-dev' => ['psr-4' => ['Kongono\\ModelInvocationBuild\\Tests\\' => 'tests/']],
        ], $manifest);
        self::assertCount(19, $this->recursivePhpFiles($package . '/src'));
        self::assertCount(14, $this->recursivePhpFiles($package . '/tests'));

        $rootManifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($rootManifest);
        self::assertIsArray($rootManifest['require']);
        self::assertIsArray($rootManifest['autoload-dev']);
        self::assertIsArray($rootManifest['autoload-dev']['psr-4']);
        self::assertIsArray($rootManifest['scripts']);
        self::assertIsArray($rootManifest['scripts']['validate:packages']);
        self::assertSame('@dev', $rootManifest['require']['kongono/model-invocation-build']);
        self::assertSame('packages/model-invocation-build/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\ModelInvocationBuild\\Tests\\']);
        self::assertSame(1, count(array_keys($rootManifest['scripts']['validate:packages'], 'composer --working-dir=packages/model-invocation-build validate --no-check-publish', true)));

        $manifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($manifests);
        foreach ($manifests as $path) {
            $candidate = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($candidate);
            self::assertIsString($candidate['name']);
            self::assertIsArray($candidate['require']);
            if (!in_array($candidate['name'], ['kongono/model-invocation-application', 'kongono/model-invocation-build', 'kongono/model-invocation-inspection', 'kongono/model-invocation-runtime'], true)) {
                self::assertArrayNotHasKey('kongono/model-invocation-build', $candidate['require']);
            }
        }

        $phpstan = (string) file_get_contents($root . '/phpstan.neon.dist');
        self::assertSame(1, substr_count($phpstan, 'packages/model-invocation-build/src'));
        self::assertSame(1, substr_count($phpstan, 'packages/model-invocation-build/tests'));
    }

    public function testModelInvocationAgentBridgeBoundaryAndRootIntegrationAreExact(): void
    {
        $root = dirname(__DIR__, 2);
        $package = $root . '/packages/model-invocation-agent-bridge';
        $manifest = json_decode((string) file_get_contents($package . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame([
            'name' => 'kongono/model-invocation-agent-bridge',
            'description' => 'Inert bridge from normalized model results to Kongono Agent proposal evidence.',
            'type' => 'library',
            'require' => [
                'php' => '>=8.5.0 <8.6.0',
                'kongono/agent-principal-contract' => '*',
                'kongono/agent-runtime' => '*',
                'kongono/api-metadata' => '*',
                'kongono/diagnostics' => '*',
                'kongono/model-invocation-contract' => '*',
                'kongono/model-invocation-runtime' => '*',
            ],
            'require-dev' => ['phpunit/phpunit' => '^13.0'],
            'autoload' => ['psr-4' => ['Kongono\\ModelInvocationAgentBridge\\' => 'src/']],
            'autoload-dev' => ['psr-4' => ['Kongono\\ModelInvocationAgentBridge\\Tests\\' => 'tests/']],
        ], $manifest);
        self::assertCount(13, $this->recursivePhpFiles($package . '/src'));
        self::assertCount(9, $this->recursivePhpFiles($package . '/tests'));

        $rootManifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($rootManifest);
        $rootRequire = $rootManifest['require'] ?? null;
        self::assertIsArray($rootRequire);
        self::assertSame('@dev', $rootRequire['kongono/model-invocation-agent-bridge']);
        $rootAutoloadDev = $rootManifest['autoload-dev'] ?? null;
        self::assertIsArray($rootAutoloadDev);
        $rootAutoloadDevPsr4 = $rootAutoloadDev['psr-4'] ?? null;
        self::assertIsArray($rootAutoloadDevPsr4);
        self::assertSame('packages/model-invocation-agent-bridge/tests/', $rootAutoloadDevPsr4['Kongono\\ModelInvocationAgentBridge\\Tests\\']);
        $rootScripts = $rootManifest['scripts'] ?? null;
        self::assertIsArray($rootScripts);
        $validatePackages = $rootScripts['validate:packages'] ?? null;
        self::assertIsArray($validatePackages);
        self::assertSame(1, count(array_keys($validatePackages, 'composer --working-dir=packages/model-invocation-agent-bridge validate --no-check-publish', true)));
        $rootRepositories = $rootManifest['repositories'] ?? null;
        self::assertIsArray($rootRepositories);
        $bridgeRepositoryCount = 0;
        foreach ($rootRepositories as $repository) {
            if (is_array($repository)
                && 'path' === ($repository['type'] ?? null)
                && 'packages/model-invocation-agent-bridge' === ($repository['url'] ?? null)
            ) {
                ++$bridgeRepositoryCount;
            }
        }
        self::assertSame(1, $bridgeRepositoryCount);

        $statuses = [
            'Kongono\\ModelInvocationAgentBridge\\Bridge\\ModelResultProposalBridge' => ApiStatus::Experimental,
            'Kongono\\ModelInvocationAgentBridge\\Mapping\\ActionProposalMapping' => ApiStatus::Experimental,
            'Kongono\\ModelInvocationAgentBridge\\Mapping\\ProposalUsageSelector' => ApiStatus::Experimental,
            'Kongono\\ModelInvocationAgentBridge\\Exception\\ExceptionInterface' => ApiStatus::Public,
            'Kongono\\ModelInvocationAgentBridge\\Exception\\ProposalBridgeRejectedException' => ApiStatus::Experimental,
            'Kongono\\ModelInvocationAgentBridge\\Diagnostic\\ModelInvocationAgentBridgeDiagnostic' => ApiStatus::Internal,
            'Kongono\\ModelInvocationAgentBridge\\Exception\\ModelInvocationAgentBridgeException' => ApiStatus::Internal,
            'Kongono\\ModelInvocationAgentBridge\\Exception\\InvalidModelInvocationAgentBridgeException' => ApiStatus::Internal,
            'Kongono\\ModelInvocationAgentBridge\\Internal\\BridgeGuard' => ApiStatus::Internal,
            'Kongono\\ModelInvocationAgentBridge\\Internal\\CanonicalEvidence' => ApiStatus::Internal,
            'Kongono\\ModelInvocationAgentBridge\\Internal\\ProposalFactory' => ApiStatus::Internal,
            'Kongono\\ModelInvocationAgentBridge\\Internal\\UsageProjector' => ApiStatus::Internal,
            'Kongono\\ModelInvocationAgentBridge\\Internal\\VisibilityProjector' => ApiStatus::Internal,
        ];
        foreach ($statuses as $type => $status) {
            $attributes = (new ReflectionClass($type))->getAttributes(Api::class);
            self::assertCount(1, $attributes, $type);
            self::assertSame($status, $attributes[0]->newInstance()->status, $type);
        }

        $packageManifestPaths = glob($root . '/packages/*/composer.json');
        if (false === $packageManifestPaths) {
            throw new RuntimeException('Package manifests could not be enumerated.');
        }
        foreach ($packageManifestPaths as $path) {
            $candidate = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($candidate);
            if ('kongono/model-invocation-agent-bridge' !== $candidate['name']) {
                $candidateRequire = $candidate['require'] ?? null;
                self::assertIsArray($candidateRequire);
                self::assertArrayNotHasKey('kongono/model-invocation-agent-bridge', $candidateRequire);
            }
        }

        foreach (['phpstan.neon.dist', 'phpunit.xml.dist', '.php-cs-fixer.dist.php'] as $toolingPath) {
            $tooling = (string) file_get_contents($root . '/' . $toolingPath);
            self::assertSame(1, substr_count($tooling, 'packages/model-invocation-agent-bridge/src'), $toolingPath);
            self::assertSame(1, substr_count($tooling, 'packages/model-invocation-agent-bridge/tests'), $toolingPath);
        }
    }

    public function testModelInvocationApplicationBoundaryAndRootIntegrationAreExact(): void
    {
        $root = dirname(__DIR__, 2);
        $package = $root . '/packages/model-invocation-application';
        $manifest = json_decode((string) file_get_contents($package . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame([
            'name' => 'kongono/model-invocation-application',
            'description' => 'Exact-current single-profile application workflow for Kongono model invocation.',
            'type' => 'library',
            'require' => [
                'php' => '>=8.5.0 <8.6.0',
                'kongono/api-metadata' => '*',
                'kongono/diagnostics' => '*',
                'kongono/model-invocation-contract' => '*',
                'kongono/model-invocation-build' => '*',
                'kongono/model-invocation-runtime' => '*',
            ],
            'require-dev' => ['phpunit/phpunit' => '^13.0'],
            'autoload' => ['psr-4' => ['Kongono\\ModelInvocationApplication\\' => 'src/']],
            'autoload-dev' => ['psr-4' => ['Kongono\\ModelInvocationApplication\\Tests\\' => 'tests/']],
        ], $manifest);
        self::assertCount(29, $this->recursivePhpFiles($package . '/src'));
        self::assertCount(11, $this->recursivePhpFiles($package . '/tests'));

        $rootManifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($rootManifest);
        self::assertIsArray($rootManifest['require']);
        self::assertIsArray($rootManifest['autoload-dev']);
        self::assertIsArray($rootManifest['autoload-dev']['psr-4']);
        self::assertIsArray($rootManifest['scripts']);
        self::assertIsArray($rootManifest['scripts']['validate:packages']);
        self::assertSame('@dev', $rootManifest['require']['kongono/model-invocation-application']);
        self::assertSame('packages/model-invocation-application/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\ModelInvocationApplication\\Tests\\']);
        self::assertSame(1, count(array_keys($rootManifest['scripts']['validate:packages'], 'composer --working-dir=packages/model-invocation-application validate --no-check-publish', true)));
        self::assertIsArray($rootManifest['repositories']);
        self::assertSame(1, count(array_filter(
            $rootManifest['repositories'],
            static fn(mixed $repository): bool => is_array($repository)
                && 'path' === ($repository['type'] ?? null)
                && 'packages/model-invocation-application' === ($repository['url'] ?? null),
        )));

        $packageStatuses = array_filter(
            self::EXPECTED_STATUS,
            static fn(string $type): bool => str_starts_with($type, 'Kongono\\ModelInvocationApplication\\'),
            ARRAY_FILTER_USE_KEY,
        );
        self::assertCount(29, $packageStatuses);
        foreach ($packageStatuses as $type => $status) {
            $attributes = (new ReflectionClass($this->autoloadedClassLike($type)))->getAttributes(Api::class);
            self::assertCount(1, $attributes, $type);
            self::assertSame($status, $attributes[0]->newInstance()->status, $type);
        }
        self::assertSame([
            'KGN-MODEL-APPLICATION-1430',
            'KGN-MODEL-APPLICATION-1431',
            'KGN-MODEL-APPLICATION-1432',
            'KGN-MODEL-APPLICATION-1433',
            'KGN-MODEL-APPLICATION-1434',
            'KGN-MODEL-APPLICATION-1435',
            'KGN-MODEL-APPLICATION-1436',
        ], array_column(('Kongono\\ModelInvocationApplication\\Diagnostic\\ModelInvocationApplicationDiagnostic')::cases(), 'value'));

        $manifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($manifests);
        self::assertCount(75, $manifests);
        $edges = 0;
        foreach ($manifests as $path) {
            $candidate = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($candidate);
            self::assertIsArray($candidate['require']);
            foreach ($candidate['require'] as $dependency => $_constraint) {
                if (str_starts_with((string) $dependency, 'kongono/')) {
                    ++$edges;
                }
            }
            if (!in_array($candidate['name'], ['kongono/model-invocation-application', 'kongono/model-invocation-inspection'], true)) {
                self::assertArrayNotHasKey('kongono/model-invocation-application', $candidate['require']);
            }
        }
        self::assertSame(289, $edges);

        foreach (['phpstan.neon.dist', 'phpunit.xml.dist', '.php-cs-fixer.dist.php'] as $toolingPath) {
            $tooling = (string) file_get_contents($root . '/' . $toolingPath);
            self::assertSame(1, substr_count($tooling, 'packages/model-invocation-application/src'), $toolingPath);
            self::assertSame(1, substr_count($tooling, 'packages/model-invocation-application/tests'), $toolingPath);
        }
        $source = implode('', array_map(static fn(string $path): string => (string) file_get_contents($path), $this->recursivePhpFiles($package . '/src')));
        foreach (['ModelInvocationAgentBridge', 'AgentProposalPort', 'AgentCapabilityPort', 'getenv(', 'putenv(', '$_ENV', '$_SERVER', '$GLOBALS', 'PDO', 'curl_', 'scandir(', 'glob(', 'shell_exec(', 'proc_open(', 'Fiber', 'Queue', 'Scheduler', 'Worker', 'Retry', 'Fallback', 'Registry', 'Credential', 'ApplicationGraph', 'Chie', 'Hantei', 'Oshie'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source, $forbidden);
        }
    }

    public function testModelInvocationInspectionBoundaryAndRootIntegrationAreExact(): void
    {
        $root = dirname(__DIR__, 2);
        $package = $root . '/packages/model-invocation-inspection';
        $manifest = json_decode((string) file_get_contents($package . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame([
            'name' => 'kongono/model-invocation-inspection',
            'description' => 'Visibility-safe model-invocation inspection and bounded cross-system evidence.',
            'type' => 'library',
            'require' => [
                'php' => '>=8.5.0 <8.6.0',
                'kongono/api-metadata' => '*',
                'kongono/application-graph-intelligence' => '*',
                'kongono/diagnostics' => '*',
                'kongono/model-invocation-application' => '*',
                'kongono/model-invocation-build' => '*',
                'kongono/model-invocation-contract' => '*',
                'kongono/model-invocation-runtime' => '*',
                'kongono/observability' => '*',
                'kongono/security-audit' => '*',
            ],
            'require-dev' => ['phpunit/phpunit' => '^13.0'],
            'autoload' => ['psr-4' => ['Kongono\\ModelInvocationInspection\\' => 'src/']],
            'autoload-dev' => ['psr-4' => ['Kongono\\ModelInvocationInspection\\Tests\\' => 'tests/']],
        ], $manifest);
        self::assertCount(22, $this->recursivePhpFiles($package . '/src'));
        self::assertCount(6, $this->recursivePhpFiles($package . '/tests'));

        $rootManifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($rootManifest);
        self::assertIsArray($rootManifest['require']);
        self::assertIsArray($rootManifest['autoload-dev']);
        self::assertIsArray($rootManifest['autoload-dev']['psr-4']);
        self::assertIsArray($rootManifest['scripts']);
        self::assertIsArray($rootManifest['scripts']['validate:packages']);
        self::assertIsArray($rootManifest['repositories']);
        self::assertSame('@dev', $rootManifest['require']['kongono/model-invocation-inspection']);
        self::assertSame('packages/model-invocation-inspection/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\ModelInvocationInspection\\Tests\\']);
        self::assertSame(1, count(array_keys($rootManifest['scripts']['validate:packages'], 'composer --working-dir=packages/model-invocation-inspection validate --no-check-publish', true)));
        self::assertSame(1, count(array_filter(
            $rootManifest['repositories'],
            static fn(mixed $repository): bool => is_array($repository)
                && 'path' === ($repository['type'] ?? null)
                && 'packages/model-invocation-inspection' === ($repository['url'] ?? null),
        )));

        $packageStatuses = array_filter(
            self::EXPECTED_STATUS,
            static fn(string $type): bool => str_starts_with($type, 'Kongono\\ModelInvocationInspection\\'),
            ARRAY_FILTER_USE_KEY,
        );
        self::assertCount(22, $packageStatuses);
        foreach ($packageStatuses as $type => $status) {
            $attributes = (new ReflectionClass($this->autoloadedClassLike($type)))->getAttributes(Api::class);
            self::assertCount(1, $attributes, $type);
            self::assertSame($status, $attributes[0]->newInstance()->status, $type);
        }
        self::assertSame([
            'KGN-MODEL-INSPECTION-1437',
            'KGN-MODEL-INSPECTION-1438',
            'KGN-MODEL-INSPECTION-1439',
            'KGN-MODEL-INSPECTION-1440',
            'KGN-MODEL-INSPECTION-1441',
        ], array_column(('Kongono\\ModelInvocationInspection\\Diagnostic\\ModelInvocationInspectionDiagnostic')::cases(), 'value'));

        $manifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($manifests);
        self::assertCount(75, $manifests);
        $edges = 0;
        foreach ($manifests as $path) {
            $candidate = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($candidate);
            self::assertIsArray($candidate['require']);
            foreach ($candidate['require'] as $dependency => $_constraint) {
                if (str_starts_with((string) $dependency, 'kongono/')) {
                    ++$edges;
                }
            }
            if ('kongono/model-invocation-inspection' !== $candidate['name']) {
                self::assertArrayNotHasKey('kongono/model-invocation-inspection', $candidate['require']);
            }
        }
        self::assertSame(289, $edges);

        foreach (['phpstan.neon.dist', 'phpunit.xml.dist', '.php-cs-fixer.dist.php'] as $toolingPath) {
            $tooling = (string) file_get_contents($root . '/' . $toolingPath);
            self::assertSame(1, substr_count($tooling, 'packages/model-invocation-inspection/src'), $toolingPath);
            self::assertSame(1, substr_count($tooling, 'packages/model-invocation-inspection/tests'), $toolingPath);
        }
        $source = implode('', array_map(static fn(string $path): string => (string) file_get_contents($path), $this->recursivePhpFiles($package . '/src')));
        foreach (['ModelInvocationAgentBridge', 'AgentProposalPort', 'AgentCapabilityPort', 'file_get_contents(', 'file_put_contents(', 'fopen(', 'getenv(', 'putenv(', '$_ENV', '$_SERVER', '$GLOBALS', 'PDO', 'curl_', 'scandir(', 'glob(', 'shell_exec(', 'proc_open(', 'Reflection', 'Queue', 'Scheduler', 'Worker', 'Retry', 'Fallback', 'Registry', 'Credential'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source, $forbidden);
        }
    }

    private static function assertMarkersDistinct(string $left, string $right): void
    {
        self::assertFalse(is_a($left, $right, true));
        self::assertFalse(is_a($right, $left, true));
    }
}
