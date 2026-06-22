<?php

declare(strict_types=1);

namespace MaxMessenger\Bot\Bundle\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class AsMaxBotCommand
{
    public function __construct(
        public int $priority = 0,
    ) {
    }
}
