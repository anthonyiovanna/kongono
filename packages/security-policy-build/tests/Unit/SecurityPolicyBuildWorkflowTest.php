<?php

declare(strict_types=1);

namespace Kongono\SecurityPolicyBuild\Tests\Unit;

use Kongono\Authorization\AuthorizationPolicyEvidence;
use Kongono\SecurityPolicyBuild\Build\SecurityPolicyArtifactBuilder;
use Kongono\SecurityPolicyBuild\Build\SecurityPolicyArtifactValidator;
use Kongono\SecurityPolicyBuild\Exception\InvalidSecurityPolicyArtifactException;
use Kongono\SecurityPolicyBuild\Exception\InvalidSecurityPolicySourceException;
use Kongono\SecurityPolicyBuild\Exception\SecurityPolicyWorkflowException;
use Kongono\SecurityPolicyBuild\Workflow\SecurityPolicyBuildWorkflow;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SecurityPolicyBuildWorkflowTest extends TestCase
{
    /** @var list<string> */
    private array $roots = [];

    protected function tearDown(): void
    {
        foreach ($this->roots as $root) {
            self::removeTree($root);
        }
    }

    public function testEqualSourcesProduceCanonicalRuleFreeEvidenceIndependentOfRoot(): void
    {
        $first = $this->application();
        $second = $this->application();
        $builder = new SecurityPolicyArtifactBuilder();

        $a = $builder->build($first);
        $b = $builder->build($second);

        self::assertInstanceOf(AuthorizationPolicyEvidence::class, $a);
        self::assertSame($a->canonicalBytes(), $b->canonicalBytes());
        self::assertSame($a->artifactSha256(), hash('sha256', $a->canonicalBytes()));
        self::assertSame('example.public', $a->reference()->name()->value());
        self::assertStringEndsWith("\n", $a->canonicalBytes());
        self::assertStringNotContainsString('rules', $a->canonicalBytes());
        self::assertSame($a->canonicalBytes(), (new SecurityPolicyArtifactValidator())->validateStructure($a->canonicalBytes())->canonicalBytes());
    }

    public function testInspectIsReadOnlyAndBuildCheckAreExactCurrentAndIdempotent(): void
    {
        $root = $this->application();
        $output = $root . '/var/build';
        $workflow = new SecurityPolicyBuildWorkflow();

        $planned = $workflow->inspect($root, $output);
        self::assertTrue($planned->publicationRequired());
        $entries = scandir($output);
        self::assertIsArray($entries);
        self::assertSame(['.', '..'], $entries);

        $current = $workflow->build($root, $output);
        self::assertSame($planned->artifactBasename(), $current->artifactBasename());
        self::assertSame($planned->artifactSha256(), $current->artifactSha256());
        self::assertSame($current->artifactSha256(), $workflow->build($root, $output)->artifactSha256());
        self::assertSame($current->artifactSha256(), $workflow->check($root, $output)->artifactSha256());
        self::assertFalse($workflow->inspect($root, $output)->publicationRequired());
    }

    public function testChangedSourceMakesOldArtifactStaleAndNewIdentityRequired(): void
    {
        $root = $this->application();
        $output = $root . '/var/build';
        $workflow = new SecurityPolicyBuildWorkflow();
        $old = $workflow->build($root, $output);
        $changed = self::source('v2');
        self::assertSame(strlen($changed), file_put_contents($root . '/kongono.security-policy.json', $changed));

        $new = $workflow->inspect($root, $output);
        self::assertTrue($new->publicationRequired());
        self::assertNotSame($old->artifactSha256(), $new->artifactSha256());
        $this->expectException(SecurityPolicyWorkflowException::class);
        $workflow->check($root, $output);
    }

    public function testConflictingTargetIsNeverOverwritten(): void
    {
        $root = $this->application();
        $output = $root . '/var/build';
        $workflow = new SecurityPolicyBuildWorkflow();
        $current = $workflow->build($root, $output);
        $target = $output . '/' . $current->artifactBasename();
        self::assertSame(9, file_put_contents($target, "conflict\n"));

        try {
            $workflow->build($root, $output);
            self::fail('Expected no-clobber failure.');
        } catch (SecurityPolicyWorkflowException) {
            self::assertSame("conflict\n", file_get_contents($target));
        }
    }

    /** @param callable(string): void $mutate */
    #[DataProvider('invalidSources')]
    public function testInvalidAndUnsafeSourcesFailClosed(callable $mutate): void
    {
        $root = $this->application();
        $mutate($root);
        $this->expectException(InvalidSecurityPolicySourceException::class);
        (new SecurityPolicyArtifactBuilder())->build($root);
    }

    /** @return iterable<string, array{callable(string): void}> */
    public static function invalidSources(): iterable
    {
        yield 'empty' => [static function (string $root): void {
            file_put_contents($root . '/kongono.security-policy.json', '');
        }];
        yield 'bom' => [static function (string $root): void {
            file_put_contents($root . '/kongono.security-policy.json', "\xEF\xBB\xBF" . self::source());
        }];
        yield 'control' => [static function (string $root): void {
            file_put_contents($root . '/kongono.security-policy.json', self::source() . "\0");
        }];
        yield 'oversize' => [static function (string $root): void {
            file_put_contents($root . '/kongono.security-policy.json', str_repeat('x', 32769));
        }];
        yield 'extra key' => [static function (string $root): void {
            file_put_contents($root . '/kongono.security-policy.json', '{"schema":"kongono.security-policy","version":1,"policy":{"name":"example.public","version":"v1","provenance":"application-owned"},"rules":[]}');
        }];
    }

    public function testCanonicalValidatorRejectsAlteredOrNoncanonicalBytes(): void
    {
        $artifact = (new SecurityPolicyArtifactBuilder())->build($this->application());
        $validator = new SecurityPolicyArtifactValidator();
        foreach (["{}\n", str_replace("\n", "\r\n", $artifact->canonicalBytes()), " " . $artifact->canonicalBytes()] as $invalid) {
            try {
                $validator->validateStructure($invalid);
                self::fail('Expected artifact rejection.');
            } catch (InvalidSecurityPolicyArtifactException) {
                self::addToAssertionCount(1);
            }
        }
    }

    public function testOutputMustBeAnExistingStrictDescendant(): void
    {
        $root = $this->application();
        $this->expectException(SecurityPolicyWorkflowException::class);
        (new SecurityPolicyBuildWorkflow())->inspect($root, dirname($root));
    }

    private function application(): string
    {
        $root = str_replace('\\', '/', sys_get_temp_dir()) . '/kongono-security-policy-' . bin2hex(random_bytes(12));
        self::assertTrue(mkdir($root . '/var/build', 0700, true));
        $source = self::source();
        self::assertSame(strlen($source), file_put_contents($root . '/kongono.security-policy.json', $source));
        $this->roots[] = $root;

        return $root;
    }

    private static function source(string $version = 'v1'): string
    {
        return '{"policy":{"provenance":"application-owned","version":"' . $version . '","name":"example.public"},"version":1,"schema":"kongono.security-policy"}' . "\n";
    }

    private static function removeTree(string $root): void
    {
        if (!is_dir($root)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $item) {
            if (!$item instanceof \SplFileInfo) {
                continue;
            }
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($root);
    }
}
