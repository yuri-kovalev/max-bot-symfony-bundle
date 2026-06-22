<?php

declare(strict_types=1);

namespace MaxMessenger\Bot\Bundle\Contract;

use MaxMessenger\Bot\MaxBot;

interface MaxBotConfiguratorInterface
{
    public function configure(MaxBot $maxBot): void;
}
