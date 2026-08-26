<?php

declare(strict_types=1);

namespace Kongono\ConsoleRuntime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Console\CommandHandler;
use Kongono\Console\CommandIdentifier;

/** Binds one exact command identifier to one already-constructed explicit handler. */
#[Api(ApiStatus::Experimental)]
final readonly class CommandHandlerBinding
{
    public function __construct(
        private CommandIdentifier $identifier,
        private CommandHandler $handler,
    ) {}

    public function identifier(): CommandIdentifier
    {
        return $this->identifier;
    }

    public function handler(): CommandHandler
    {
        return $this->handler;
    }
}
