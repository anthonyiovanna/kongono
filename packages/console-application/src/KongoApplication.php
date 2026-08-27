<?php

declare(strict_types=1);

namespace Kongono\ConsoleApplication;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Console\CommandFailure;
use Kongono\Console\CommandFailureCategory;
use Kongono\Console\CommandIdentifier;
use Kongono\Console\CommandResult;
use Kongono\Console\CommandSpecificationSet;
use Kongono\Console\CommandTokenParser;
use Kongono\ConsoleApplication\Diagnostic\ConsoleApplicationDiagnostic;
use Kongono\ConsoleApplication\Process\NeverCancelled;
use Kongono\ConsolePresentation\CommandExitStatus;
use Kongono\ConsolePresentation\CommandOutputPort;
use Kongono\ConsolePresentation\CommandPresentation;
use Kongono\ConsolePresentation\CommandPresentationAdapter;
use Kongono\ConsolePresentation\Exception\CommandOutputException;
use Kongono\ConsolePresentation\HumanCommandPresenter;
use Kongono\ConsolePresentation\MachineCommandPresenter;
use Kongono\ConsoleRuntime\CommandDispatcher;
use Throwable;

/** Executes one exact request through parse, dispatch, presentation, and delivery. */
#[Api(ApiStatus::Experimental)]
final readonly class KongoApplication
{
    public function __construct(
        private CommandSpecificationSet $specifications,
        private CommandTokenParser $parser,
        private CommandDispatcher $dispatcher,
        private HumanCommandPresenter $humanPresenter,
        private MachineCommandPresenter $machinePresenter,
        private CommandPresentationAdapter $presentationAdapter,
    ) {}

    public function run(KongoProcessRequest $request, CommandOutputPort $stdout, CommandOutputPort $stderr): int
    {
        try {
            $presentation = $this->presentation($request);

            return $this->presentationAdapter->deliver($presentation, $stdout, $stderr);
        } catch (CommandOutputException) {
            return CommandExitStatus::Infrastructure->value;
        } catch (Throwable) {
            try {
                return $this->presentationAdapter->deliver(
                    $this->presentResult($request->isMachine(), $this->infrastructureFailure()),
                    $stdout,
                    $stderr,
                );
            } catch (Throwable) {
                return CommandExitStatus::Infrastructure->value;
            }
        }
    }

    private function presentation(KongoProcessRequest $request): CommandPresentation
    {
        if (!$request->isValid()) {
            return $this->presentResult($request->isMachine(), CommandResult::rejected(new CommandFailure(
                CommandFailureCategory::InvalidInput,
                ConsoleApplicationDiagnostic::InvalidProcessRequest->value,
            )));
        }
        if ($request->requestsFullHelp()) {
            return $request->isMachine()
                ? $this->machinePresenter->presentHelp($this->specifications)
                : $this->humanPresenter->presentHelp($this->specifications);
        }
        if ($request->requestsCommandHelp()) {
            $identifier = $request->helpCommandIdentifier();
            $specification = null === $identifier
                ? null
                : $this->specifications->command(new CommandIdentifier($identifier));
            if (null === $specification) {
                return $this->presentResult($request->isMachine(), $this->infrastructureFailure());
            }

            return $request->isMachine()
                ? $this->machinePresenter->presentCommandHelp($specification)
                : $this->humanPresenter->presentCommandHelp($specification);
        }

        $parsed = $this->parser->parse($this->specifications, $request->commandTokens());
        if (!$parsed->isAccepted()) {
            $failure = $parsed->failure();

            return $this->presentResult(
                $request->isMachine(),
                null === $failure ? $this->infrastructureFailure() : CommandResult::rejected($failure),
            );
        }
        $invocation = $parsed->invocation();
        if (null === $invocation) {
            return $this->presentResult($request->isMachine(), $this->infrastructureFailure());
        }
        try {
            $result = $this->dispatcher->dispatch($invocation, new NeverCancelled());
        } catch (Throwable) {
            $result = $this->infrastructureFailure();
        }

        return $this->presentResult($request->isMachine(), $result);
    }

    private function presentResult(bool $machine, CommandResult $result): CommandPresentation
    {
        return $machine
            ? $this->machinePresenter->presentResult($result)
            : $this->humanPresenter->presentResult($result);
    }

    private function infrastructureFailure(): CommandResult
    {
        return CommandResult::failed(new CommandFailure(
            CommandFailureCategory::Infrastructure,
            ConsoleApplicationDiagnostic::ApplicationFailure->value,
        ));
    }
}
