<?php

declare(strict_types=1);

use Kongono\Authentication\AuthenticationInstant;
use Kongono\Authentication\AuthenticationOrchestrator;
use Kongono\Authentication\AuthenticationRequirement;
use Kongono\Authentication\AuthenticationStrength;
use Kongono\Authorization\AuthorizationEngine;
use Kongono\Composition\Runtime\GeneratedWiringLoader;
use Kongono\Composition\Workflow\ApplicationBuildWorkflow;
use Kongono\Http\Message\Message\ServerRequest;
use Kongono\SecurityApplication\BrowserSessionProtectedApplication;
use Kongono\SecurityEnforcement\Exception\ProtectedCapabilityEnforcementException;
use Kongono\SecurityEnforcement\ProtectedCapabilityEnforcer;
use Kongono\SecurityHttp\BrowserSessionSecurityGateway;
use Kongono\SecurityHttp\TrustedOrigin;
use Kongono\SecurityPolicyBuild\Workflow\SecurityPolicyBuildWorkflow;
use Kongono\SecurityRuntime\GeneratedSecurityApplication;
use KongonoSecurityMinimal\NoSessionVerifier;
use KongonoSecurityMinimal\PublicExampleCapability;
use KongonoSecurityMinimal\PublicExampleEnvironment;
use KongonoSecurityMinimal\PublicExampleEvaluator;

require dirname(__DIR__) . '/vendor/autoload.php';

$root = str_replace('\\', '/', dirname(__DIR__));
$output = $root . '/var/kongono/build';
$generated = null;

try {
    $applicationBuild = (new ApplicationBuildWorkflow())->check($root, $output);
    $policy = (new SecurityPolicyBuildWorkflow())->check($root, $output);
    $wiring = (new GeneratedWiringLoader())->loadCurrent(
        $root,
        $applicationBuild->compositionArtifactBytes(),
        $applicationBuild->generatedPhpPath(),
    );
    $generated = GeneratedSecurityApplication::start($wiring);
    $gateway = new BrowserSessionSecurityGateway(
        new TrustedOrigin('https://example.test'),
        new AuthenticationOrchestrator([new NoSessionVerifier()]),
        new AuthenticationRequirement(new AuthenticationStrength(1), 300),
        $generated,
    );
    $request = new ServerRequest('GET', 'https://example.test/public-example');
    $now = new AuthenticationInstant(100);
    $environment = new PublicExampleEnvironment();

    $permitted = new BrowserSessionProtectedApplication(
        $gateway,
        new AuthorizationEngine([PublicExampleEvaluator::permitting($policy->evidence()->reference())]),
        new ProtectedCapabilityEnforcer(),
        $policy,
    );
    $capability = new PublicExampleCapability();
    $result = $permitted->execute($request, $now, $environment, $capability);
    if ('public-example-ok' !== $result || !$capability->wasExecuted()) {
        throw new RuntimeException('Public example did not execute exactly as expected.');
    }

    $denied = new BrowserSessionProtectedApplication(
        $gateway,
        new AuthorizationEngine([PublicExampleEvaluator::denying($policy->evidence()->reference())]),
        new ProtectedCapabilityEnforcer(),
        $policy,
    );
    $deniedCapability = new PublicExampleCapability();
    try {
        $denied->execute($request, $now, $environment, $deniedCapability);
        throw new RuntimeException('Deny unexpectedly executed the capability.');
    } catch (ProtectedCapabilityEnforcementException) {
        if ($deniedCapability->wasExecuted()) {
            throw new RuntimeException('Denied capability executed.');
        }
    }

    $generated->shutdown();
    $generated = null;
    fwrite(STDOUT, "public-example-ok\ndeny-prevented-execution\n");
    exit(0);
} catch (Throwable) {
    if ($generated instanceof GeneratedSecurityApplication) {
        try {
            $generated->shutdown();
        } catch (Throwable) {
        }
    }
    fwrite(STDERR, "Security application run failed.\n");
    exit(1);
}
