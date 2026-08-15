<?php

declare(strict_types=1);

namespace Kongono\DiagnosticPresentation\Tests\Unit;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMNodeList;
use DOMXPath;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DiagnosticPresentation\Developer\DeveloperDiagnosticOwner;
use Kongono\DiagnosticPresentation\Developer\Html\TrustedDeveloperHtmlRenderer;
use Kongono\DiagnosticPresentation\Developer\TrustedDeveloperDiagnostic;
use Kongono\DiagnosticPresentation\Developer\TrustedDeveloperDiagnosticField;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticValueKind;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

final class TrustedDeveloperHtmlRendererTest extends TestCase
{
    public function testShapeAndExperimentalStatusAreExact(): void
    {
        $reflection = new ReflectionClass(TrustedDeveloperHtmlRenderer::class);
        $declaredMethods = array_values(array_filter(
            $reflection->getMethods(),
            static fn(ReflectionMethod $method): bool => TrustedDeveloperHtmlRenderer::class === $method->getDeclaringClass()->getName(),
        ));
        $publicMethods = array_values(array_filter(
            $declaredMethods,
            static fn(ReflectionMethod $method): bool => $method->isPublic(),
        ));
        $privateMethods = array_values(array_filter(
            $declaredMethods,
            static fn(ReflectionMethod $method): bool => $method->isPrivate(),
        ));
        $attributes = $reflection->getAttributes(Api::class);

        self::assertTrue($reflection->isFinal());
        self::assertTrue($reflection->isReadOnly());
        self::assertFalse($reflection->getParentClass());
        self::assertSame([], $reflection->getInterfaceNames());
        self::assertNull($reflection->getConstructor());
        self::assertSame([], $reflection->getProperties());
        self::assertSame([], $reflection->getConstants());
        self::assertCount(1, $attributes);
        self::assertSame(ApiStatus::Experimental, $attributes[0]->newInstance()->status);
        self::assertCount(1, $publicMethods);
        self::assertSame('render', $publicMethods[0]->getName());
        self::assertSame(TrustedDeveloperDiagnostic::class, (string) $publicMethods[0]->getParameters()[0]->getType());
        self::assertSame('string', (string) $publicMethods[0]->getReturnType());
        self::assertSame([], array_values(array_filter($declaredMethods, static fn(ReflectionMethod $method): bool => $method->isProtected())));
        self::assertSame([], array_values(array_filter($declaredMethods, static fn(ReflectionMethod $method): bool => str_starts_with($method->getName(), '__'))));

        $allowedHelpers = ['renderContext', 'renderField', 'ownerLabel', 'kindLabel', 'disclosureLabel', 'formatValue', 'escape'];
        foreach ($privateMethods as $method) {
            self::assertContains($method->getName(), $allowedHelpers);
            self::assertTrue($method->isStatic());
        }
    }

    public function testFullDiagnosticMatchesGoldenDocument(): void
    {
        $html = (new TrustedDeveloperHtmlRenderer())->render($this->fullDiagnostic());

        self::assertSame($this->fixture('trusted-developer-html-full.html'), $html);
        $this->assertDocumentBytes($html);
    }

    public function testEmptyContextMatchesGoldenDocument(): void
    {
        $html = (new TrustedDeveloperHtmlRenderer())->render($this->emptyDiagnostic());

        self::assertSame($this->fixture('trusted-developer-html-empty.html'), $html);
        self::assertStringContainsString(
            '        <p class="empty-context">No additional diagnostic context was captured.</p>',
            $html,
        );
        self::assertStringNotContainsString('<dl class="fields">', $html);
        $this->assertDocumentBytes($html);
    }

    public function testEveryOwnerUsesItsExactVisibleLabel(): void
    {
        foreach ([
            [DeveloperDiagnosticOwner::Kernel, 'KGN-KERNEL-1001', 'Kernel'],
            [DeveloperDiagnosticOwner::ApplicationGraph, 'KGN-GRAPH-1001', 'Application Graph'],
            [DeveloperDiagnosticOwner::DependencyInjection, 'KGN-DI-1001', 'Dependency Injection'],
        ] as [$owner, $identifier, $label]) {
            $html = (new TrustedDeveloperHtmlRenderer())->render($this->diagnostic($owner, $identifier));
            self::assertStringContainsString(
                sprintf('<p class="owner">Owner: <span>%s</span></p>', $label),
                $html,
            );
            self::assertSame(1, substr_count($html, '<p class="owner">'));

            foreach (['Kernel', 'Application Graph', 'Dependency Injection'] as $candidate) {
                $expectedCount = $candidate === $label ? 1 : 0;
                self::assertSame(
                    $expectedCount,
                    substr_count($html, sprintf('<span>%s</span>', $candidate)),
                );
            }
        }
    }

