<?php

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use MaxMessenger\Bot\Bundle\Controller\WebhookController;

return static function (RoutingConfigurator $routes) {
    $routes->add('_max_bot_webhook', '/')
        ->controller([WebhookController::class, 'indexAction']);
};
