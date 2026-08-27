<?php

declare(strict_types=1);

namespace Kongono\Authorization\Tests\Unit;

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
use Kongono\Authorization\Exception\AuthorizationEvaluationException;
use Kongono\Authorization\Exception\InvalidAuthorizationValueException;
use Kongono\IdentityAccess\Action;
use Kongono\IdentityAccess\AuthorizationDecision;
use Kongono\IdentityAccess\ResourceId;
use Kongono\IdentityAccess\ResourceReference;
use Kongono\IdentityAccess\ResourceType;
use Kongono\IdentityAccess\SecurityContext;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

final class AuthorizationEngineTest extends TestCase
{
    public function testRegistrationBoundsListMembersAndDuplicatesAreExact(): void
    {
        $single = new EngineEvaluator($this->policy('policy.0'), AuthorizationDecision::Deny);
        self::assertInstanceOf(AuthorizationEngine::class, new AuthorizationEngine([$single]));

        $thirtyTwo = [];
        for ($index = 0; $index < 32; ++$index) {
            $thirtyTwo[] = new EngineEvaluator($this->policy('policy.' . $index), AuthorizationDecision::Deny);
        }
        self::assertInstanceOf(AuthorizationEngine::class, new AuthorizationEngine($thirtyTwo));

        foreach ([[], array_merge($thirtyTwo, [$single]), [1 => $single], [new stdClass()], [$single, $single]] as $invalid) {
            try {
                new AuthorizationEngine($invalid);
                self::fail('Invalid evaluator registration accepted.');
            } catch (InvalidAuthorizationValueException $failure) {
                self::assertSame('KGN-AUTHORIZATION-1002', $failure->diagnosticId());
            }
        }
    }

    public function testMismatchedEvidenceIsIndeterminateWithoutEvaluatorAndReadsReferenceOnce(): void
    {
        $expected = $this->policy('policy', 'v1', 'source.one');
        $evidence = new EnginePolicyEvidence($this->policy('policy', 'v2', 'source.one'));
        $evaluator = new EngineEvaluator($expected, AuthorizationDecision::Permit);
        $evaluation = (new AuthorizationEngine([$evaluator]))->authorize($this->request($expected, $evidence));

        self::assertSame(AuthorizationDecision::Indeterminate, $evaluation->decision());
        self::assertSame(AuthorizationReceiptReason::PolicyMismatch, $evaluation->receipt()->reason());
        self::assertSame(1, $evidence->referenceCalls);
        self::assertSame(0, $evaluator->evaluateCalls);
    }

    public function testMatchingEvidenceWithoutExactEvaluatorIsUnsupportedAndDoesNotEvaluate(): void
    {
        $expected = $this->policy('policy', 'v1', 'source.one');
        $otherName = new EngineEvaluator($this->policy('other', 'v1', 'source.one'), AuthorizationDecision::Permit);
        $otherVersion = new EngineEvaluator($this->policy('policy', 'v2', 'source.one'), AuthorizationDecision::Permit);
        $otherProvenance = new EngineEvaluator($this->policy('policy', 'v1', 'source.two'), AuthorizationDecision::Permit);
        $evaluation = (new AuthorizationEngine([$otherName, $otherVersion, $otherProvenance]))
            ->authorize($this->request($expected, new EnginePolicyEvidence($expected)));

        self::assertSame(AuthorizationDecision::Indeterminate, $evaluation->decision());
        self::assertSame(AuthorizationReceiptReason::UnsupportedPolicy, $evaluation->receipt()->reason());
        self::assertSame(0, $otherName->evaluateCalls);
        self::assertSame(0, $otherVersion->evaluateCalls);
        self::assertSame(0, $otherProvenance->evaluateCalls);
    }

    /** @return iterable<string, array{AuthorizationDecision}> */
    public static function decisions(): iterable
    {
        yield 'permit' => [AuthorizationDecision::Permit];
        yield 'deny' => [AuthorizationDecision::Deny];
        yield 'indeterminate' => [AuthorizationDecision::Indeterminate];
    }

