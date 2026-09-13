<?php

declare(strict_types=1);

namespace Kongono\ViewBuild\Tests\Unit\Model;

use Kongono\ViewBuild\Internal\BuildGuard;
use Kongono\ViewBuild\Source\ViewSourceParser;
use Kongono\ViewBuild\Tests\Fixture\BuildProbe;
use PHPUnit\Framework\TestCase;

final class CompiledViewModelTest extends TestCase
{
    public function testModelHasSortedViewsAndCompleteContiguousSourceMaps(): void
    {
        $model = BuildGuard::compile((new ViewSourceParser())->parse(BuildProbe::sourceBytes()));
        self::assertSame(2, $model->viewCount());
        self::assertSame(['page.alpha', 'page.zeta'], array_map(static fn($view): string => $view->identity()->value(), $model->views()));
        foreach ($model->views() as $view) {
            self::assertSame(range(0, $view->operationCount() - 1), array_map(static fn($entry): int => $entry->operationIndex(), $view->sourceMap()));
            self::assertCount($view->operationCount(), $view->sourceMap());
        }
        self::assertTrue($model->equals(BuildGuard::modelFromData(BuildGuard::modelData($model))));
    }
}
