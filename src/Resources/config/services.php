<?php

declare(strict_types=1);

use MaxMessenger\Bot\Bundle\Controller\WebhookController;
use MaxMessenger\Bot\Bundle\DependencyInjection\MaxBotExtension;
use MaxMessenger\Bot\Bundle\Service\MaxBotConfiguratorRegistry;
use MaxMessenger\Bot\Bundle\Service\MaxBotFactory;
use MaxMessenger\Bot\MaxApiClient;
use MaxMessenger\Bot\MaxApiConfig;
use MaxMessenger\Bot\MaxBot;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use MaxMessenger\Bot\Bundle\EventListener\CommandListener;
use MaxMessenger\Bot\Bundle\Service\CommandRegistry;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
        ->private()
        ->autowire()
        ->autoconfigure();

    $services->load('MaxMessenger\\Bot\\Bundle\\', '../../')
        ->exclude('../../{DependencyInjection,Entity,Resources,Kernel.php}');

    $services->set(MaxApiConfig::class)
        ->arg(0, param('max_bot.access_token'))
        ->arg(1, null)
        ->arg(2, param('max_bot.base_url'))
        ->call('setConnectTimeout', [param('max_bot.connect_timeout')])
        ->call('setTimeout', [param('max_bot.timeout')])
        ->call('setRetryAttempts', [param('max_bot.retry_attempts')]);

    $services->set(MaxApiClient::class)
        ->arg(0, service(MaxApiConfig::class));

    $services->set(MaxBotFactory::class)
        ->arg(0, service(MaxApiClient::class))
        ->arg(1, service(MaxBotConfiguratorRegistry::class))
        ->arg(2, param('max_bot.webhook_secret'));

    $services->set(MaxBot::class)
        ->factory([service(MaxBotFactory::class), 'create']);

    $services->set(WebhookController::class)
        ->arg(0, param('max_bot.webhook_secret'))
        ->public()
        ->tag('controller.service_arguments');

    $services->set(MaxBotConfiguratorRegistry::class)
        ->arg(0, tagged_iterator(MaxBotExtension::CONFIGURATOR_TAG));

    $services->set(CommandListener::class)
        ->args([
            service(CommandRegistry::class),
        ])
        ->tag('kernel.event_subscriber');
};