    #[DataProvider('decisions')]
    public function testExactEvaluatorReceivesIdenticalRequestOnceAndDecisionIsPreserved(AuthorizationDecision $decision): void
    {
        $expected = $this->policy('policy', 'v1', 'source.one');
        $evaluator = new EngineEvaluator($expected, $decision);
        $request = $this->request($expected, new EnginePolicyEvidence($expected));
        $evaluation = (new AuthorizationEngine([$evaluator]))->authorize($request);

        self::assertSame($decision, $evaluation->decision());
        self::assertSame(AuthorizationReceiptReason::Evaluated, $evaluation->receipt()->reason());
        self::assertSame(1, $evaluator->evaluateCalls);
        self::assertSame($request, $evaluator->lastRequest);
    }

    public function testRegistrationDetachesPolicyReferenceFromRepeatedAuthorization(): void
    {
        $registered = $this->policy('policy', 'v1', 'source.one');
        $evaluator = new EngineDetachedPolicyEvaluator($registered, AuthorizationDecision::Permit);
        $engine = new AuthorizationEngine([$evaluator]);

        self::assertSame(1, $evaluator->policyCalls);

        $first = $this->request($registered, new EnginePolicyEvidence($registered), 'first.read');
        self::assertSame(AuthorizationDecision::Permit, $engine->authorize($first)->decision());
        self::assertSame(1, $evaluator->evaluateCalls);
        self::assertSame($first, $evaluator->requests[0]);
        self::assertSame(1, $evaluator->policyCalls);

        $second = $this->request($registered, new EnginePolicyEvidence($registered), 'second.read');
        self::assertSame(AuthorizationDecision::Permit, $engine->authorize($second)->decision());
        self::assertSame(2, $evaluator->evaluateCalls);
        self::assertSame([$first, $second], $evaluator->requests);
        self::assertSame(1, $evaluator->policyCalls);
    }

    public function testEvaluatorFailureIsWrappedWithExactPreviousAndCannotPermit(): void
    {
        $expected = $this->policy();
        $cause = new RuntimeException('synthetic evaluator detail');
        $evaluator = new EngineEvaluator($expected, $cause);

        try {
            (new AuthorizationEngine([$evaluator]))->authorize($this->request($expected, new EnginePolicyEvidence($expected)));
            self::fail('Evaluator failure was not wrapped.');
        } catch (AuthorizationEvaluationException $failure) {
            self::assertSame($cause, $failure->getPrevious());
            self::assertSame('KGN-AUTHORIZATION-1005', $failure->diagnosticId());
            self::assertStringNotContainsString($cause->getMessage(), (string) $failure);
            self::assertStringNotContainsString($cause->getMessage(), $failure->getMessage());
        }
    }

    public function testSequentialAndReentrantEvaluationShareNoCallState(): void
    {
        $policy = $this->policy();
        $sequence = new EngineSequenceEvaluator($policy, [AuthorizationDecision::Deny, AuthorizationDecision::Permit]);
        $engine = new AuthorizationEngine([$sequence]);
        $first = $this->request($policy, new EnginePolicyEvidence($policy), 'first.read');
        $second = $this->request($policy, new EnginePolicyEvidence($policy), 'second.read');

        self::assertSame(AuthorizationDecision::Deny, $engine->authorize($first)->decision());
        self::assertSame(AuthorizationDecision::Permit, $engine->authorize($second)->decision());
        self::assertSame([$first, $second], $sequence->requests);

        $reentrant = new EngineReentrantEvaluator($policy);
        $reentrantEngine = new AuthorizationEngine([$reentrant]);
        $nested = $this->request($policy, new EnginePolicyEvidence($policy), 'nested.read');
        $outer = $this->request($policy, new EnginePolicyEvidence($policy), 'outer.read');
        $reentrant->configure($reentrantEngine, $nested);

        self::assertSame(AuthorizationDecision::Deny, $reentrantEngine->authorize($outer)->decision());
        self::assertSame(AuthorizationDecision::Permit, $reentrant->nestedEvaluation?->decision());
        self::assertSame([$outer, $nested], $reentrant->requests);
    }

    private function policy(string $name = 'policy', string $version = 'v1', string $provenance = 'source.one'): AuthorizationPolicyReference
    {
        return new AuthorizationPolicyReference(
            new AuthorizationPolicyName($name),
            new AuthorizationPolicyVersion($version),
            new AuthorizationPolicyProvenance($provenance),
        );
    }

