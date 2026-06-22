<?php

namespace MaxMessenger\Bot\Bundle\Max;

use MaxMessenger\Bot\Bundle\Event\MaxUpdateEvent;
use MaxMessenger\Bot\MaxBot;
use MaxMessenger\Bot\Model\Response\Update;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final readonly class MaxUpdateHandler
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function handle(MaxBot $maxBot, Update $update): bool
    {
        $event = new MaxUpdateEvent($update);
        $this->eventDispatcher->dispatch($event);

        return $maxBot->handleUpdate($update);
    }
}
