<?php

declare(strict_types=1);

namespace Kongono\ViewBuild\Generated;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewBuild\Exception\InvalidViewSourceException;
use Kongono\ViewBuild\Grammar\ViewAttributeValueKind;
use Kongono\ViewBuild\Grammar\ViewNode;
use Kongono\ViewBuild\Grammar\ViewNodeKind;
use Kongono\ViewBuild\Internal\BuildGuard;
use Kongono\ViewBuild\Model\CompiledViewModel;
use Kongono\ViewBuild\Source\ViewSourceIdentity;
use Kongono\ViewContract\Output\OutputContext;

/** Emits one deterministic inert ordinary-PHP representation of a compiled model. */
#[Api(ApiStatus::Internal)]
final class GeneratedPhpEmitter
{
    public function emit(CompiledViewModel $model, ViewSourceIdentity $sourceIdentity, string $modelSha256): string
    {
        if (1 !== preg_match('/\A[a-f0-9]{64}\z/D', $modelSha256)) {
            throw new \LogicException('Compiled view model identity is invalid.');
        }
        $class = 'CompiledViewSet_' . $modelSha256;
        $lines = [
            '<?php',
            '',
            'declare(strict_types=1);',
            '',
            'namespace Kongono\\Generated\\View;',
            '',
            'final class ' . $class,
            '{',
            "    public static function sourceSha256(): string",
            '    {',
            "        return '" . $sourceIdentity->value() . "';",
            '    }',
            '',
            '    public static function modelSha256(): string',
            '    {',
            "        return '" . $modelSha256 . "';",
            '    }',
            '',
            '    public static function emit(\\Kongono\\ViewContract\\Identity\\LogicalViewIdentity $view, \\Kongono\\ViewContract\\Value\\ViewContext $context, \\Kongono\\ViewBuild\\Generated\\RenderSink $sink): void',
            '    {',
            '        $viewIndex = match ($view->value()) {',
        ];
        foreach ($model->views() as $position => $view) {
            $lines[] = '            ' . self::phpString($view->identity()->value()) . ' => ' . $position . ',';
        }
        $lines[] = "            default => throw new \\LogicException('Compiled view identity mismatch.'),";
        $lines[] = '        };';

        foreach ($model->views() as $position => $view) {
            $lines[] = '';
            $lines[] = '        if (' . $position . ' === $viewIndex) {';
            $operationIndex = 0;
            $temporaryIndex = 0;
            $lines = array_merge($lines, $this->nodes($view->body(), 3, $operationIndex, $temporaryIndex, []));
            if ($operationIndex !== $view->operationCount()) {
                throw new \LogicException('Compiled view operation map mismatch.');
            }
            $lines[] = '            return;';
            $lines[] = '        }';
        }
        $lines[] = '    }';
        $lines[] = '}';
        $lines[] = '';
        $lines[] = 'return ' . self::phpString('Kongono\\Generated\\View\\' . $class) . ';';

        $bytes = implode("\n", $lines) . "\n";
        if (strlen($bytes) > BuildGuard::MAX_GENERATED_BYTES) {
            throw InvalidViewSourceException::bound('generated_bytes', BuildGuard::MAX_GENERATED_BYTES, strlen($bytes));
        }

        return $bytes;
    }

    /**
     * @param list<ViewNode>    $nodes
     * @param array<string, string> $aliases
     * @return list<string>
     */
    private function nodes(array $nodes, int $indent, int &$operationIndex, int &$temporaryIndex, array $aliases): array
    {
        $lines = [];
        foreach ($nodes as $node) {
            $lines = array_merge($lines, match ($node->kind()) {
                ViewNodeKind::Text => $this->text($node, $indent, $operationIndex),
                ViewNodeKind::Value => $this->value($node, $indent, $operationIndex, $temporaryIndex, $aliases, OutputContext::HtmlText),
                ViewNodeKind::Element => $this->element($node, $indent, $operationIndex, $temporaryIndex, $aliases),
                ViewNodeKind::Each => $this->each($node, $indent, $operationIndex, $temporaryIndex, $aliases),
            });
        }

        return $lines;
    }

    /** @return list<string> */
    private function text(ViewNode $node, int $indent, int &$operationIndex): array
    {
        $prefix = str_repeat('    ', $indent);
        $index = $operationIndex++;

        return [
            $prefix . '$sink->step(' . $index . ');',
            $prefix . '$sink->literal(' . self::phpString(BuildGuard::htmlText($node->textValue() ?? '')) . ', ' . $index . ');',
        ];
    }

    /** @param array<string, string> $aliases
     * @return list<string>
     */
    private function value(ViewNode $node, int $indent, int &$operationIndex, int &$temporaryIndex, array $aliases, OutputContext $context): array
    {
        $prefix = str_repeat('    ', $indent);
        $index = $operationIndex++;
        [$variable, $lines] = $this->reference($node->path() ?? '', $indent, $temporaryIndex, $aliases);
        $lines[] = $prefix . "if (!" . $variable . " instanceof \\Kongono\\ViewContract\\Value\\StringValue) {";
        $lines[] = $prefix . "    throw new \\LogicException('Compiled view value shape mismatch.');";
        $lines[] = $prefix . '}';
        $lines[] = $prefix . '$sink->step(' . $index . ');';
        $lines[] = $prefix . '$sink->value(' . $variable . ', \\Kongono\\ViewContract\\Output\\OutputContext::' . ('html_text' === $context->value ? 'HtmlText' : 'HtmlQuotedAttribute') . ', ' . $index . ');';

        return $lines;
    }

