<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Tests\Unit\Workflow;

use Kongono\ModelProviderOpenAiResponses\Exception\OpenAiResponsesApplicationWorkflowException;
use Kongono\ModelProviderOpenAiResponses\Runtime\OpenAiResponsesApplicationState;
use Kongono\ModelProviderOpenAiResponses\Tests\Support\OpenAiResponsesApplicationFixture;
use PHPUnit\Framework\TestCase;

final class OpenAiResponsesApplicationBuildWorkflowTest extends TestCase
{
    public function testBuildDelegatesPublicationAndReturnsCurrentReadyCompositionWithoutEffects(): void
    {
        $fixture = new OpenAiResponsesApplicationFixture(build: false);
        try {
            $build = $fixture->build();
            self::assertSame($fixture->profile->identity()->value(), $build->profile()->identity()->value());
            self::assertSame($fixture->modelProfile->identity()->value(), $build->modelInvocationBuild()->profile()->identity()->value());
            self::assertFileExists($build->modelInvocationBuild()->generatedWiringPath());
            self::assertSame(OpenAiResponsesApplicationState::Ready, $build->application()->state());
            self::assertSame(0, $fixture->adapterFixture->createCalls);
            self::assertNull($fixture->adapterFixture->application);
        } finally {
            $fixture->cleanup();
        }
    }

    public function testCheckIsByteForByteWriteFreeAndRequiresExpectedGeneratedOutput(): void
    {
        $fixture = new OpenAiResponsesApplicationFixture(build: false);
        try {
            $fixture->publishExpectedGeneratedWiring();
            $before = self::directoryIdentity($fixture->root);
            $build = $fixture->check();
            self::assertSame($before, self::directoryIdentity($fixture->root));
            self::assertSame(OpenAiResponsesApplicationState::Ready, $build->application()->state());
            self::assertSame(0, $fixture->adapterFixture->createCalls);
        } finally {
            $fixture->cleanup();
        }

        $missing = new OpenAiResponsesApplicationFixture(build: false);
        try {
            $before = self::directoryIdentity($missing->root);
            try {
                $missing->check();
                self::fail('Check must require the exact expected generated output.');
            } catch (OpenAiResponsesApplicationWorkflowException $failure) {
                self::assertSame('KGN-OPENAI-RESPONSES-1721', $failure->diagnosticId());
            }
            self::assertSame($before, self::directoryIdentity($missing->root));
            self::assertSame(0, $missing->adapterFixture->createCalls);
        } finally {
            $missing->cleanup();
        }
    }

    public function testLowerArtifactMismatchRemainsAWriteFreeWorkflowFailure(): void
    {
        $fixture = new OpenAiResponsesApplicationFixture(build: false);
        try {
            file_put_contents($fixture->artifactFile, '{}');
            $before = self::directoryIdentity($fixture->root);
            $this->expectException(OpenAiResponsesApplicationWorkflowException::class);
            try {
                $fixture->check();
            } finally {
                self::assertSame($before, self::directoryIdentity($fixture->root));
                self::assertSame(0, $fixture->adapterFixture->createCalls);
            }
        } finally {
            $fixture->cleanup();
        }
    }

    /** @return array<string, string> */
    private static function directoryIdentity(string $root): array
    {
        $identity = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));
        foreach ($iterator as $file) {
            if ($file instanceof \SplFileInfo && $file->isFile()) {
                $path = str_replace('\\', '/', $file->getPathname());
                $hash = hash_file('sha256', $path);
                if (false === $hash) {
                    throw new \RuntimeException('Unable to hash a workflow test output.');
                }
                $identity[substr($path, strlen($root) + 1)] = $hash;
            }
        }
        ksort($identity, SORT_STRING);

        return $identity;
    }
}
