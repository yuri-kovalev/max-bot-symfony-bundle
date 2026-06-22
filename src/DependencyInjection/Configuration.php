<?php

declare(strict_types=1);

namespace MaxMessenger\Bot\Bundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('max_bot');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('access_token')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->info('Access token for Max Bot API.')
                ->end()
                ->scalarNode('webhook_secret')
                    ->defaultNull()
                    ->info('Optional secret from X-Max-Bot-Api-Secret header.')
                ->end()
                ->scalarNode('base_url')
                    ->defaultValue('https://platform-api.max.ru')
                    ->cannotBeEmpty()
                    ->info('Base URL for Max API.')
                ->end()
                ->integerNode('connect_timeout')
                    ->min(0)
                    ->defaultValue(5000)
                    ->info('Connection timeout in milliseconds.')
                ->end()
                ->integerNode('timeout')
                    ->min(0)
                    ->defaultValue(10000)
                    ->info('Request timeout in milliseconds.')
                ->end()
                ->arrayNode('retry_attempts')
                    ->integerPrototype()->min(1)->end()
                    ->defaultValue([1000, 2000, 4000, 8000, 15000])
                    ->info('Retry delay sequence in milliseconds.')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