    private function request(
        AuthorizationPolicyReference $policy,
        EnginePolicyEvidence $evidence,
        string $action = 'record.read',
    ): AuthorizationRequest {
        return new AuthorizationRequest(
            SecurityContext::anonymous(),
            new Action($action),
            new ResourceReference(new ResourceType('record'), new ResourceId('resource-engine')),
            $policy,
            $evidence,
            new EngineEnvironmentEvidence(new AuthorizationEnvironmentProfile('facts.v1')),
        );
    }
}

final class EnginePolicyEvidence implements AuthorizationPolicyEvidence
{
    public int $referenceCalls = 0;

    public function __construct(private readonly AuthorizationPolicyReference $reference) {}

    public function reference(): AuthorizationPolicyReference
    {
        ++$this->referenceCalls;

        return $this->reference;
    }
}

final readonly class EngineEnvironmentEvidence implements AuthorizationEnvironmentEvidence
{
    public function __construct(private AuthorizationEnvironmentProfile $profile) {}

    public function profile(): AuthorizationEnvironmentProfile
    {
        return $this->profile;
    }
}

final class EngineEvaluator implements AuthorizationEvaluator
{
    public int $evaluateCalls = 0;
    public ?AuthorizationRequest $lastRequest = null;

    public function __construct(
        private readonly AuthorizationPolicyReference $policy,
        private readonly AuthorizationDecision|RuntimeException $result,
    ) {}

    public function policy(): AuthorizationPolicyReference
    {
        return $this->policy;
    }

    public function evaluate(AuthorizationRequest $request): AuthorizationDecision
    {
        ++$this->evaluateCalls;
        $this->lastRequest = $request;
        if ($this->result instanceof RuntimeException) {
            throw $this->result;
        }

        return $this->result;
    }
}

final class EngineDetachedPolicyEvaluator implements AuthorizationEvaluator
{
    public int $policyCalls = 0;
    public int $evaluateCalls = 0;

    /** @var list<AuthorizationRequest> */
    public array $requests = [];

    public function __construct(
        private readonly AuthorizationPolicyReference $policy,
        private readonly AuthorizationDecision $decision,
    ) {}

    public function policy(): AuthorizationPolicyReference
    {
        ++$this->policyCalls;
        if (1 !== $this->policyCalls) {
            throw new RuntimeException('Evaluator policy was read after registration.');
        }

        return $this->policy;
    }

    public function evaluate(AuthorizationRequest $request): AuthorizationDecision
    {
        ++$this->evaluateCalls;
        $this->requests[] = $request;

        return $this->decision;
    }
}

final class EngineSequenceEvaluator implements AuthorizationEvaluator
{
    /** @var list<AuthorizationRequest> */
    public array $requests = [];

    /** @param list<AuthorizationDecision> $decisions */
    public function __construct(
        private readonly AuthorizationPolicyReference $policy,
        private array $decisions,
    ) {}

    public function policy(): AuthorizationPolicyReference
    {
        return $this->policy;
    }

    public function evaluate(AuthorizationRequest $request): AuthorizationDecision
    {
        $this->requests[] = $request;
        $decision = array_shift($this->decisions);
        if (null === $decision) {
            throw new RuntimeException('Synthetic decision sequence exhausted.');
        }

        return $decision;
    }
}

final class EngineReentrantEvaluator implements AuthorizationEvaluator
{
    /** @var list<AuthorizationRequest> */
    public array $requests = [];
    public ?AuthorizationEvaluation $nestedEvaluation = null;
    private ?AuthorizationEngine $engine = null;
    private ?AuthorizationRequest $nestedRequest = null;
    private bool $nested = false;

    public function __construct(private readonly AuthorizationPolicyReference $policy) {}

    public function configure(AuthorizationEngine $engine, AuthorizationRequest $nestedRequest): void
    {
        $this->engine = $engine;
        $this->nestedRequest = $nestedRequest;
    }

    public function policy(): AuthorizationPolicyReference
    {
        return $this->policy;
    }

    public function evaluate(AuthorizationRequest $request): AuthorizationDecision
    {
        $this->requests[] = $request;
        if (!$this->nested) {
            $this->nested = true;
            if (null === $this->engine || null === $this->nestedRequest) {
                throw new RuntimeException('Synthetic reentrant evaluator was not configured.');
            }
            $this->nestedEvaluation = $this->engine->authorize($this->nestedRequest);

            return AuthorizationDecision::Deny;
        }

        return AuthorizationDecision::Permit;
    }
}
