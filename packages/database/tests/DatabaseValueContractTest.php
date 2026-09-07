<?php

declare(strict_types=1);

namespace Kongono\Database\Tests;

use Kongono\Database\CommandResult;
use Kongono\Database\DatabaseCapabilities;
use Kongono\Database\DatabaseCapability;
use Kongono\Database\DatabaseColumn;
use Kongono\Database\DatabaseParameter;
use Kongono\Database\DatabaseRow;
use Kongono\Database\DatabaseStatement;
use Kongono\Database\DatabaseValue;
use Kongono\Database\DatabaseValueKind;
use Kongono\Database\Diagnostic\DatabaseDiagnostic;
use Kongono\Database\Exception\DatabaseResultException;
use Kongono\Database\Exception\ExceptionInterface;
use Kongono\Database\Exception\InvalidDatabaseValueException;
use Kongono\Database\ResultExpectation;
use Kongono\Database\TransactionAccess;
use Kongono\Database\TransactionIsolation;
use Kongono\Database\TransactionOptions;
use Kongono\Database\TransactionState;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DatabaseValueContractTest extends TestCase
{
    public function testStatementPreservesExactSqlParametersAndExpectation(): void
    {
        $parameters = [
            new DatabaseParameter('Alpha', DatabaseValue::integer(PHP_INT_MIN)),
            new DatabaseParameter('_beta9', DatabaseValue::text('value')),
        ];
        $statement = new DatabaseStatement(' SELECT :Alpha, :_beta9 ', $parameters, ResultExpectation::Rows);
        $parameters[] = new DatabaseParameter('later', DatabaseValue::null());

        self::assertSame(' SELECT :Alpha, :_beta9 ', $statement->sql());
        self::assertSame(['Alpha', '_beta9'], array_map(static fn(DatabaseParameter $parameter): string => $parameter->name(), $statement->parameters()));
        self::assertSame(ResultExpectation::Rows, $statement->expectation());
    }

    #[DataProvider('invalidSqlProvider')]
    public function testStatementRejectsInvalidSql(string $sql): void
    {
        $this->expectException(InvalidDatabaseValueException::class);
        new DatabaseStatement($sql, [], ResultExpectation::Rows);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidSqlProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'nul' => ["SELECT\0 1"];
        yield 'invalid utf8' => ["\xC3\x28"];
        yield 'above maximum' => [str_repeat('x', 1_048_577)];
    }

    public function testStatementAcceptsSqlAtMaximumAndRejectsParameterShapeAndDuplicates(): void
    {
        self::assertSame(1_048_576, strlen((new DatabaseStatement(str_repeat('x', 1_048_576), [], ResultExpectation::Completion))->sql()));

        foreach (
            [
                [1 => new DatabaseParameter('a', DatabaseValue::null())],
                [new DatabaseParameter('a', DatabaseValue::null()), new DatabaseParameter('a', DatabaseValue::integer(1))],
                array_fill(0, 65_536, new \stdClass()),
            ] as $parameters
        ) {
            try {
                new DatabaseStatement('x', $parameters, ResultExpectation::Rows);
                self::fail('Invalid parameter collection was accepted.');
            } catch (InvalidDatabaseValueException) {
            }
        }
    }

    public function testStatementAcceptsParameterCountAtMaximum(): void
    {
        $parameters = [];
        for ($index = 0; $index < 65_535; ++$index) {
            $parameters[] = new DatabaseParameter('p' . $index, DatabaseValue::null());
        }

        self::assertCount(65_535, (new DatabaseStatement('SELECT 1', $parameters, ResultExpectation::Rows))->parameters());
    }

    #[DataProvider('invalidNameProvider')]
    public function testParameterRejectsInvalidNames(string $name): void
    {
        $this->expectException(InvalidDatabaseValueException::class);
        new DatabaseParameter($name, DatabaseValue::null());
    }

    /** @return iterable<string, array{string}> */
    public static function invalidNameProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'colon' => [':name'];
        yield 'numeric first' => ['1name'];
        yield 'hyphen' => ['a-b'];
        yield 'non ascii' => ['café'];
        yield 'above bound' => ['a' . str_repeat('b', 128)];
    }

    public function testAllValueFactoriesPreserveExactKindsAndDetachedScalars(): void
    {
        $values = [
            [DatabaseValue::null(), DatabaseValueKind::Null, null],
            [DatabaseValue::boolean(true), DatabaseValueKind::Boolean, true],
            [DatabaseValue::integer(PHP_INT_MAX), DatabaseValueKind::Integer, PHP_INT_MAX],
            [DatabaseValue::float(1.25), DatabaseValueKind::Float, 1.25],
            [DatabaseValue::text('text'), DatabaseValueKind::Text, 'text'],
            [DatabaseValue::binary("\0\xFF"), DatabaseValueKind::Binary, "\0\xFF"],
        ];
        foreach ($values as [$value, $kind, $scalar]) {
            self::assertSame($kind, $value->kind());
            self::assertSame($scalar, $value->value());
        }
    }

    public function testParameterNameAndRowColumnCountAcceptExactMaximums(): void
    {
        self::assertSame(128, strlen((new DatabaseParameter('a' . str_repeat('b', 127), DatabaseValue::null()))->name()));

        $columns = [];
        $values = [];
        for ($index = 0; $index < 4_096; ++$index) {
            $columns[] = new DatabaseColumn($index, 'c' . $index);
            $values[] = DatabaseValue::integer($index);
        }
        self::assertCount(4_096, (new DatabaseRow($columns, $values))->columns());

        $columns[] = new DatabaseColumn(4_096, 'above');
        $values[] = DatabaseValue::integer(4_096);
        $this->expectException(InvalidDatabaseValueException::class);
        new DatabaseRow($columns, $values);
    }

    public function testValueBoundsRejectNonFiniteInvalidAndOversizedValues(): void
    {
        foreach ([NAN, INF, -INF] as $float) {
            try {
                DatabaseValue::float($float);
                self::fail('Non-finite float was accepted.');
            } catch (InvalidDatabaseValueException) {
            }
        }

        try {
            DatabaseValue::text("\xC3\x28");
            self::fail('Invalid or oversized bytes were accepted.');
        } catch (InvalidDatabaseValueException) {
        }

        $oversizedText = str_repeat('x', 16_777_217);
        try {
            DatabaseValue::text($oversizedText);
            self::fail('Invalid or oversized bytes were accepted.');
        } catch (InvalidDatabaseValueException) {
        }
        unset($oversizedText);

        $oversizedBinary = str_repeat("\0", 16_777_217);
        try {
            DatabaseValue::binary($oversizedBinary);
            self::fail('Invalid or oversized bytes were accepted.');
        } catch (InvalidDatabaseValueException) {
        }
        unset($oversizedBinary);

        $textInput = str_repeat('x', 16_777_216);
        $text = DatabaseValue::text($textInput)->value();
        unset($textInput);
        self::assertIsString($text);
        self::assertSame(16_777_216, strlen($text));
        unset($text);

        $binaryInput = str_repeat("\0", 16_777_216);
        $binary = DatabaseValue::binary($binaryInput)->value();
        unset($binaryInput);
        self::assertIsString($binary);
        self::assertSame(16_777_216, strlen($binary));
        unset($binary);
    }

    public function testColumnsRowsAndExactLabelAccessPreserveOrderAndDuplicates(): void
    {
        $columns = [new DatabaseColumn(0, 'same'), new DatabaseColumn(1, 'Same'), new DatabaseColumn(2, 'same')];
        $values = [DatabaseValue::integer(1), DatabaseValue::integer(2), DatabaseValue::integer(3)];
        $row = new DatabaseRow($columns, $values);

        self::assertSame(2, $row->at(1)->value());
        self::assertSame(2, $row->named('Same')->value());
        self::assertSame(['same', 'Same', 'same'], array_map(static fn(DatabaseColumn $column): string => $column->label(), $row->columns()));

        foreach ([static fn(): DatabaseValue => $row->at(-1), static fn(): DatabaseValue => $row->at(3), static fn(): DatabaseValue => $row->named('missing'), static fn(): DatabaseValue => $row->named('same')] as $access) {
            try {
                $access();
                self::fail('Invalid row access was accepted.');
            } catch (DatabaseResultException) {
            }
        }
    }

    public function testColumnRowAndCommandValidationBoundsAreVisible(): void
    {
        foreach ([static fn() => new DatabaseColumn(-1, 'x'), static fn() => new DatabaseColumn(0, ''), static fn() => new DatabaseColumn(0, "x\0"), static fn() => new DatabaseColumn(0, str_repeat('x', 1_025)), static fn() => new CommandResult(-1)] as $construct) {
            try {
                $construct();
                self::fail('Invalid result value was accepted.');
            } catch (InvalidDatabaseValueException) {
            }
        }
        self::assertSame(1_024, strlen((new DatabaseColumn(0, str_repeat('x', 1_024)))->label()));
        self::assertNull((new CommandResult(null))->affectedRows());
        self::assertSame(0, (new CommandResult(0))->affectedRows());
    }

    public function testCapabilitiesCanonicalizeAndRejectDuplicatesOrNonCapabilities(): void
    {
        $capabilities = new DatabaseCapabilities([DatabaseCapability::AffectedRows, DatabaseCapability::Transactions]);
        self::assertSame([DatabaseCapability::Transactions, DatabaseCapability::AffectedRows], $capabilities->all());
        self::assertTrue($capabilities->has(DatabaseCapability::Transactions));
        self::assertFalse($capabilities->has(DatabaseCapability::ReadOnlyTransactions));

        foreach ([[DatabaseCapability::Transactions, DatabaseCapability::Transactions], [new \stdClass()]] as $invalid) {
            try {
                new DatabaseCapabilities($invalid);
                self::fail('Invalid capability set was accepted.');
            } catch (InvalidDatabaseValueException) {
            }
        }
    }

    public function testClosedEnumsAndTransactionDefaultsAreExact(): void
    {
        self::assertSame(['Null', 'Boolean', 'Integer', 'Float', 'Text', 'Binary'], array_column(DatabaseValueKind::cases(), 'name'));
        self::assertSame(['Rows', 'Completion'], array_column(ResultExpectation::cases(), 'name'));
        self::assertSame(['Transactions', 'ReadOnlyTransactions', 'IsolationReadCommitted', 'IsolationRepeatableRead', 'IsolationSerializable', 'AffectedRows'], array_column(DatabaseCapability::cases(), 'name'));
        self::assertSame(['Default', 'ReadCommitted', 'RepeatableRead', 'Serializable'], array_column(TransactionIsolation::cases(), 'name'));
        self::assertSame(['ReadWrite', 'ReadOnly'], array_column(TransactionAccess::cases(), 'name'));
        self::assertSame(['Active', 'Committed', 'RolledBack', 'Failed'], array_column(TransactionState::cases(), 'name'));
        $options = new TransactionOptions();
        self::assertSame(TransactionIsolation::Default, $options->isolation());
        self::assertSame(TransactionAccess::ReadWrite, $options->access());
    }

    public function testDiagnosticsAreClosedUniqueActionableAndNonDisclosing(): void
    {
        $identifiers = array_column(DatabaseDiagnostic::cases(), 'value');
        self::assertSame(range(1001, 1011), array_map(static fn(string $identifier): int => (int) substr($identifier, -4), $identifiers));
        self::assertCount(11, array_unique($identifiers));
        $sentinel = 'KONGONO_SECRET_SENTINEL';
        try {
            new DatabaseStatement($sentinel . "\0", [], ResultExpectation::Rows);
            self::fail('Invalid statement was accepted.');
        } catch (ExceptionInterface $exception) {
            $presentation = $exception->getMessage() . (string) $exception . $exception->diagnosticId() . $exception->diagnosticTitle() . $exception->diagnosticRemediation();
            foreach ($exception->diagnosticContext()->fields() as $field) {
                $presentation .= (string) $field->value();
            }
            self::assertStringNotContainsString($sentinel, $presentation);
            self::assertNotSame('', $exception->diagnosticRemediation());
        }
    }
}
