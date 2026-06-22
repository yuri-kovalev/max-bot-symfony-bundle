<?php

namespace MaxMessenger\Bot\Bundle\EventListener;

use MaxMessenger\Bot\Bundle\Event\MaxUpdateEvent;
use MaxMessenger\Bot\Bundle\Service\CommandRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class CommandListener implements EventSubscriberInterface
{
    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            MaxUpdateEvent::class => 'onUpdate',
        ];
    }

    public function __construct(
        private readonly CommandRegistry $commandRegistry
    )
    {
    }

    public function onUpdate(MaxUpdateEvent $event): void
    {
        foreach ($this->commandRegistry->getCommands() as $command) {
            if (!$command->isApplicable($event->getUpdate())) {
                continue;
            }

            $command->execute($event->getUpdate());
            $event->setProcessed();

            break;
        }
    }
}
