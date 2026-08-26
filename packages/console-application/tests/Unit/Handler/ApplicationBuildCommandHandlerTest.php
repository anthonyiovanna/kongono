<?php

declare(strict_types=1);

namespace Kongono\ConsoleApplication\Tests\Unit\Handler;

use Kongono\Composition\Workflow\ApplicationBuildWorkflow;
use Kongono\Console\CommandDefinition;
use Kongono\Console\CommandExecutionIntent;
use Kongono\Console\CommandIdentifier;
use Kongono\Console\CommandInput;
use Kongono\Console\CommandInputName;
use Kongono\Console\CommandInputSensitivity;
use Kongono\Console\CommandInputValue;
use Kongono\Console\CommandInteractionMode;
use Kongono\Console\CommandInteractionPolicy;
use Kongono\Console\CommandInvocation;
use Kongono\Console\CommandMutationPolicy;
use Kongono\Console\CommandResultRecord;
use Kongono\Console\CommandSummary;
use Kongono\ConsoleApplication\Handler\ApplicationBuildCommandHandler;
use Kongono\ConsoleApplication\KongoCommand;
use Kongono\ConsoleRuntime\CommandExecutionOperation;
use PHPUnit\Framework\TestCase;

final class ApplicationBuildCommandHandlerTest extends TestCase
{
    private static string $root;
    private static string $output;

    public static function setUpBeforeClass(): void
    {
        self::$root = self::temporaryDirectory();
        self::$output = self::$root . '/var/build';
        self::assertTrue(mkdir(self::$root . '/src', 0700, true));
        self::assertTrue(mkdir(self::$output, 0700, true));
        self::write(self::$root . '/src/Input.php', "<?php\n\ndeclare(strict_types=1);\n\nnamespace Phase8GHandlerFixture;\n\nfinal readonly class Input {}\n");
        self::write(self::$root . '/src/Root.php', "<?php\n\ndeclare(strict_types=1);\n\nnamespace Phase8GHandlerFixture;\n\nfinal readonly class Root { public function __construct(public Input \$input) {} }\n");
        require self::$root . '/src/Input.php';
        require self::$root . '/src/Root.php';
        self::write(self::$root . '/composer.json', "{\"name\":\"example/phase8g-handler\"}\n");
        self::write(self::$root . '/composer.lock', "{\"packages\":[]}\n");
        self::write(self::$root . '/kongono.composition.json', "{\"schema\":\"kongono.composition\",\"version\":1,\"inputs\":[{\"contract\":\"Phase8GHandlerFixture\\\\Input\"}],\"services\":[{\"class\":\"Phase8GHandlerFixture\\\\Root\",\"lifetime\":\"application\",\"arguments\":[{\"parameter\":\"input\",\"input\":\"Phase8GHandlerFixture\\\\Input\"}],\"cleanup\":null}],\"bindings\":[],\"roots\":[{\"kind\":\"application\",\"contract\":\"Phase8GHandlerFixture\\\\Root\"}]}\n");
    }

    public static function tearDownAfterClass(): void
    {
        self::removeTree(self::$root);
    }

    public function testPlanIsNonMutatingAndApplyPublishesExactlyTwoCurrentOutputs(): void
    {
        $handler = new ApplicationBuildCommandHandler(new ApplicationBuildWorkflow());
        $plan = $handler->execute(self::operation(CommandExecutionIntent::Plan));

        self::assertSame('succeeded', $plan->outcome()->value);
        self::assertSame([], self::files(self::$output));
        $planData = $plan->data();
        self::assertInstanceOf(CommandResultRecord::class, $planData);
        self::assertTrue($planData->field(new \Kongono\Console\CommandResultFieldName('publication_required'))?->value()->value());

        $apply = $handler->execute(self::operation(CommandExecutionIntent::Apply));
        self::assertSame('succeeded', $apply->outcome()->value);
        self::assertCount(2, self::files(self::$output));
        $applyData = $apply->data();
        self::assertInstanceOf(CommandResultRecord::class, $applyData);
        self::assertFalse($applyData->field(new \Kongono\Console\CommandResultFieldName('publication_required'))?->value()->value());
        self::assertSame(
            $planData->field(new \Kongono\Console\CommandResultFieldName('artifact_sha256'))?->value()->value(),
            $applyData->field(new \Kongono\Console\CommandResultFieldName('artifact_sha256'))?->value()->value(),
        );

        $second = $handler->execute(self::operation(CommandExecutionIntent::Apply));
        self::assertSame('succeeded', $second->outcome()->value);
        self::assertCount(2, self::files(self::$output));
    }

    public function testObserveAndInvalidPathsRejectWithoutDisclosure(): void
    {
        $handler = new ApplicationBuildCommandHandler(new ApplicationBuildWorkflow());
        $observe = $handler->execute(self::operation(CommandExecutionIntent::Observe, CommandMutationPolicy::ReadOnly));
        self::assertSame('rejected', $observe->outcome()->value);
        self::assertSame('invalid_input', $observe->failure()?->category()->value);

        $invalid = $handler->execute(self::operation(CommandExecutionIntent::Plan, applicationRoot: 'C:/sentinel-secret-does-not-exist'));
        self::assertSame('rejected', $invalid->outcome()->value);
        self::assertSame('precondition', $invalid->failure()?->category()->value);
        self::assertStringNotContainsString('sentinel', json_encode($invalid, JSON_THROW_ON_ERROR));
    }

    private static function operation(
        CommandExecutionIntent $intent,
        CommandMutationPolicy $mutation = CommandMutationPolicy::MutationCapable,
        ?string $applicationRoot = null,
    ): CommandExecutionOperation {
        $definition = new CommandDefinition(
            new CommandIdentifier(KongoCommand::ApplicationBuild->value),
            new CommandSummary('Build exact current application composition outputs'),
            CommandInteractionPolicy::None,
            $mutation,
        );
        $invocation = new CommandInvocation($definition, [
            new CommandInput(new CommandInputName('application-root'), new CommandInputValue($applicationRoot ?? self::$root), CommandInputSensitivity::Ordinary),
            new CommandInput(new CommandInputName('output-directory'), new CommandInputValue(self::$output), CommandInputSensitivity::Ordinary),
        ], $intent, CommandInteractionMode::Disabled);

        return new CommandExecutionOperation($invocation, new \Kongono\ConsoleApplication\Process\NeverCancelled());
    }

    /** @return list<string> */
    private static function files(string $directory): array
    {
        $files = glob($directory . '/*');

        return false === $files ? [] : $files;
    }

    private static function temporaryDirectory(): string
    {
        $path = str_replace('\\', '/', sys_get_temp_dir()) . '/kongono-phase8g-handler-' . bin2hex(random_bytes(12));
        self::assertTrue(mkdir($path, 0700, true));

        return $path;
    }

    private static function write(string $path, string $bytes): void
    {
        self::assertSame(strlen($bytes), file_put_contents($path, $bytes));
    }

    private static function removeTree(string $directory): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $entry) {
            if (!$entry instanceof \SplFileInfo) {
                continue;
            }
            $entry->isDir() ? self::assertTrue(rmdir($entry->getPathname())) : self::assertTrue(unlink($entry->getPathname()));
        }
        self::assertTrue(rmdir($directory));
    }
}