    public function testDynamicTextIsEscapedExactlyOnceAtEveryInsertionSite(): void
    {
        $html = (new TrustedDeveloperHtmlRenderer())->render($this->fullDiagnostic());

        self::assertStringContainsString('Relation &lt;source&gt; &amp; &quot;target&quot; mismatch', $html);
        self::assertStringContainsString('Validate relation source &amp; target', $html);
        self::assertStringContainsString('Source &lt;node&gt; must exist &amp; remain &quot;canonical&quot;', $html);
        self::assertStringContainsString('Add the missing relation &amp; retry &lt;carefully&gt;.', $html);
        self::assertStringContainsString('<span class="field-label">Source &amp; key</span>', $html);
        self::assertStringContainsString('orders&lt;&amp;&gt;&quot;&apos;&amp;lt;', $html);
        self::assertStringNotContainsString('Relation <source> & "target" mismatch', $html);
        self::assertStringNotContainsString('Source <node> must exist & remain "canonical"', $html);
        self::assertStringNotContainsString('orders<&>"\'&lt;', $html);
        self::assertStringNotContainsString('&amp;amp;lt;', $html);
        self::assertStringNotContainsString('="KGN-GRAPH-1009"', $html);
        self::assertStringNotContainsString('href="orders', $html);
        self::assertStringNotContainsString('<script', strtolower($html));
    }

    public function testKindsDisclosuresTruncationAndFieldOrderRemainExact(): void
    {
        $html = (new TrustedDeveloperHtmlRenderer())->render($this->fullDiagnostic());
        $positions = array_map(
            static fn(string $key): int|false => strpos($html, sprintf('<code class="field-key">%s</code>', $key)),
            ['entry_count', 'source_key', 'canonical', 'received_value'],
        );

        foreach ($positions as $position) {
            self::assertIsInt($position);
        }
        self::assertSame($positions, array_values(array_unique($positions)));
        self::assertTrue($positions[0] < $positions[1] && $positions[1] < $positions[2] && $positions[2] < $positions[3]);
        self::assertSame(2, substr_count($html, 'Kind: text'));
        self::assertSame(1, substr_count($html, 'Kind: integer'));
        self::assertSame(1, substr_count($html, 'Kind: boolean'));
        self::assertSame(2, substr_count($html, 'Disclosure: Safe'));
        self::assertSame(2, substr_count($html, 'Disclosure: Protected'));
        self::assertStringContainsString('<pre class="field-value"><code>3</code></pre>', $html);
        self::assertStringContainsString('<pre class="field-value"><code>true</code></pre>', $html);
        self::assertSame(1, substr_count($html, '<span class="meta-item">Truncated</span>'));
        self::assertStringNotContainsString("Disclosure: Protected</span>\n\n              </div>", $html);
        self::assertStringNotContainsString("Disclosure: Safe</span>\n\n              </div>", $html);
    }

    public function testRenderingIsDeterministicAndStateless(): void
    {
        $renderer = new TrustedDeveloperHtmlRenderer();
        $diagnostic = $this->fullDiagnostic();
        $first = $renderer->render($diagnostic);
        $second = $renderer->render($diagnostic);
        $fresh = (new TrustedDeveloperHtmlRenderer())->render($this->fullDiagnostic());

        $interleaved = $renderer->render($this->emptyDiagnostic());
        $afterInterleaving = $renderer->render($diagnostic);

        self::assertSame($first, $second);
        self::assertSame($first, $fresh);
        self::assertNotSame($first, $interleaved);
        self::assertSame($first, $afterInterleaving);
        self::assertSame([], (new ReflectionClass($renderer))->getProperties());
    }