    /** @param array<string, string> $aliases
     * @return list<string>
     */
    private function element(ViewNode $node, int $indent, int &$operationIndex, int &$temporaryIndex, array $aliases): array
    {
        $prefix = str_repeat('    ', $indent);
        $lines = [];
        $start = $operationIndex++;
        $lines[] = $prefix . '$sink->step(' . $start . ');';
        $lines[] = $prefix . '$sink->literal(' . self::phpString('<' . ($node->name() ?? '')) . ', ' . $start . ');';
        foreach ($node->attributes() as $attribute) {
            if (ViewAttributeValueKind::Literal === $attribute->value()->kind()) {
                $index = $operationIndex++;
                $literal = ' ' . $attribute->name() . '="' . BuildGuard::htmlAttribute($attribute->value()->text() ?? '') . '"';
                $lines[] = $prefix . '$sink->literal(' . self::phpString($literal) . ', ' . $index . ');';
                continue;
            }
            $prefixIndex = $operationIndex++;
            $lines[] = $prefix . '$sink->literal(' . self::phpString(' ' . $attribute->name() . '="') . ', ' . $prefixIndex . ');';
            $valueNode = ViewNode::value($attribute->value()->path() ?? '', $attribute->sourcePointer());
            $lines = array_merge($lines, $this->value($valueNode, $indent, $operationIndex, $temporaryIndex, $aliases, OutputContext::HtmlQuotedAttribute));
            $suffixIndex = $operationIndex++;
            $lines[] = $prefix . '$sink->literal(' . self::phpString('"') . ', ' . $suffixIndex . ');';
        }
        $openEnd = $operationIndex++;
        $lines[] = $prefix . '$sink->literal(' . self::phpString('>') . ', ' . $openEnd . ');';
        $lines = array_merge($lines, $this->nodes($node->children(), $indent, $operationIndex, $temporaryIndex, $aliases));
        $close = $operationIndex++;
        $lines[] = $prefix . '$sink->literal(' . self::phpString('</' . ($node->name() ?? '') . '>') . ', ' . $close . ');';

        return $lines;
    }

    /** @param array<string, string> $aliases
     * @return list<string>
     */
    private function each(ViewNode $node, int $indent, int &$operationIndex, int &$temporaryIndex, array $aliases): array
    {
        $prefix = str_repeat('    ', $indent);
        $index = $operationIndex++;
        [$variable, $lines] = $this->reference($node->path() ?? '', $indent, $temporaryIndex, $aliases);
        $lines[] = $prefix . "if (!" . $variable . " instanceof \\Kongono\\ViewContract\\Value\\ListValue) {";
        $lines[] = $prefix . "    throw new \\LogicException('Compiled view list shape mismatch.');";
        $lines[] = $prefix . '}';
        $lines[] = $prefix . '$sink->step(' . $index . ');';
        $loop = '$loopValue_' . $temporaryIndex++;
        $count = '$loopCount_' . $temporaryIndex++;
        $lines[] = $prefix . $count . ' = 0;';
        $lines[] = $prefix . 'foreach (' . $variable . '->values() as ' . $loop . ') {';
        $lines[] = $prefix . '    if (++' . $count . ' > ' . ($node->maxItems() ?? 0) . ') {';
        $lines[] = $prefix . "        throw new \\LogicException('Compiled view iteration bound mismatch.');";
        $lines[] = $prefix . '    }';
        $lines[] = $prefix . '    $sink->step(' . $index . ');';
        $nested = $aliases;
        $nested[$node->alias() ?? ''] = $loop;
        $lines = array_merge($lines, $this->nodes($node->children(), $indent + 1, $operationIndex, $temporaryIndex, $nested));
        $lines[] = $prefix . '}';

        return $lines;
    }

    /** @param array<string, string> $aliases
     * @return array{string, list<string>}
     */
    private function reference(string $reference, int $indent, int &$temporaryIndex, array $aliases): array
    {
        $prefix = str_repeat('    ', $indent);
        $variable = '$value_' . $temporaryIndex++;
        if (str_starts_with($reference, '$.')) {
            $segments = explode('.', substr($reference, 2));
            $first = array_shift($segments);
            $lines = [$prefix . $variable . ' = $context->fields()[' . self::phpString($first) . "] ?? throw new \\LogicException('Compiled view value shape mismatch.');"];
        } else {
            $parts = explode('.', substr($reference, 1));
            $alias = array_shift($parts);
            $segments = $parts;
            $lines = [$prefix . $variable . ' = ' . ($aliases[$alias] ?? '$context') . ';'];
        }
        foreach ($segments as $segment) {
            $lines[] = $prefix . 'if (!' . $variable . ' instanceof \\Kongono\\ViewContract\\Value\\RecordValue) {';
            $lines[] = $prefix . "    throw new \\LogicException('Compiled view record shape mismatch.');";
            $lines[] = $prefix . '}';
            $lines[] = $prefix . $variable . ' = ' . $variable . '->fields()[' . self::phpString($segment) . "] ?? throw new \\LogicException('Compiled view value shape mismatch.');";
        }

        return [$variable, $lines];
    }

    private static function phpString(string $value): string
    {
        return var_export($value, true);
    }
}
