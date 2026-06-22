<?php

declare(strict_types=1);

namespace MaxMessenger\Bot\Bundle\Max\Command;

use MaxMessenger\Bot\Bundle\Contract\CommandInterface;
use MaxMessenger\Bot\Model\Response\MessageCreatedUpdate;
use MaxMessenger\Bot\Model\Response\Update;

abstract class AbstractCommand implements CommandInterface
{
    /**
     * RegExp for bot commands
     */
    public const REGEXP = '/^([^\s@]+)(@\S+)?\s?(.*)$/';

    public function isApplicable(Update $update): bool
    {
        if (!$update instanceof MessageCreatedUpdate) {
            return false;
        }

        if ($this->matchCommandName((string) $update->getMessage()->getText(), $this->getCommandName())) {
            return true;
        }

        return false;
    }

    abstract protected function getCommandName(): string;


    protected function matchCommandName(string $text, string $name): bool
    {
        preg_match(self::REGEXP, $text, $matches);

        return !empty($matches) && $matches[1] == $name;
    }
}
