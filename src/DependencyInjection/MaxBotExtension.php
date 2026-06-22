<?php

declare(strict_types=1);

namespace MaxMessenger\Bot\Bundle\DependencyInjection;

use MaxMessenger\Bot\Bundle\Attribute\AsMaxBotConfigurator;
use MaxMessenger\Bot\Bundle\Attribute\AsMaxBotCommand;
use MaxMessenger\Bot\Bundle\Contract\CommandInterface;
use MaxMessenger\Bot\Bundle\Contract\MaxBotConfiguratorInterface;
use MaxMessenger\Bot\Bundle\DependencyInjection\Compiler\CommandCompilerPass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

final class MaxBotExtension extends Extension
{
    public const CONFIGURATOR_TAG = 'max_bot.configurator';

    #[\Override]
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.php');

        $container->setParameter('max_bot.access_token', $config['access_token']);
        $container->setParameter('max_bot.webhook_secret', $config['webhook_secret']);
        $container->setParameter('max_bot.base_url', $config['base_url']);
        $container->setParameter('max_bot.connect_timeout', $config['connect_timeout']);
        $container->setParameter('max_bot.timeout', $config['timeout']);
        $container->setParameter('max_bot.retry_attempts', $config['retry_attempts']);

        $container->registerForAutoconfiguration(MaxBotConfiguratorInterface::class)
            ->addTag(self::CONFIGURATOR_TAG);

        $container->registerAttributeForAutoconfiguration(
            AsMaxBotConfigurator::class,
            static function (ChildDefinition $definition, AsMaxBotConfigurator $attribute): void {
                $definition->addTag(self::CONFIGURATOR_TAG);
            },
        );

        $container->registerForAutoconfiguration(CommandInterface::class)
            ->addTag(CommandCompilerPass::COMMAND_TAG);

        $container->registerAttributeForAutoconfiguration(
            AsMaxBotCommand::class,
            static function (ChildDefinition $definition, AsMaxBotCommand $attribute): void {
                $definition->addTag(CommandCompilerPass::COMMAND_TAG, [
                    'priority' => $attribute->priority,
                ]);
            },
        );
    }

    #[\Override]
    public function getAlias(): string
    {
        return 'max_bot';
    }
}
