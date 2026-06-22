<?php

declare(strict_types=1);

namespace MaxMessenger\Bot\Bundle\Service;

use MaxMessenger\Bot\MaxApiClient;
use MaxMessenger\Bot\MaxBot;

final class MaxBotFactory
{
    private ?MaxBot $maxBot = null;

    public function __construct(
        private readonly MaxApiClient $maxApiClient,
        private readonly MaxBotConfiguratorRegistry $configuratorRegistry,
        private readonly ?string $webhookSecret,
    ) {
    }

    public function create(): MaxBot
    {
        if (null !== $this->maxBot) {
            return $this->maxBot;
        }

        $maxBot = new MaxBot($this->maxApiClient, $this->webhookSecret);
        $this->configuratorRegistry->configure($maxBot);

        $this->maxBot = $maxBot;

        return $maxBot;
    }
}