    public function testMaximumValidDiagnosticRemainsWithinOutputBounds(): void
    {
        $fields = [];
        for ($index = 0; $index < 16; ++$index) {
            $fields[] = $this->field(
                sprintf('field_%02d', $index),
                str_repeat('&', 96),
                DiagnosticValueKind::Text,
                str_repeat('&', 512),
                DiagnosticDisclosure::Protected,
                false,
            );
        }
        $maximum = new TrustedDeveloperDiagnostic(
            DeveloperDiagnosticOwner::DependencyInjection,
            'KGN-DI-9999',
            str_repeat('&', 160),
            str_repeat('&', 160),
            str_repeat('&', 256),
            str_repeat('&', 256),
            $fields,
        );
        $renderer = new TrustedDeveloperHtmlRenderer();
        $html = $renderer->render($maximum);
        $empty = $this->fixture('trusted-developer-html-empty.html');
        $styleStart = strpos($empty, '<style>');
        $styleEnd = strpos($empty, '</style>');

        self::assertIsInt($styleStart);
        self::assertIsInt($styleEnd);
        $stylesheet = substr($empty, $styleStart + strlen('<style>'), $styleEnd - $styleStart - strlen('<style>'));
        self::assertLessThanOrEqual(98_304, strlen($html));
        self::assertLessThanOrEqual(16_384, strlen($stylesheet));
        self::assertSame(16, substr_count($html, '<div class="context-item">'));

        $dynamicBytes = 2 * strlen(self::escape('KGN-KERNEL-1001'))
            + 2 * strlen(self::escape('Kernel boot failed'))
            + strlen(self::escape('Kernel'))
            + strlen(self::escape('Boot kernel'))
            + strlen(self::escape('Kernel state must be cold'))
            + strlen(self::escape('Create one fresh kernel instance.'));
        self::assertLessThanOrEqual(16_384, strlen($empty) - $dynamicBytes);

        $one = $renderer->render($this->diagnostic(DeveloperDiagnosticOwner::Kernel, 'KGN-KERNEL-1001', [$this->field('field_00')]));
        $two = $renderer->render($this->diagnostic(DeveloperDiagnosticOwner::Kernel, 'KGN-KERNEL-1001', [$this->field('field_00'), $this->field('field_01')]));
        $three = $renderer->render($this->diagnostic(DeveloperDiagnosticOwner::Kernel, 'KGN-KERNEL-1001', [$this->field('field_00'), $this->field('field_01'), $this->field('field_02')]));
        self::assertSame(strlen($two) - strlen($one), strlen($three) - strlen($two));
        self::assertSame([1, 2, 3], [substr_count($one, '<div class="context-item">'), substr_count($two, '<div class="context-item">'), substr_count($three, '<div class="context-item">')]);
    }

    public function testDocumentUsesTheExactSemanticAccessibilityGrammar(): void
    {
        $html = (new TrustedDeveloperHtmlRenderer())->render($this->fullDiagnostic());
        $previous = libxml_use_internal_errors(true);

        try {
            libxml_clear_errors();
            $document = new DOMDocument();
            self::assertTrue($document->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING));
            $xpath = new DOMXPath($document);

            self::assertNotNull($document->doctype);
            self::assertSame('html', strtolower($document->doctype->name));
            self::assertSame(1, $this->query($xpath, '/html[@lang="en"]')->length);
            self::assertSame(1, $this->query($xpath, '/html/head/meta[@charset="utf-8"]')->length);
            self::assertSame(1, $this->query($xpath, '/html/head/meta[@name="viewport" and @content="width=device-width, initial-scale=1"]')->length);
            self::assertSame(1, $this->query($xpath, '/html/head/meta[@name="color-scheme" and @content="light dark"]')->length);
            self::assertSame('KGN-GRAPH-1009 — Relation <source> & "target" mismatch — Kongono diagnostic', $this->text($xpath, '/html/head/title'));
            self::assertSame(1, $this->query($xpath, '//a[@class="skip-link" and @href="#diagnostic-main"]')->length);
            self::assertSame(1, $this->query($xpath, '//main[@id="diagnostic-main"]')->length);
            self::assertSame(1, $this->query($xpath, '//article[@class="diagnostic-card"]')->length);
            self::assertSame(1, $this->query($xpath, '//h1')->length);

            $headings = [];
            foreach ($this->query($xpath, '//h2') as $heading) {
                self::assertInstanceOf(DOMElement::class, $heading);
                $headings[] = trim($heading->textContent);
            }
            self::assertSame(['What happened', 'Why it happened', 'Context', 'How to fix it'], $headings);
            self::assertSame(1, $this->query($xpath, '//dl[@class="fields"]')->length);
            self::assertSame(4, $this->query($xpath, '//dl[@class="fields"]/div[@class="context-item"]')->length);
            self::assertSame(4, $this->query($xpath, '//dl[@class="fields"]//dt')->length);
            self::assertSame(4, $this->query($xpath, '//dl[@class="fields"]//dd')->length);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        foreach (['@media (prefers-color-scheme: dark)', '@media (forced-colors: active)', '@media print', '.skip-link:focus', 'overflow-wrap: anywhere', '@media (max-width: 36rem)'] as $rule) {
            self::assertStringContainsString($rule, $html);
        }
        self::assertStringNotContainsString('animation:', $html);
        self::assertStringNotContainsString('transition:', $html);
    }

