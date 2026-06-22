<?php

namespace MaxMessenger\Bot\Bundle\Event;

use MaxMessenger\Bot\Model\Response\Update;
use Symfony\Contracts\EventDispatcher\Event;

final class MaxUpdateEvent extends Event
{
    private bool $processed;

    public function __construct(private readonly Update $update)
    {
        $this->processed = false;
    }

    public function getUpdate(): Update
    {
        return $this->update;
    }

    public function isProcessed(): bool
    {
        return $this->processed;
    }

    public function setProcessed(): void
    {
        $this->processed = true;
    }
}
