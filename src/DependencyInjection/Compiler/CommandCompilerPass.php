<?php

declare(strict_types=1);

namespace MaxMessenger\Bot\Bundle\DependencyInjection\Compiler;

use MaxMessenger\Bot\Bundle\Contract\CommandInterface;
use MaxMessenger\Bot\Bundle\Service\CommandRegistry;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;

final class CommandCompilerPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    public const COMMAND_TAG = 'max_bot.command';

    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(CommandRegistry::class)) {
            return;
        }

        $listenerDefinition = $container->findDefinition(CommandRegistry::class);

        foreach ($this->findAndSortTaggedServices(new TaggedIteratorArgument(self::COMMAND_TAG), $container) as $commandReference) {
            $commandDefinition = $container->findDefinition((string) $commandReference);
            $class = $commandDefinition->getClass();

            if ($class === null) {
                throw new LogicException(sprintf('Unknown class for service "%s".', (string) $commandReference));
            }

            $interfaces = class_implements($class);

            if (!isset($interfaces[CommandInterface::class])) {
                throw new LogicException(sprintf(
                    'Service "%s" is tagged with "%s", but class "%s" must implement "%s".',
                    (string) $commandReference,
                    self::COMMAND_TAG,
                    $class,
                    CommandInterface::class,
                ));
            }

            $listenerDefinition->addMethodCall('addCommand', [$commandReference]);
        }
    }
}
