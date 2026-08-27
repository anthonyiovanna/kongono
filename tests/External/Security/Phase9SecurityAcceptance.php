<?php

declare(strict_types=1);

namespace Kongono\Tests\External\Security;

use Closure;
use JsonException;
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
use Kongono\Authorization\AuthorizationEngine;
use Kongono\Authorization\AuthorizationEnvironmentEvidence;
use Kongono\Authorization\AuthorizationEnvironmentProfile;
use Kongono\Authorization\AuthorizationEvaluator;
use Kongono\Authorization\AuthorizationPolicyName;
use Kongono\Authorization\AuthorizationPolicyProvenance;
use Kongono\Authorization\AuthorizationPolicyReference;
use Kongono\Authorization\AuthorizationPolicyVersion;
use Kongono\Authorization\AuthorizationRequest;
use Kongono\Composition\Exception\CompositionLifecycleException;
use Kongono\Composition\Runtime\GeneratedOperationBoundary;
use Kongono\Composition\Runtime\GeneratedOperationInputApplicationBoundary;
use Kongono\Composition\Runtime\GeneratedOperationInputs;
use Kongono\Composition\Runtime\LoadedGeneratedWiring;
use Kongono\Http\Message\Message\ServerRequest;
use Kongono\IdentityAccess\Action;
use Kongono\IdentityAccess\AuthenticationOutcomeKind;
use Kongono\IdentityAccess\AuthorizationDecision;
use Kongono\IdentityAccess\PrincipalId;
use Kongono\IdentityAccess\PrincipalKind;
use Kongono\IdentityAccess\PrincipalReference;
use Kongono\IdentityAccess\ResourceId;
use Kongono\IdentityAccess\ResourceReference;
use Kongono\IdentityAccess\ResourceType;
use Kongono\IdentityAccess\SecurityContext;
use Kongono\IdentityAccess\SecurityContextState;
use Kongono\SecurityApplication\BrowserSessionProtectedApplication;
use Kongono\SecurityEnforcement\Exception\ProtectedCapabilityEnforcementException;
use Kongono\SecurityEnforcement\ProtectedCapability;
use Kongono\SecurityEnforcement\ProtectedCapabilityEnforcer;
use Kongono\SecurityHttp\BrowserSessionEvidence;
use Kongono\SecurityHttp\BrowserSessionProfile;
use Kongono\SecurityHttp\BrowserSessionSecurityGateway;
use Kongono\SecurityHttp\Exception\BrowserSessionAuthenticationException;
use Kongono\SecurityHttp\Exception\BrowserSessionRequestException;
use Kongono\SecurityHttp\TrustedOrigin;
use Kongono\SecurityPolicyBuild\Exception\SecurityPolicyWorkflowException;
use Kongono\SecurityPolicyBuild\Workflow\CurrentSecurityPolicy;
use Kongono\SecurityPolicyBuild\Workflow\SecurityPolicyBuildWorkflow;
use Kongono\SecurityRuntime\Exception\SecurityRuntimeLifecycleException;
use Kongono\SecurityRuntime\GeneratedSecurityApplication;
use Kongono\SecurityRuntime\SecurityOperationRoot;
use LogicException;
use ReflectionMethod;
use RuntimeException;
use Throwable;

/** Executes the closed real-process Phase 9 identity-and-access acceptance profile. */
final class Phase9SecurityAcceptance
{
    private const int FAILURE = 1;
    private const int MAX_STREAM_BYTES = 1_048_576;
    private const int PROCESS_TIMEOUT_SECONDS = 600;
    private const string FAILURE_BYTES = "phase9_acceptance_failed\n";
    private const string SESSION = 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA';
    private const string CSRF = 'BBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBB';
    private const string CREDENTIAL = 'Phase9RawCredential-DoNotDisclose';
    private const string PRINCIPAL = 'Phase9PrivatePrincipal-DoNotDisclose';
    private const string POLICY_PAYLOAD = 'Phase9PrivatePolicyPayload-DoNotDisclose';
    private const string SENTINEL = 'Phase9PrivateSentinel-DoNotDisclose';
    private const string ENVIRONMENT_SECRET = 'Phase9EnvironmentSecret-DoNotDisclose';

    private string $repositoryRoot = '';

    /** @var list<string> */
    private array $composerCommand = [];

    private int $caseCount = 0;

    /** @var list<string> */
    private array $observedOutput = [];

