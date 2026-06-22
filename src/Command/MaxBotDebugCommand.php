<?php

declare(strict_types=1);

namespace MaxMessenger\Bot\Bundle\Command;

use MaxMessenger\Bot\MaxApiClient;
use MaxMessenger\Bot\MaxApiConfig;
use MaxMessenger\Bot\Model\Response\Subscription;
use MaxMessenger\Bot\Model\Response\Update;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'max-bot:debug', description: 'Show bot diagnostics and optional updates preview')]
final class MaxBotDebugCommand extends AbstractMaxBotCommand
{
    public function __construct(
        private readonly MaxApiClient $maxApiClient,
        private readonly MaxApiConfig $maxApiConfig,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('show-updates', null, InputOption::VALUE_NONE, 'Fetch and print updates once')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Updates limit for debug fetch', '10')
            ->addOption('timeout', null, InputOption::VALUE_REQUIRED, 'Updates timeout for debug fetch (0-90)', '0')
            ->addOption('marker', null, InputOption::VALUE_REQUIRED, 'Marker for debug updates fetch')
            ->addOption('types', null, InputOption::VALUE_REQUIRED, 'Comma-separated update types or "all"');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = $this->createIo($input, $output);

        $limitRaw = $input->getOption('limit');
        $timeoutRaw = $input->getOption('timeout');
        $markerRaw = $input->getOption('marker');
        $typesRaw = $input->getOption('types');

        if (
            !is_string($limitRaw) || !preg_match('/^\d+$/', $limitRaw)
            || !is_string($timeoutRaw) || !preg_match('/^\d+$/', $timeoutRaw)
            || ($markerRaw !== null && (!is_string($markerRaw) || !preg_match('/^\d+$/', $markerRaw)))
            || ($typesRaw !== null && !is_string($typesRaw))
        ) {
            $io->error('Invalid options. Check --help for expected types.');

            return self::INVALID;
        }

        $limit = (int) $limitRaw;
        $timeout = (int) $timeoutRaw;
        $marker = $markerRaw !== null ? (int) $markerRaw : null;

        if ($limit < 1 || $limit > 1000) {
            $io->error('Option "--limit" must be between 1 and 1000.');

            return self::INVALID;
        }

        if ($timeout < 0 || $timeout > 90) {
            $io->error('Option "--timeout" must be between 0 and 90.');

            return self::INVALID;
        }

        try {
            $botInfo = $this->maxApiClient->getMyInfo();
            $subscriptions = $this->maxApiClient->getSubscriptions()->getSubscriptions();

            $io->section('Bot info');
            $io->definitionList(
                ['name' => $botInfo->getName()],
                ['user_id' => (string) $botInfo->getUserId()],
                ['username' => $botInfo->getUsername() ?? '-'],
            );

            $io->section('API config');
            $io->definitionList(
                ['base_url' => $this->maxApiConfig->getBaseUrl()],
                ['connect_timeout_ms' => (string) $this->maxApiConfig->getConnectTimeout()],
                ['timeout_ms' => (string) $this->maxApiConfig->getTimeout()],
                ['retry_attempts_ms' => implode(', ', $this->maxApiConfig->getRetryAttempts())],
            );

            $io->section('Webhook subscriptions');
            if ($subscriptions === []) {
                $io->writeln('No subscriptions configured.');
            } else {
                $rows = array_map(
                    static function (Subscription $subscription): array {
                        $types = $subscription->getUpdateTypesRaw();

                        return [
                            $subscription->getUrl(),
                            $subscription->getTime()->format('Y-m-d H:i:s'),
                            $types === null || $types === [] ? 'all' : implode(', ', $types),
                        ];
                    },
                    $subscriptions,
                );

                $io->table(['url', 'created_at', 'types'], $rows);
                $io->warning('Long polling may be unavailable while webhook subscriptions exist.');
            }

            if (!$input->getOption('show-updates')) {
                return self::SUCCESS;
            }

            $types = $this->parseUpdateTypes($typesRaw);

            $io->section('Updates preview');
            $response = $this->maxApiClient->getUpdates($limit, $timeout, $marker, $types);
            $updates = $response->getUpdates();

            if ($updates === []) {
                $io->writeln('No updates returned for current query.');
            } else {
                $rows = array_map(
                    static fn (Update $update): array => [
                        $update->getUpdateType()?->value ?? $update->getUpdateTypeRaw(),
                        $update->getTimestamp()->format('Y-m-d H:i:s'),
                    ],
                    $updates,
                );

                $io->table(['type', 'timestamp'], $rows);

                if ($io->isVerbose()) {
                    foreach ($updates as $update) {
                        $json = json_encode(
                            $update->getRawData(),
                            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
                        );
                        $io->writeln($json);
                    }
                }
            }

            $io->note(sprintf('Next marker: %s', $response->getMarker() !== null ? (string) $response->getMarker() : 'null'));
        } catch (\Throwable $exception) {
            return $this->renderException($io, $exception);
        }

        return self::SUCCESS;
    }
}
