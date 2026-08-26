<?php

declare(strict_types=1);

namespace Kongono\ConsolePresentation\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Console\CommandResult;
use Kongono\Console\CommandResultField;
use Kongono\Console\CommandResultFieldDisclosure;
use Kongono\Console\CommandResultRecord;
use Kongono\ConsolePresentation\Exception\InvalidConsolePresentationException;

/** Projects only Safe fields from the one supported bounded result record. */
#[Api(ApiStatus::Internal)]
final readonly class CommandResultProjection
{
    /** @var list<CommandResultField> */
    private array $safeFields;

    /** @param list<CommandResultField> $safeFields */
    private function __construct(array $safeFields, private int $protectedFieldCount)
    {
        $this->safeFields = $safeFields;
    }

    public static function fromResult(CommandResult $result): self
    {
        $data = $result->data();
        if (null === $data) {
            return new self([], 0);
        }
        if (!$data instanceof CommandResultRecord) {
            throw InvalidConsolePresentationException::unsupportedResultData();
        }

        $safe = [];
        $protected = 0;
        foreach ($data->fields() as $field) {
            if (CommandResultFieldDisclosure::Safe === $field->disclosure()) {
                $safe[] = $field;
            } else {
                ++$protected;
            }
        }

        return new self($safe, $protected);
    }

    /** @return list<CommandResultField> */
    public function safeFields(): array
    {
        return $this->safeFields;
    }

    public function protectedFieldCount(): int
    {
        return $this->protectedFieldCount;
    }
}
