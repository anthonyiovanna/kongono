<?php

declare(strict_types=1);

namespace Kongono\Database\Tests;

use Closure;
use Kongono\Database\ConnectionInterface;
use Kongono\Database\DatabaseException;
use Kongono\Database\Dialect;
use Kongono\Database\QueryBuilder;
use Kongono\Database\Schema\Blueprint;
use Kongono\Database\Schema\Schema;
use PHPUnit\Framework\TestCase;

final readonly class NoteRowDto
{
    public function __construct(
        public int $id,
        public string $title,
        public string $createdAt,
    ) {}
}

final class QueryBuilderTest extends TestCase
{
    public function testCompilesFullSelectWithJoinWhereGroupByHavingOrderAndPagination(): void
    {
        $sql = (new QueryBuilder('posts', Dialect::Postgres))
            ->select('posts.author_id', 'users.name AS author_name', 'COUNT(*) AS total')
            ->leftJoin('users', 'users.id', '=', 'posts.author_id')
            ->where('posts.published', '=', true)
            ->whereIn('posts.category', ['php', 'ai'])
            ->whereNotNull('posts.published_at')
            ->groupBy('posts.author_id', 'users.name')
            ->having('COUNT(*)', '>=', 2)
            ->orderBy('posts.author_id', 'DESC')
            ->limit(10)
            ->offset(20)
            ->toSelectSql();

        self::assertSame(
            'SELECT "posts"."author_id", "users"."name" AS "author_name", COUNT(*) AS "total" '
            . 'FROM "posts" '
            . 'LEFT JOIN "users" ON "users"."id" = "posts"."author_id" '
            . 'WHERE "posts"."published" = ? AND "posts"."category" IN (?, ?) AND "posts"."published_at" IS NOT NULL '
            . 'GROUP BY "posts"."author_id", "users"."name" '
            . 'HAVING COUNT(*) >= ? '
            . 'ORDER BY "posts"."author_id" DESC '
            . 'LIMIT 10 OFFSET 20',
            $sql->query,
        );
        self::assertSame([true, 'php', 'ai', 2], $sql->bindings);
    }

    public function testCompilesInsertReturningAndRefusesUnscopedUpdateDelete(): void
    {
        $insert = (new QueryBuilder('notes', Dialect::Postgres))
            ->toInsertSql(['title' => 'Hello', 'body' => 'World'], ['id', 'title']);

        self::assertSame('INSERT INTO "notes" ("title", "body") VALUES (?, ?) RETURNING "id", "title"', $insert->query);
        self::assertSame(['Hello', 'World'], $insert->bindings);

        $update = (new QueryBuilder('notes', Dialect::Mysql))
            ->where('id', '=', 7)
            ->toUpdateSql(['title' => 'Updated']);
        self::assertSame('UPDATE `notes` SET `title` = ? WHERE `id` = ?', $update->query);
        self::assertSame(['Updated', 7], $update->bindings);

        $this->expectException(DatabaseException::class);
        (new QueryBuilder('notes', Dialect::Postgres))->toDeleteSql();
    }

    public function testRejectsUnsafeSqlIdentifiers(): void
    {
        $this->expectException(DatabaseException::class);
        (new QueryBuilder('users; DROP TABLE users;--', Dialect::Postgres))->toSelectSql();
    }

    public function testSchemaBlueprintPaginationAndDtoHydration(): void
    {
        $ddl = [];
        $fakeDb = new class ($ddl) implements ConnectionInterface {
            /** @param list<string> $ddl */
            public function __construct(public array &$ddl) {}

            public function dialect(): Dialect
            {
                return Dialect::Postgres;
            }

            public function query(string $sql, array $bindings = []): array
            {
                return [
                    ['id' => 1, 'title' => 'First', 'created_at' => '2026-11-20T00:00:00Z'],
                    ['id' => 2, 'title' => 'Second', 'created_at' => '2026-11-20T01:00:00Z'],
                ];
            }

            public function first(string $sql, array $bindings = []): ?array
            {
                if (str_contains($sql, 'aggregate_count')) {
                    return ['aggregate_count' => 5];
                }

                return ['id' => 1, 'title' => 'First', 'created_at' => '2026-11-20T00:00:00Z'];
            }

            public function execute(string $sql, array $bindings = []): int
            {
                $this->ddl[] = $sql;

                return 1;
            }

            public function table(string $table, ?string $alias = null): QueryBuilder
            {
                return new QueryBuilder($table, Dialect::Postgres, $this, $alias);
            }

            public function transaction(Closure $callback): mixed
            {
                return $callback($this);
            }
        };

        Schema::create($fakeDb, 'articles', static function (Blueprint $table): void {
            $table->id();
            $table->string('title', 120);
            $table->boolean('published')->default(false);
            $table->timestamps();
            $table->index(['published']);
        });

        self::assertCount(2, $ddl);
        self::assertStringContainsString('CREATE TABLE IF NOT EXISTS "articles" ("id" BIGSERIAL PRIMARY KEY', $ddl[0]);
        self::assertStringContainsString('CREATE INDEX IF NOT EXISTS "idx_articles_published"', $ddl[1]);

        $page = $fakeDb->table('articles')->paginate(page: 1, perPage: 2);
        self::assertSame(5, $page->total);
        self::assertSame(3, $page->lastPage());
        self::assertTrue($page->hasMore());
        self::assertCount(2, $page->items);

        $dtos = $fakeDb->table('articles')->getAs(NoteRowDto::class);
        self::assertCount(2, $dtos);
        self::assertSame('First', $dtos[0]->title);
        self::assertSame('2026-11-20T00:00:00Z', $dtos[0]->createdAt);
    }
}
