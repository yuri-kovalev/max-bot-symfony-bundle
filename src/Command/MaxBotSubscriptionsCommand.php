<?php

declare(strict_types=1);

namespace MaxMessenger\Bot\Bundle\Command;

use MaxMessenger\Bot\MaxApiClient;
use MaxMessenger\Bot\Model\Response\Subscription;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'max-bot:subscriptions', description: 'Show webhook subscriptions')]
final class MaxBotSubscriptionsCommand extends AbstractMaxBotCommand
{
    public function __construct(
        private readonly MaxApiClient $maxApiClient,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = $this->createIo($input, $output);

        try {
            $subscriptions = $this->maxApiClient->getSubscriptions()->getSubscriptions();

            if ($subscriptions === []) {
                $io->warning('No webhook subscriptions found.');

                return self::SUCCESS;
            }

            $rows = array_map(
                static function (Subscription $subscription): array {
                    $rawTypes = $subscription->getUpdateTypesRaw();

                    return [
                        $subscription->getUrl(),
                        $subscription->getVersion() ?? '-',
                        $rawTypes === null || $rawTypes === [] ? 'all' : implode(', ', $rawTypes),
                        $subscription->getTime()->format('Y-m-d H:i:s'),
                    ];
                },
                $subscriptions,
            );

            $io->table(['url', 'version', 'update_types', 'created_at'], $rows);
        } catch (\Throwable $exception) {
            return $this->renderException($io, $exception);
        }

        return self::SUCCESS;
    }
}