    public function testDocumentContainsNoActiveExternalOrDynamicAttributeSurface(): void
    {
        $html = (new TrustedDeveloperHtmlRenderer())->render($this->fullDiagnostic());
        $previous = libxml_use_internal_errors(true);

        try {
            libxml_clear_errors();
            $document = new DOMDocument();
            self::assertTrue($document->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING));
            $xpath = new DOMXPath($document);

            foreach (['script', 'form', 'button', 'iframe', 'img', 'video', 'audio', 'object', 'embed', 'base'] as $tag) {
                self::assertSame(0, $this->query($xpath, '//' . $tag)->length, $tag);
            }
            self::assertSame(0, $this->query($xpath, '//link')->length);
            self::assertSame(0, $this->query($xpath, '//meta[translate(@http-equiv, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")="refresh"]')->length);
            self::assertSame(0, $this->query($xpath, '//*[@style]')->length);
            self::assertSame(0, $this->query($xpath, '//@*[starts-with(translate(name(), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), "on")]')->length);
            self::assertSame(1, $this->query($xpath, '//a')->length);
            self::assertSame('#diagnostic-main', $this->text($xpath, '//a/@href'));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        self::assertDoesNotMatchRegularExpression('/https?:|\/\/|url\s*\(/i', $html);
        self::assertStringNotContainsString('preload', strtolower($html));
        self::assertStringNotContainsString('prefetch', strtolower($html));
        self::assertStringNotContainsString('beacon', strtolower($html));
    }

    private function fullDiagnostic(): TrustedDeveloperDiagnostic
    {
        return new TrustedDeveloperDiagnostic(
            DeveloperDiagnosticOwner::ApplicationGraph,
            'KGN-GRAPH-1009',
            'Relation <source> & "target" mismatch',
            'Validate relation source & target',
            'Source <node> must exist & remain "canonical"',
            'Add the missing relation & retry <carefully>.',
            [
                $this->field('entry_count', 'Entry count', DiagnosticValueKind::Integer, 3, DiagnosticDisclosure::Safe, null),
                $this->field('source_key', 'Source & key', DiagnosticValueKind::Text, 'orders<&>"\'&lt;', DiagnosticDisclosure::Protected, false),
                $this->field('canonical', 'Canonical', DiagnosticValueKind::Boolean, true, DiagnosticDisclosure::Safe, null),
                $this->field('received_value', 'Received value', DiagnosticValueKind::Text, 'abcdefgh...[truncated]', DiagnosticDisclosure::Protected, true),
            ],
        );
    }

    private function emptyDiagnostic(): TrustedDeveloperDiagnostic
    {
        return new TrustedDeveloperDiagnostic(
            DeveloperDiagnosticOwner::Kernel,
            'KGN-KERNEL-1001',
            'Kernel boot failed',
            'Boot kernel',
            'Kernel state must be cold',
            'Create one fresh kernel instance.',
            [],
        );
    }

    /** @param list<TrustedDeveloperDiagnosticField> $fields */
    private function diagnostic(
        DeveloperDiagnosticOwner $owner,
        string $identifier,
        array $fields = [],
    ): TrustedDeveloperDiagnostic {
        return new TrustedDeveloperDiagnostic(
            $owner,
            $identifier,
            'Title',
            'Operation',
            'Invariant',
            'Remediation',
            $fields,
        );
    }

    private function field(
        string $key,
        string $label = 'Field',
        DiagnosticValueKind $kind = DiagnosticValueKind::Text,
        string|int|bool $value = 'value',
        DiagnosticDisclosure $disclosure = DiagnosticDisclosure::Safe,
        ?bool $textWasTruncated = false,
    ): TrustedDeveloperDiagnosticField {
        return new TrustedDeveloperDiagnosticField(
            $key,
            $label,
            $kind,
            $value,
            $disclosure,
            $textWasTruncated,
        );
    }

    private function fixture(string $name): string
    {
        $fixture = file_get_contents(__DIR__ . '/../Fixture/' . $name);
        self::assertIsString($fixture);

        return $fixture;
    }

    /** @return DOMNodeList<\DOMNameSpaceNode|DOMNode> */
    private function query(DOMXPath $xpath, string $expression): DOMNodeList
    {
        $nodes = $xpath->query($expression);
        self::assertNotFalse($nodes);

        return $nodes;
    }

    private function text(DOMXPath $xpath, string $expression): string
    {
        $nodes = $this->query($xpath, $expression);
        self::assertSame(1, $nodes->length);
        $node = $nodes->item(0);
        self::assertInstanceOf(DOMNode::class, $node);

        return trim($node->textContent);
    }

    private function assertDocumentBytes(string $html): void
    {
        self::assertStringStartsWith('<!doctype html>', $html);
        self::assertStringEndsWith("</html>\n", $html);
        self::assertNotSame("\n", substr($html, -2, 1));
        self::assertStringNotContainsString("\r", $html);
        self::assertStringNotContainsString("\0", $html);
        self::assertFalse(str_starts_with($html, "\xEF\xBB\xBF"));
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars(
            $value,
            ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5,
            'UTF-8',
            true,
        );
    }
}
