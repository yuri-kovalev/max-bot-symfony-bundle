<?php

declare(strict_types=1);

namespace MaxMessenger\Bot\Bundle\Service;

use MaxMessenger\Bot\Bundle\Contract\CommandInterface;

final class CommandRegistry
{
    /**
     * @var list<CommandInterface>
     */
    private array $commands = [];

    public function addCommand(CommandInterface $command): void
    {
        $this->commands[] = $command;
    }

    /**
     * @return CommandInterface[]
     */
    public function getCommands(): array
    {
        return $this->commands;
    }
}
