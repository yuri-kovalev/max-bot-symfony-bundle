<?php

declare(strict_types=1);

namespace MaxMessenger\Bot\Bundle\Exception;

final class InvalidWebhookSecretException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('The provided X-Max-Bot-Api-Secret header is invalid.');
    }
}
