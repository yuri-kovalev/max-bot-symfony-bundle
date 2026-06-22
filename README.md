# Max Bot Bundle для Symfony

`yuri-kovalev/max-bot-symfony-bundle` — это Symfony бандл Max Bot API созданный на основе
[`max-messenger-bot/max-bot-api-php`](https://packagist.org/packages/max-messenger-bot/max-bot-api-php)
с обработкой webhook и расширяемыми обработчиками бота.

## Требования

- PHP 8.2+
- Symfony 6.0+ (также поддерживается Symfony 7)
- `max-messenger-bot/max-bot-api-php` ^0.3

## Установка

```bash
composer require it-koval/max-bot-symfony-bundle
```

## Регистрация бандла

Если Symfony Flex не зарегистрировал бандл автоматически, добавьте его вручную в `config/bundles.php`:

```php
<?php

return [
    // ...
    MaxMessenger\Bot\Bundle\MaxBotBundle::class => ['all' => true],
];
```

## Конфигурация

Создайте `config/packages/max_bot.yaml`:

```yaml
max_bot:
  access_token: '%env(MAX_BOT_ACCESS_TOKEN)%'
  webhook_secret: '%env(default::MAX_BOT_WEBHOOK_SECRET)%'
  base_url: 'https://platform-api.max.ru'
  connect_timeout: 5000
  timeout: 10000
  retry_attempts: [1000, 2000, 4000, 8000, 15000]
```

Зарегистрируйте маршрут webhook в `config/routes/max_bot.yaml`:

```yaml
max_bot_bundle:
  resource: "@MaxBotBundle/Resources/config/routing.php"
  prefix: "/webhook/max/bot"
```

## Консольные команды

### Управление webhook

```bash
bin/console max-bot:subscribe https://example.ru/max-bot/webhook --secret=your-secret
bin/console max-bot:subscriptions
bin/console max-bot:unsubscribe https://example.ru/max-bot/webhook
```

### Long Polling

```bash
bin/console max-bot:polling
bin/console max-bot:polling --limit=10 --timeout=5 --marker=123 --types=message_created,message_callback
```

### Чаты

```bash
bin/console max-bot:chats
bin/console max-bot:chats --count=20 --marker=123
bin/console max-bot:delete-chats 12345,67890 --force
```

### Диагностика

```bash
bin/console max-bot:debug
```

## Примеры

Ниже два рекомендуемых способа расширения бандла.

### 1) Конфигуратор через атрибут `AsMaxBotConfigurator`

Используйте, когда нужно централизованно настроить обработчики `MaxBot` (события, fallback, callbacks).

Пример конфигуратора:

```php
<?php

declare(strict_types=1);

namespace App\MaxBot\Configurator;

use MaxMessenger\Bot\Bundle\Attribute\AsMaxBotConfigurator;
use MaxMessenger\Bot\Bundle\Contract\MaxBotConfiguratorInterface;
use MaxMessenger\Bot\MaxBot;
use MaxMessenger\Bot\MaxBot\Event\MessageCreatedEvent;

#[AsMaxBotConfigurator]
final readonly class BotEventsConfigurator implements MaxBotConfiguratorInterface
{
    public function configure(MaxBot $maxBot): void
    {
        $maxBot->onMessageCreated(static function (MessageCreatedEvent $event): void {
            if (trim($event->getMessage()?->getText() ?? '') !== 'ping') {
                return;
            }

            $event->reply('pong');
        });
    }
}
```

### 2) Команда через атрибут `AsMaxBotCommand`

Используйте, когда нужен отдельный обработчик команды.

Наследуйтесь от `AbstractCommand` и реализуйте методы `getCommandName` и `execute`, или реализуйте интерфейс `CommandInterface`.

Команды запускаются в порядке приоритета (чем выше `priority`, тем раньше вызов).

Пример команды:
```php
<?php

declare(strict_types=1);

namespace App\MaxBot\Command;

use MaxMessenger\Bot\Bundle\Attribute\AsMaxBotCommand;
use MaxMessenger\Bot\Bundle\Max\Command\AbstractCommand;
use MaxMessenger\Bot\MaxApiClient;
use MaxMessenger\Bot\Model\Response\MessageCreatedUpdate;
use MaxMessenger\Bot\Model\Response\Update;

#[AsMaxBotCommand(priority: 100)]
final readonly class StartCommand extends AbstractCommand
{
    public function __construct(
        private MaxApiClient $maxApiClient,
    ) {
    }

    protected function getCommandName(): string
    {
        return '/start';
    }

    public function execute(Update $update): void
    {
        if (!$update instanceof MessageCreatedUpdate) {
            return;
        }

        $chatId = $update->getMessage()->getRecipient()->getChatId();
        $this->maxApiClient->sendMessageToChat($chatId, 'Welcome! I am ready to help.');
    }
}
```

> Если в проекте включены стандартные `autowire + autoconfigure` (Symfony Flex по умолчанию), достаточно создать классы в `src/` — бандл сам зарегистрирует их по атрибутам и интерфейсам.


## Доступные сервисы

- `MaxMessenger\Bot\MaxApiConfig`
- `MaxMessenger\Bot\MaxApiClient`
- `MaxMessenger\Bot\MaxBot` (настроенный singleton)
- `MaxMessenger\Bot\Bundle\Service\MaxBotFactory`
- `MaxMessenger\Bot\Bundle\Service\WebhookProcessor`

## Примечания

- Обработчик webhook валидирует:
  - HTTP-метод (`POST`)
  - `Content-Type: application/json`
  - заголовок `X-Max-Bot-Api-Secret` (если настроен `webhook_secret`)
- Некорректный payload возвращает `400`, некорректный секрет — `403`.
