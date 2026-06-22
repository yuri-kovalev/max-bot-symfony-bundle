<?php

declare(strict_types=1);

namespace MaxMessenger\Bot\Bundle\Contract;

use MaxMessenger\Bot\Model\Response\Update;

interface CommandInterface
{
    public function execute(Update $update): void;

    public function isApplicable(Update $update): bool;
}
