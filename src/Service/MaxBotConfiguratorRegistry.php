<?php

declare(strict_types=1);

namespace MaxMessenger\Bot\Bundle\Service;

use MaxMessenger\Bot\Bundle\Contract\MaxBotConfiguratorInterface;
use MaxMessenger\Bot\MaxBot;

final readonly class MaxBotConfiguratorRegistry
{
    /**
     * @param iterable<MaxBotConfiguratorInterface> $configurators
     */
    public function __construct(
        private iterable $configurators,
    ) {
    }

    public function configure(MaxBot $maxBot): void
    {
        foreach ($this->configurators as $configurator) {
            $configurator->configure($maxBot);
        }
    }
}
