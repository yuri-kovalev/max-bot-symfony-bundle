<?php

declare(strict_types=1);

namespace MaxMessenger\Bot\Bundle;

use MaxMessenger\Bot\Bundle\DependencyInjection\Compiler\CommandCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class MaxBotBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new CommandCompilerPass());
    }
}