    /**
     * @param array<array-key, mixed> $arguments
     * @param resource                $stdout
     * @param resource                $stderr
     */
    public function run(array $arguments, mixed $stdout, mixed $stderr): int
    {
        $temporary = null;
        $before = null;
        $failed = false;

        try {
            $this->require(array_is_list($arguments) && [] === $arguments, 'invalid_invocation');
            $this->require(is_resource($stdout) && is_resource($stderr), 'invalid_output_ports');
            $root = realpath(dirname(__DIR__, 3));
            if (false === $root || is_link($root) || !is_dir($root)) {
                throw new RuntimeException('repository_root_invalid');
            }
            $this->repositoryRoot = self::normalize($root);
            $this->composerCommand = $this->resolveComposerCommand();
            $before = $this->repositoryStatus();
            $temporary = TemporarySecurityApplication::create($this->repositoryRoot);
            $this->accept($temporary);
        } catch (Throwable) {
            $failed = true;
        } finally {
            if (null !== $temporary) {
                try {
                    $root = $temporary->root();
                    $temporary->cleanup();
                    $this->require(!file_exists($root) && !is_link($root), 'temporary_residue');
                    $this->caseCompleted();
                } catch (Throwable) {
                    $failed = true;
                }
            }
            if (null !== $before) {
                try {
                    $this->require($before === $this->repositoryStatus(), 'repository_status_changed');
                    $this->caseCompleted();
                } catch (Throwable) {
                    $failed = true;
                }
            }
        }

        if ($failed || $this->caseCount < 1) {
            $this->write($stderr, self::FAILURE_BYTES);

            return self::FAILURE;
        }

        $success = json_encode([
            'status' => 'ok',
            'profile' => 'kongono-phase9-security',
            'case_count' => $this->caseCount,
            'cleanup' => 'complete',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";
        $this->write($stdout, $success);

        return 0;
    }

    private function accept(TemporarySecurityApplication $temporary): void
    {
        $applicationRoot = $temporary->applicationRoot();
        $composerHome = $temporary->root() . '/composer-home';
        $temporary->write($composerHome . '/cache/.keep', '');
        $environment = [
            'COMPOSER_CACHE_DIR' => $composerHome . '/cache',
            'COMPOSER_DISABLE_NETWORK' => '1',
            'COMPOSER_HOME' => $composerHome,
            'KONGONO_PHASE9_UNRELATED_SECRET' => self::ENVIRONMENT_SECRET,
        ];
        $install = $this->process([
            ...$this->composerCommand,
            'install',
            '--no-interaction',
            '--no-ansi',
            '--no-plugins',
            '--no-progress',
        ], $applicationRoot, $environment);
        $this->expectCompleted($install);
        $this->observe($install);
        $this->require(is_file($applicationRoot . '/vendor/autoload.php'), 'application_install_missing');
        $this->require(!$this->containsLink($applicationRoot . '/vendor'), 'application_install_linked');
        $this->caseCompleted();

        $lockIdentity = hash('sha256', $temporary->read($temporary->composerLock()));
        $build = $this->composerScript($applicationRoot, 'app:build', $environment);
        $this->expectSuccess($build);
        $this->observe($build);
        $identities = $this->buildIdentities($build->stdout());
        $this->require(3 === count($temporary->buildFiles()), 'build_output_count_invalid');
        $this->require($lockIdentity === hash('sha256', $temporary->read($temporary->composerLock())), 'application_lock_changed');
        $this->caseCompleted();

        $buildIdentity = $temporary->buildIdentity();
        $check = $this->composerScript($applicationRoot, 'app:check', $environment);
        $this->expectSuccess($check);
        $this->observe($check);
        $this->require($identities === $this->buildIdentities($check->stdout()), 'check_identity_mismatch');
        $this->require($buildIdentity === $temporary->buildIdentity(), 'check_wrote_output');
        $this->caseCompleted();

        $run = $this->composerScript($applicationRoot, 'app:run', $environment);
        $this->expectSuccess($run);
        $this->observe($run);
        $this->require("public-example-ok\ndeny-prevented-execution\n" === $run->stdout() && '' === $run->stderr(), 'application_run_invalid');
        $this->caseCompleted();

        $repeat = $this->composerScript($applicationRoot, 'app:check', $environment);
        $this->expectSuccess($repeat);
        $this->observe($repeat);
        $this->require($check->stdout() === $repeat->stdout() && $buildIdentity === $temporary->buildIdentity(), 'repeat_check_changed_state');
        $this->caseCompleted();

        $policy = (new SecurityPolicyBuildWorkflow())->check($applicationRoot, $temporary->buildDirectory());
        $this->acceptAuthorizationAndLifecycle($policy);
        $this->acceptBrowserTransport($policy);
        $this->acceptPolicyCurrentness($temporary, $policy);
        $this->acceptConsoleReadOnly($temporary);

        $phase8 = $this->process([...$this->composerCommand, '--no-interaction', '--no-ansi', 'acceptance:phase8'], $this->repositoryRoot, [
            'COMPOSER_DISABLE_NETWORK' => '1',
            'KONGONO_PHASE9_UNRELATED_SECRET' => self::ENVIRONMENT_SECRET,
        ]);
        $this->expectSuccess($phase8);
        $this->observe($phase8);
        $phase8Document = $this->canonicalJsonLine($phase8->stdout());
        $this->require('kongono-phase8-cli' === ($phase8Document['profile'] ?? null), 'phase8_profile_invalid');
        $this->caseCompleted();

        $allOutput = implode('', $this->observedOutput);
        foreach ([
            self::SESSION,
            self::CSRF,
            self::CREDENTIAL,
            self::PRINCIPAL,
            self::POLICY_PAYLOAD,
            self::SENTINEL,
            self::ENVIRONMENT_SECRET,
            $temporary->root(),
            str_replace('/', '\\', $temporary->root()),
            get_current_user(),
            false !== gethostname() ? gethostname() : 'phase9-hostname-unavailable',
            'Stack trace',
            'stack trace',
            "\033",
        ] as $protected) {
            $this->require('' === $protected || !str_contains($allOutput, $protected), 'protected_output_disclosed');
        }
        $this->caseCompleted();
    }

    private function acceptAuthorizationAndLifecycle(CurrentSecurityPolicy $policy): void
    {
        AcceptanceApplicationAssembly::reset();
        $generated = $this->generatedApplication();
        $gateway = $this->gateway($generated, new AcceptanceVerifier(AuthenticationVerification::invalid()));
        $environment = new AcceptanceEnvironment('public.example.v1');

        $permitEvaluator = new AcceptanceEvaluator($policy->evidence()->reference(), AuthorizationDecision::Permit);
        $permitCapability = new AcceptanceCapability('permit-result');
        $permitted = new BrowserSessionProtectedApplication(
            $gateway,
            new AuthorizationEngine([$permitEvaluator]),
            new ProtectedCapabilityEnforcer(),
            $policy,
        );
        $this->require('permit-result' === $permitted->execute(
            new ServerRequest('GET', 'https://example.test/public'),
            new AuthenticationInstant(100),
            $environment,
            $permitCapability,
        ), 'permit_result_invalid');
        $this->require(1 === $permitEvaluator->evaluateCalls && 1 === $permitCapability->executeCalls, 'permit_count_invalid');
        $this->require(1 === AcceptanceOperationAssembly::$closeCalls, 'permit_cleanup_invalid');
        $this->caseCompleted();

        foreach ([AuthorizationDecision::Deny, AuthorizationDecision::Indeterminate] as $decision) {
            $evaluator = new AcceptanceEvaluator($policy->evidence()->reference(), $decision);
            $capability = new AcceptanceCapability('rejected-result');
            $application = new BrowserSessionProtectedApplication(
                $gateway,
                new AuthorizationEngine([$evaluator]),
                new ProtectedCapabilityEnforcer(),
                $policy,
            );
            try {
                $application->execute(
                    new ServerRequest('GET', 'https://example.test/public'),
                    new AuthenticationInstant(100),
                    $environment,
                    $capability,
                );
                throw new RuntimeException('rejected_decision_executed');
            } catch (ProtectedCapabilityEnforcementException) {
                $this->require(1 === $evaluator->evaluateCalls && 0 === $capability->executeCalls, 'rejected_decision_effect');
            }
            $this->caseCompleted();
        }

        $operation = $generated->beginAnonymousOperation();
        $context = $operation->securityContext();
        $capability = new AcceptanceCapability('mismatch-result');
        $mismatched = new AuthorizationPolicyReference(
            new AuthorizationPolicyName('different.policy'),
            new AuthorizationPolicyVersion('v1'),
            new AuthorizationPolicyProvenance('application'),
        );
        $evaluator = new AcceptanceEvaluator($policy->evidence()->reference(), AuthorizationDecision::Permit);
        $request = new AuthorizationRequest(
            $context,
            $capability->action(),
            $capability->resource(),
            $mismatched,
            $policy->evidence(),
            $environment,
        );
        try {
            (new ProtectedCapabilityEnforcer())->enforce($context, new AuthorizationEngine([$evaluator]), $request, $capability);
            throw new RuntimeException('policy_mismatch_executed');
        } catch (ProtectedCapabilityEnforcementException) {
            $this->require(0 === $evaluator->evaluateCalls && 0 === $capability->executeCalls, 'policy_mismatch_effect');
        } finally {
            $operation->close();
        }
        $this->caseCompleted();

        $orchestrator = new AuthenticationOrchestrator([new AcceptanceVerifier(AuthenticationVerification::invalid())]);
        $requirement = new AuthenticationRequirement(new AuthenticationStrength(1), 300);
        $now = new AuthenticationInstant(100);
        $this->require(AuthenticationOutcomeKind::Absent === $orchestrator->authenticate(new AuthenticationRequest([], $now, $requirement))->outcome()->kind(), 'absent_outcome_invalid');
        $this->require(AuthenticationOutcomeKind::Ambiguous === $orchestrator->authenticate(new AuthenticationRequest([
            new AcceptanceEvidence(),
            new AcceptanceEvidence(),
        ], $now, $requirement))->outcome()->kind(), 'ambiguous_outcome_invalid');
        $unsupported = new AuthenticationOrchestrator([new AcceptanceVerifier(AuthenticationVerification::invalid())]);
        $this->require(AuthenticationOutcomeKind::Unsupported === $unsupported->authenticate(new AuthenticationRequest([
            new AcceptanceEvidence('unsupported.v1'),
        ], $now, $requirement))->outcome()->kind(), 'unsupported_outcome_invalid');
        $this->caseCompleted();

        $authenticatedVerifier = new AcceptanceVerifier(self::authenticatedVerification());
        $authenticatedGateway = $this->gateway($generated, $authenticatedVerifier);
        $deniedCapability = new AcceptanceCapability('authenticated-denied');
        $denied = new BrowserSessionProtectedApplication(
            $authenticatedGateway,
            new AuthorizationEngine([new AcceptanceEvaluator($policy->evidence()->reference(), AuthorizationDecision::Deny)]),
            new ProtectedCapabilityEnforcer(),
            $policy,
        );
        try {
            $denied->execute($this->cookieRequest('GET'), $now, $environment, $deniedCapability);
            throw new RuntimeException('authentication_granted_authority');
        } catch (ProtectedCapabilityEnforcementException) {
            $this->require(0 === $deniedCapability->executeCalls, 'authenticated_denied_effect');
        }
        $this->caseCompleted();

        $first = $generated->beginAnonymousOperation();
        $second = $generated->beginAuthenticatedOperation(self::authenticatedVerification());
        $this->require($first->securityContext() !== $second->securityContext(), 'context_identity_shared');
        $this->require(SecurityContextState::Anonymous === $first->securityContext()->state(), 'anonymous_context_invalid');
        $this->require(SecurityContextState::Authenticated === $second->securityContext()->state(), 'authenticated_context_invalid');
        $first->close();
        try {
            $first->securityContext();
            throw new RuntimeException('closed_context_retained');
        } catch (SecurityRuntimeLifecycleException) {
        }
        $second->close();
        $this->caseCompleted();

        $primary = new RuntimeException(self::SENTINEL);
        $cleanup = new RuntimeException(self::CREDENTIAL);
        AcceptanceOperationAssembly::$cleanupFailure = $cleanup;
        $failing = $generated->beginAnonymousOperation();
        try {
            $failing->close($primary);
            throw new RuntimeException('combined_failure_missing');
        } catch (CompositionLifecycleException $failure) {
            $this->require($primary === $failure->primaryFailure(), 'primary_failure_identity_lost');
            $this->require([$cleanup] === $failure->cleanupFailures(), 'cleanup_failure_identity_lost');
            $this->require(['retire', 'release'] === array_slice(AcceptanceOperationAssembly::$events, -2), 'cleanup_order_invalid');
        }
        AcceptanceOperationAssembly::$cleanupFailure = null;
        $this->caseCompleted();

        AcceptanceApplicationAssembly::$onBegin = static function (AcceptanceApplicationAssembly $assembly): void {
            try {
                $assembly->beginOperationWithInputs(GeneratedOperationInputs::of(SecurityContext::anonymous()));
                throw new RuntimeException('same_boundary_reentrancy_accepted');
            } catch (LogicException) {
            }
        };
        $outer = $generated->beginAnonymousOperation();
        $outer->close();
        AcceptanceApplicationAssembly::$onBegin = null;
        $later = $generated->beginAnonymousOperation();
        $later->close();
        $this->caseCompleted();

        $generated->shutdown();
        $this->require($generated->isRetired(), 'generated_application_not_retired');
        $this->caseCompleted();
    }

    private function acceptBrowserTransport(CurrentSecurityPolicy $policy): void
    {
        AcceptanceApplicationAssembly::reset();
        $generated = $this->generatedApplication();
        $verification = self::authenticatedVerification();
        $verifier = new AcceptanceVerifier($verification);
        $gateway = $this->gateway($generated, $verifier);

        $anonymous = $gateway->begin(new ServerRequest('GET', 'https://example.test/public'), new AuthenticationInstant(100));
        $this->require(SecurityContextState::Anonymous === $anonymous->securityContext()->state() && 0 === $verifier->beginCalls, 'anonymous_transport_invalid');
        $anonymous->close();
        $this->caseCompleted();

        $safe = $gateway->begin($this->cookieRequest('GET'), new AuthenticationInstant(100));
        $this->require(SecurityContextState::Authenticated === $safe->securityContext()->state(), 'safe_authentication_invalid');
        $safeEvidence = $verifier->lastEvidence;
        if (!$safeEvidence instanceof BrowserSessionEvidence) {
            throw new RuntimeException('session_evidence_missing');
        }
        $this->require(self::SESSION === $safeEvidence->sessionIdentifier()->sensitiveValue(), 'session_evidence_changed');
        $safe->close();
        $this->caseCompleted();

        $post = $gateway->begin($this->cookieRequest('POST', [
            'Origin' => 'https://example.test',
            BrowserSessionProfile::CSRF_HEADER_NAME => self::CSRF,
        ]), new AuthenticationInstant(100));
        $csrfEvidence = $verifier->lastEvidence;
        $this->require($csrfEvidence->requiresCsrfProof(), 'csrf_requirement_missing');
        $this->require(self::CSRF === $csrfEvidence->csrfProof()?->sensitiveValue(), 'csrf_evidence_changed');
        $post->close();
        $this->caseCompleted();

        $invalid = [
            new ServerRequest('GET', 'http://example.test/path', [], ['Cookie' => '__Host-kongono_session=' . self::SESSION]),
            new ServerRequest('GET', 'https://wrong.test/path', [], ['Cookie' => '__Host-kongono_session=' . self::SESSION]),
            new ServerRequest('GET', 'https://-bad.test/path', [], ['Cookie' => '__Host-kongono_session=' . self::SESSION]),
            $this->cookieRequest('POST'),
            $this->cookieRequest('POST', ['Origin' => 'https://wrong.test', BrowserSessionProfile::CSRF_HEADER_NAME => self::CSRF]),
            $this->cookieRequest('POST', ['Origin' => 'https://example.test']),
            $this->cookieRequest('POST', ['Origin' => 'https://example.test', BrowserSessionProfile::CSRF_HEADER_NAME => str_repeat('C', 42)]),
            new ServerRequest('GET', 'https://example.test/path', [], ['Cookie' => '__Host-kongono_session=' . str_repeat('A', 42)]),
            new ServerRequest('GET', 'https://example.test/path', [], ['Cookie' => '__Host-kongono_session=' . self::SESSION . '; __Host-kongono_session=' . self::SESSION]),
        ];
        foreach ($invalid as $request) {
            $before = $verifier->beginCalls;
            try {
                $gateway->begin($request, new AuthenticationInstant(100));
                throw new RuntimeException('invalid_transport_accepted');
            } catch (BrowserSessionRequestException) {
                $this->require($before === $verifier->beginCalls, 'transport_reached_verifier');
            }
        }
        $this->caseCompleted();

        foreach ([
            AuthenticationVerification::invalid(),
            AuthenticationVerification::expired(),
            AuthenticationVerification::replayed(),
            AuthenticationVerification::unsupported(),
            AuthenticationVerification::ambiguous(),
            AuthenticationVerification::unavailable(),
        ] as $rejected) {
            $rejectedGateway = $this->gateway($generated, new AcceptanceVerifier($rejected));
            try {
                $rejectedGateway->begin($this->cookieRequest('GET'), new AuthenticationInstant(100));
                throw new RuntimeException('authentication_downgraded');
            } catch (BrowserSessionAuthenticationException) {
            }
        }
        $this->caseCompleted();

        $sequentialCapability = new AcceptanceCapability('sequential');
        $permit = new BrowserSessionProtectedApplication(
            $gateway,
            new AuthorizationEngine([new AcceptanceEvaluator($policy->evidence()->reference(), AuthorizationDecision::Permit)]),
            new ProtectedCapabilityEnforcer(),
            $policy,
        );
        $this->require('sequential' === $permit->execute(
            $this->cookieRequest('GET'),
            new AuthenticationInstant(100),
            new AcceptanceEnvironment('public.example.v1'),
            $sequentialCapability,
        ), 'sequential_permit_failed');
        $anonymousAfter = $gateway->begin(new ServerRequest('GET', 'https://example.test/public'), new AuthenticationInstant(100));
        $this->require(SecurityContextState::Anonymous === $anonymousAfter->securityContext()->state(), 'sequential_principal_leak');
        $anonymousAfter->close();
        $this->caseCompleted();

        $generated->shutdown();
    }

    private function acceptPolicyCurrentness(TemporarySecurityApplication $temporary, CurrentSecurityPolicy $policy): void
    {
        $workflow = new SecurityPolicyBuildWorkflow();
        $source = $temporary->read($temporary->policySource());
        $changed = str_replace('"version":"v1"', '"version":"v2"', $source);
        $this->require($changed !== $source, 'policy_change_fixture_invalid');
        $temporary->write($temporary->policySource(), $changed);
        try {
            $workflow->check($temporary->applicationRoot(), $temporary->buildDirectory());
            throw new RuntimeException('stale_policy_accepted');
        } catch (SecurityPolicyWorkflowException) {
        }
        $temporary->write($temporary->policySource(), $source);
        $this->require($policy->artifactSha256() === $workflow->check($temporary->applicationRoot(), $temporary->buildDirectory())->artifactSha256(), 'policy_restore_failed');
        $this->caseCompleted();

        $artifactPath = $temporary->buildDirectory() . '/' . $policy->artifactBasename();
        $artifact = $temporary->read($artifactPath);
        $temporary->write($artifactPath, self::SENTINEL . "\n");
        try {
            $workflow->check($temporary->applicationRoot(), $temporary->buildDirectory());
            throw new RuntimeException('tampered_policy_accepted');
        } catch (SecurityPolicyWorkflowException) {
        }
        $temporary->write($artifactPath, $artifact);
        $this->require($policy->artifactSha256() === $workflow->check($temporary->applicationRoot(), $temporary->buildDirectory())->artifactSha256(), 'artifact_restore_failed');
        $this->caseCompleted();
    }

    private function acceptConsoleReadOnly(TemporarySecurityApplication $temporary): void
    {
        $edge = $this->ordinaryExecutable($this->repositoryRoot . '/vendor/bin/kongo');
        $arguments = [
            '--application-root=' . $temporary->applicationRoot(),
            '--output-directory=' . $temporary->buildDirectory(),
        ];
        $before = $temporary->treeIdentity();
        $inspect = $this->process([PHP_BINARY, $edge, '--json', 'security:inspect', ...$arguments], $this->repositoryRoot);
        $this->expectSuccess($inspect);
        $this->observe($inspect);
        $inspectDocument = $this->canonicalJsonLine($inspect->stdout());
        $this->require('succeeded' === ($inspectDocument['outcome'] ?? null), 'inspect_outcome_invalid');
        $this->require($before === $temporary->treeIdentity(), 'inspect_wrote_state');
        $this->caseCompleted();

        $check = $this->process([PHP_BINARY, $edge, '--json', 'security:check', ...$arguments], $this->repositoryRoot);
        $this->expectSuccess($check);
        $this->observe($check);
        $checkDocument = $this->canonicalJsonLine($check->stdout());
        $this->require('succeeded' === ($checkDocument['outcome'] ?? null), 'security_check_outcome_invalid');
        $this->require($before === $temporary->treeIdentity(), 'security_check_wrote_state');
        $this->caseCompleted();

        $mutation = $this->process([PHP_BINARY, $edge, '--json', 'security:check', '--apply', ...$arguments], $this->repositoryRoot);
        $this->require($mutation->started() && null === $mutation->blockedReason() && 0 !== $mutation->exitCode(), 'security_mutation_not_rejected');
        $this->observe($mutation);
        $this->canonicalJsonLine($mutation->stdout());
        $this->require($before === $temporary->treeIdentity(), 'security_mutation_changed_state');
        $this->caseCompleted();

        $humanHelp = $this->process([PHP_BINARY, $edge, 'security:check', '--help'], $this->repositoryRoot);
        $machineHelp = $this->process([PHP_BINARY, $edge, '--json', 'security:check', '--help'], $this->repositoryRoot);
        $this->expectSuccess($humanHelp);
        $this->expectSuccess($machineHelp);
        $this->observe($humanHelp);
        $this->observe($machineHelp);
        $this->require(str_contains($humanHelp->stdout(), 'security:check') && !str_contains($humanHelp->stdout(), "\033"), 'human_help_invalid');
        $machine = $this->canonicalJsonLine($machineHelp->stdout());
        $this->require('kongono.command-help' === ($machine['schema'] ?? null), 'machine_help_invalid');
        $this->caseCompleted();
    }

    private function generatedApplication(): GeneratedSecurityApplication
    {
        $wiring = new LoadedGeneratedWiring(
            str_repeat('a', 64),
            AcceptanceApplicationAssembly::class,
            AcceptanceOperationAssembly::class,
            new ReflectionMethod(AcceptanceApplicationAssembly::class, 'start'),
        );

        return GeneratedSecurityApplication::start($wiring);
    }

    private function gateway(GeneratedSecurityApplication $application, AcceptanceVerifier $verifier): BrowserSessionSecurityGateway
    {
        return new BrowserSessionSecurityGateway(
            new TrustedOrigin('https://example.test'),
            new AuthenticationOrchestrator([$verifier]),
            new AuthenticationRequirement(new AuthenticationStrength(1), 300),
            $application,
        );
    }

    /** @param array<string, string|list<string>> $headers */
    private function cookieRequest(string $method, array $headers = []): ServerRequest
    {
        $headers['Cookie'] = '__Host-kongono_session=' . self::SESSION;

        return new ServerRequest($method, 'https://example.test/path', [], $headers);
    }

    private static function authenticatedVerification(): AuthenticationVerification
    {
        $principal = new PrincipalReference(PrincipalKind::Human, new PrincipalId(self::PRINCIPAL));

        return AuthenticationVerification::authenticated(new AuthenticationFacts(
            $principal,
            new AuthenticationMechanism(BrowserSessionProfile::NAME),
            new AuthenticationStrength(2),
            new AuthenticationInstant(90),
            new AuthenticationInstant(200),
        ));
    }

    /** @return array{composition:string,wiring:string,policy:string} */
    private function buildIdentities(string $stdout): array
    {
        $patterns = [
            'composition' => '/composition_artifact_sha256=([a-f0-9]{64})/',
            'wiring' => '/generated_wiring_sha256=([a-f0-9]{64})/',
            'policy' => '/security_policy_artifact_sha256=([a-f0-9]{64})/',
        ];
        $identities = [];
        foreach ($patterns as $name => $pattern) {
            if (1 !== preg_match($pattern, $stdout, $match)) {
                throw new RuntimeException('build_identity_invalid');
            }
            $identities[$name] = $match[1];
        }

        return $identities;
    }

    /** @param array<string, string|null> $environment */
    private function composerScript(string $workingDirectory, string $script, array $environment): ProcessResult
    {
        return $this->process([...$this->composerCommand, '--no-interaction', '--no-ansi', $script], $workingDirectory, $environment);
    }

    /** @return list<string> */
    private function resolveComposerCommand(): array
    {
        $candidates = [];
        $configured = getenv('COMPOSER_BINARY');
        if (is_string($configured) && '' !== $configured) {
            $candidates[] = $configured;
        }
        $path = getenv('PATH');
        if (is_string($path)) {
            foreach (explode(PATH_SEPARATOR, $path) as $directory) {
                if ('' !== $directory) {
                    $candidates[] = rtrim($directory, '\\/') . DIRECTORY_SEPARATOR . 'composer.phar';
                }
            }
        }
        foreach ($candidates as $candidate) {
            $resolved = realpath($candidate);
            if (false !== $resolved && !is_link($candidate) && is_file($resolved) && is_readable($resolved)) {
                return [PHP_BINARY, self::normalize($resolved)];
            }
        }

        throw new RuntimeException('composer_binary_unavailable');
    }

    /**
     * @param list<string>                    $command
     * @param array<string, string|null>|null $environment
     */
    private function process(array $command, string $workingDirectory, ?array $environment = null): ProcessResult
    {
        if ([] === $command) {
            return new ProcessResult(false, null, '', '', 'invalid_process');
        }
        $inherited = getenv();
        if (null !== $environment) {
            foreach ($environment as $name => $value) {
                if (null === $value) {
                    unset($inherited[$name]);
                } else {
                    $inherited[$name] = $value;
                }
            }
        }
        $pipes = [];
        $process = @proc_open(
            $command,
            [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']],
            $pipes,
            $workingDirectory,
            $inherited,
            ['bypass_shell' => true, 'suppress_errors' => true, 'binary_pipes' => true],
        );
        if (!is_resource($process) || 3 !== count($pipes)) {
            return new ProcessResult(false, null, '', '', 'process_start_failed');
        }
        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        $stdout = '';
        $stderr = '';
        $blocked = null;
        $observedExit = null;
        $deadline = microtime(true) + self::PROCESS_TIMEOUT_SECONDS;
        try {
            while (true) {
                $this->drain($pipes[1], $stdout);
                $this->drain($pipes[2], $stderr);
                if (strlen($stdout) > self::MAX_STREAM_BYTES || strlen($stderr) > self::MAX_STREAM_BYTES) {
                    $blocked = 'output_limit';
                    proc_terminate($process);
                    break;
                }
                $status = proc_get_status($process);
                if (!$status['running']) {
                    $observedExit = $status['exitcode'] >= 0 ? $status['exitcode'] : null;
                    break;
                }
                if (microtime(true) >= $deadline) {
                    $blocked = 'timeout';
                    proc_terminate($process);
                    break;
                }
                usleep(10_000);
            }
            stream_set_blocking($pipes[1], true);
            stream_set_blocking($pipes[2], true);
            $this->drain($pipes[1], $stdout);
            $this->drain($pipes[2], $stderr);
        } finally {
            foreach ([1, 2] as $index) {
                if (isset($pipes[$index]) && is_resource($pipes[$index])) {
                    fclose($pipes[$index]);
                }
            }
        }
        $closedExit = proc_close($process);
        $exit = null !== $observedExit ? $observedExit : ($closedExit >= 0 ? $closedExit : null);

        return new ProcessResult(true, $exit, substr($stdout, 0, self::MAX_STREAM_BYTES), substr($stderr, 0, self::MAX_STREAM_BYTES), $blocked);
    }

    /** @param resource $stream */
    private function drain(mixed $stream, string &$buffer): void
    {
        while (true) {
            $chunk = fread($stream, 65_536);
            if (false === $chunk || '' === $chunk) {
                return;
            }
            $buffer .= $chunk;
        }
    }

    private function repositoryStatus(): string
    {
        $result = $this->process(['git', '-c', 'core.excludesfile=', '-C', $this->repositoryRoot, 'status', '--porcelain=v1', '--untracked-files=all'], $this->repositoryRoot);
        $this->require($result->started() && null === $result->blockedReason() && 0 === $result->exitCode() && '' === $result->stderr(), 'git_status_failed');

        return $result->stdout();
    }

    private function containsLink(string $root): bool
    {
        $resolved = realpath($root);
        if (false === $resolved || is_link($root) || !is_dir($resolved)) {
            return true;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($resolved, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );
        foreach ($iterator as $entry) {
            if ($entry instanceof \SplFileInfo) {
                $path = self::normalize($entry->getPathname());
                $target = @readlink($path);
                if ($entry->isLink() || false !== $target && strtolower(self::normalize($target)) !== strtolower($path)) {
                    return true;
                }
            }
        }

        return false;
    }

    /** @return array<string, mixed> */
    private function canonicalJsonLine(string $bytes): array
    {
        $this->require('' !== $bytes && !str_contains($bytes, "\r") && !str_contains($bytes, "\033"), 'json_framing_invalid');
        $this->require(1 === substr_count($bytes, "\n") && str_ends_with($bytes, "\n"), 'json_line_invalid');
        try {
            $document = json_decode(substr($bytes, 0, -1), true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException $failure) {
            throw new RuntimeException('json_invalid', 0, $failure);
        }
        if (!is_array($document) || array_is_list($document)) {
            throw new RuntimeException('json_object_required');
        }
        $object = [];
        foreach ($document as $key => $value) {
            if (!is_string($key)) {
                throw new RuntimeException('json_object_key_invalid');
            }
            $object[$key] = $value;
        }
        $canonical = json_encode($object, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";
        $this->require($bytes === $canonical, 'json_not_canonical');

        return $object;
    }

    private function ordinaryExecutable(string $path): string
    {
        $resolved = realpath($path);
        if (false === $resolved || is_link($path) || !is_file($resolved) || !is_readable($resolved)) {
            throw new RuntimeException('executable_invalid');
        }

        return self::normalize($resolved);
    }

    private function expectSuccess(ProcessResult $result): void
    {
        $this->expectCompleted($result);
        $this->require('' === $result->stderr(), 'process_stderr_unexpected');
    }

    private function expectCompleted(ProcessResult $result): void
    {
        $this->require($result->started() && null === $result->blockedReason() && 0 === $result->exitCode(), 'process_failed');
    }

    private function observe(ProcessResult $result): void
    {
        $this->observedOutput[] = $result->stdout();
        $this->observedOutput[] = $result->stderr();
    }

    private function caseCompleted(): void
    {
        ++$this->caseCount;
    }

    private function require(bool $condition, string $token): void
    {
        if (!$condition) {
            throw new RuntimeException($token);
        }
    }

    /** @param resource $stream */
    private function write(mixed $stream, string $bytes): void
    {
        for ($offset = 0, $length = strlen($bytes); $offset < $length;) {
            $written = fwrite($stream, substr($bytes, $offset));
            if (false === $written || 0 === $written) {
                throw new RuntimeException('acceptance_output_failed');
            }
            $offset += $written;
        }
    }

    private static function normalize(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}

final readonly class AcceptanceEvidence implements AuthenticationEvidence
{
    public function __construct(private string $name = BrowserSessionProfile::NAME) {}

    public function mechanism(): AuthenticationMechanism
    {
        return new AuthenticationMechanism($this->name);
    }
}

final class AcceptanceVerifier implements AuthenticationVerifier
{
    public int $beginCalls = 0;
    public int $verifyCalls = 0;
    public int $retireCalls = 0;
    public ?BrowserSessionEvidence $lastEvidence = null;

    public function __construct(private readonly AuthenticationVerification $verification) {}

    public function mechanism(): AuthenticationMechanism
    {
        return new AuthenticationMechanism(BrowserSessionProfile::NAME);
    }

    public function begin(AuthenticationEvidence $evidence, AuthenticationInstant $now): AuthenticationVerificationOperation
    {
        ++$this->beginCalls;
        if ($evidence instanceof BrowserSessionEvidence) {
            $this->lastEvidence = $evidence;
        }

        return new AcceptanceVerificationOperation($this, $this->verification);
    }
}

final readonly class AcceptanceVerificationOperation implements AuthenticationVerificationOperation
{
    public function __construct(
        private AcceptanceVerifier $verifier,
        private AuthenticationVerification $verification,
    ) {}

    public function verify(): AuthenticationVerification
    {
        ++$this->verifier->verifyCalls;

        return $this->verification;
    }

    public function retire(): void
    {
        ++$this->verifier->retireCalls;
    }
}

final readonly class AcceptanceEnvironment implements AuthorizationEnvironmentEvidence
{
    public function __construct(private string $name) {}

    public function profile(): AuthorizationEnvironmentProfile
    {
        return new AuthorizationEnvironmentProfile($this->name);
    }
}

final class AcceptanceEvaluator implements AuthorizationEvaluator
{
    public int $evaluateCalls = 0;

    public function __construct(
        private readonly AuthorizationPolicyReference $policy,
        private readonly AuthorizationDecision $decision,
    ) {}

    public function policy(): AuthorizationPolicyReference
    {
        return $this->policy;
    }

    public function evaluate(AuthorizationRequest $request): AuthorizationDecision
    {
        ++$this->evaluateCalls;

        return $this->decision;
    }
}

/** @implements ProtectedCapability<string> */
final class AcceptanceCapability implements ProtectedCapability
{
    public int $executeCalls = 0;

    public function __construct(private readonly string $result) {}

    public function action(): Action
    {
        return new Action('public.example.read');
    }

    public function resource(): ResourceReference
    {
        return new ResourceReference(new ResourceType('public.example'), new ResourceId('fixed-public-result'));
    }

    public function execute(): string
    {
        ++$this->executeCalls;

        return $this->result;
    }
}

final readonly class AcceptanceSecurityRoot implements SecurityOperationRoot
{
    public function __construct(public SecurityContext $securityContext) {}
}

final class AcceptanceApplicationAssembly implements GeneratedOperationInputApplicationBoundary
{
    public static int $beginCalls = 0;
    public static int $active = 0;
    public static ?Closure $onBegin = null;
    private bool $retired = false;
    private bool $beginning = false;

    private function __construct() {}

    public static function start(): self
    {
        return new self();
    }

    /** @return list<class-string> */
    public static function expectedOperationInputContracts(): array
    {
        return [SecurityContext::class];
    }

    public function beginOperation(): GeneratedOperationBoundary
    {
        return $this->beginOperationWithInputs(GeneratedOperationInputs::none());
    }

    public function beginOperationWithInputs(GeneratedOperationInputs $inputs): GeneratedOperationBoundary
    {
        if ($this->retired) {
            throw new LogicException('retired');
        }
        if ($this->beginning) {
            throw new LogicException('reentrant');
        }
        $this->beginning = true;
        try {
            $inputs->validateCount(1);
            $context = $inputs->validatedAt(0, SecurityContext::class);
            ++self::$beginCalls;
            ++self::$active;
            $callback = self::$onBegin;
            if (null !== $callback) {
                $callback($this);
            }

            return new AcceptanceOperationAssembly(new AcceptanceSecurityRoot($context));
        } finally {
            $this->beginning = false;
        }
    }

    public function shutdown(?Throwable $primaryFailure = null): void
    {
        if ($this->retired) {
            return;
        }
        if (0 !== self::$active) {
            throw new LogicException('active');
        }
        $this->retired = true;
        if (null !== $primaryFailure) {
            throw $primaryFailure;
        }
    }

    public function isRetired(): bool
    {
        return $this->retired;
    }

    public static function operationClosed(): void
    {
        --self::$active;
    }

    public static function reset(): void
    {
        self::$beginCalls = 0;
        self::$active = 0;
        self::$onBegin = null;
        AcceptanceOperationAssembly::$closeCalls = 0;
        AcceptanceOperationAssembly::$cleanupFailure = null;
        AcceptanceOperationAssembly::$events = [];
    }
}

final class AcceptanceOperationAssembly implements GeneratedOperationBoundary
{
    public static int $closeCalls = 0;
    public static ?Throwable $cleanupFailure = null;

    /** @var list<string> */
    public static array $events = [];
    private bool $closed = false;

    public function __construct(private readonly AcceptanceSecurityRoot $root) {}

    public function operationRoot1(): AcceptanceSecurityRoot
    {
        return $this->root;
    }

    public function close(?Throwable $primaryFailure = null): void
    {
        if ($this->closed) {
            return;
        }
        $this->closed = true;
        ++self::$closeCalls;
        self::$events[] = 'retire';
        AcceptanceApplicationAssembly::operationClosed();
        self::$events[] = 'release';
        $cleanupFailure = self::$cleanupFailure;
        if (null !== $primaryFailure && null !== $cleanupFailure) {
            throw new CompositionLifecycleException($primaryFailure, [$cleanupFailure], 'operation');
        }
        if (null !== $primaryFailure) {
            throw $primaryFailure;
        }
        if (null !== $cleanupFailure) {
            throw $cleanupFailure;
        }
    }

    public function isClosed(): bool
    {
        return $this->closed;
    }
}
