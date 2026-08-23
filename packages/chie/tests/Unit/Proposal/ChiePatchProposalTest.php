<?php

declare(strict_types=1);

namespace Kongono\Chie\Tests\Unit\Proposal;

use Kongono\Chie\Exception\InvalidChieValueException;
use Kongono\Chie\Proposal\ChiePatchHandoff;
use Kongono\Chie\Proposal\ChiePatchProposal;
use Kongono\Chie\Proposal\ChiePatchProvenance;
use PHPUnit\Framework\TestCase;

final class ChiePatchProposalTest extends TestCase
{
    public function testCanonicalShapeAndProposalIdentityExcludeOnlyTheIdentityField(): void
    {
        $proposal = $this->proposal();
        $data = $proposal->toArray();

        self::assertSame([
            'schema', 'version', 'role', 'status', 'operation', 'rule', 'authority',
            'framework_artifact_sha256', 'framework_source_set_sha256', 'target_path',
            'base_sha256', 'proposed_sha256', 'proposal_sha256', 'forward_diff',
            'reverse_diff', 'provenance', 'hantei_handoff', 'warnings',
        ], array_keys($data));
        $identity = $data['proposal_sha256'];
        self::assertIsString($identity);
        unset($data['proposal_sha256']);
        $bytes = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR, 32) . "\n";
        self::assertSame(strtoupper(hash('sha256', $bytes)), $identity);
        self::assertSame(['generated-candidate-not-authority', 'candidate-not-applied', 'candidate-not-verified'], $data['warnings']);
    }

    public function testRejectsInconsistentHandoffAndWarnings(): void
    {
        $this->expectException(InvalidChieValueException::class);
        new ChiePatchProposal(
            str_repeat('A', 64),
            str_repeat('B', 64),
            'docs/file.md',
            str_repeat('C', 64),
            str_repeat('D', 64),
            "--- a/docs/file.md\n+++ b/docs/file.md\n@@ -1,1 +1,1 @@\n-old\n+new\n",
            "--- a/docs/file.md\n+++ b/docs/file.md\n@@ -1,1 +1,1 @@\n-new\n+old\n",
            new ChiePatchProvenance('ref', 'docs/file.md', 'current-explanatory-documentation', str_repeat('C', 64), 1, 1),
            new ChiePatchHandoff(str_repeat('E', 64)),
            ['candidate-not-applied'],
        );
    }

    private function proposal(): ChiePatchProposal
    {
        return new ChiePatchProposal(
            str_repeat('A', 64),
            str_repeat('B', 64),
            'docs/file.md',
            str_repeat('C', 64),
            str_repeat('D', 64),
            "--- a/docs/file.md\n+++ b/docs/file.md\n@@ -1,1 +1,1 @@\n-old\n+new\n",
            "--- a/docs/file.md\n+++ b/docs/file.md\n@@ -1,1 +1,1 @@\n-new\n+old\n",
            new ChiePatchProvenance('ref', 'docs/file.md', 'current-explanatory-documentation', str_repeat('C', 64), 1, 1),
            new ChiePatchHandoff(str_repeat('D', 64)),
            ['generated-candidate-not-authority', 'candidate-not-applied', 'candidate-not-verified'],
        );
    }
}
