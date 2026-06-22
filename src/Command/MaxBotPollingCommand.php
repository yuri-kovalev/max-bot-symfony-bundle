<?php

declare(strict_types=1);

namespace MaxMessenger\Bot\Bundle\Command;

use MaxMessenger\Bot\Bundle\Max\MaxUpdateHandler;
use MaxMessenger\Bot\Bundle\Service\MaxBotFactory;
use MaxMessenger\Bot\MaxApiClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'max-bot:polling', description: 'Run long polling and handle updates with configured handlers')]
final class MaxBotPollingCommand extends AbstractMaxBotCommand
{
    public function __construct(
        private readonly MaxApiClient $maxApiClient,
        private readonly MaxBotFactory $maxBotFactory,
        private readonly MaxUpdateHandler $maxUpdateHandler,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Updates per request (1-1000)', '10')
            ->addOption('timeout', null, InputOption::VALUE_REQUIRED, 'Long polling timeout in seconds (0-90)', '60')
            ->addOption('marker', null, InputOption::VALUE_REQUIRED, 'Starting marker')
            ->addOption('types', null, InputOption::VALUE_REQUIRED, 'Comma-separated update types or "all"')
            ->addOption('once', null, InputOption::VALUE_NONE, 'Process one updates page and stop')
            ->addOption('sleep-ms', null, InputOption::VALUE_REQUIRED, 'Sleep between requests in milliseconds', '100');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = $this->createIo($input, $output);

        $limitRaw = $input->getOption('limit');
        $timeoutRaw = $input->getOption('timeout');
        $markerRaw = $input->getOption('marker');
        $typesRaw = $input->getOption('types');
        $sleepMsRaw = $input->getOption('sleep-ms');

        if (
            !is_string($limitRaw) || !preg_match('/^\d+$/', $limitRaw)
            || !is_string($timeoutRaw) || !preg_match('/^\d+$/', $timeoutRaw)
            || ($markerRaw !== null && (!is_string($markerRaw) || !preg_match('/^\d+$/', $markerRaw)))
            || !is_string($sleepMsRaw) || !preg_match('/^\d+$/', $sleepMsRaw)
            || ($typesRaw !== null && !is_string($typesRaw))
        ) {
            $io->error('Invalid options. Check --help for expected types.');

            return self::INVALID;
        }

        $limit = (int) $limitRaw;
        $timeout = (int) $timeoutRaw;
        $marker = $markerRaw !== null ? (int) $markerRaw : null;
        $sleepMs = (int) $sleepMsRaw;

        if ($limit < 1 || $limit > 1000) {
            $io->error('Option "--limit" must be between 1 and 1000.');

            return self::INVALID;
        }

        if ($timeout < 0 || $timeout > 90) {
            $io->error('Option "--timeout" must be between 0 and 90 seconds.');

            return self::INVALID;
        }

        try {
            $types = $this->parseUpdateTypes($typesRaw);
            $maxBot = $this->maxBotFactory->create();

            $totalReceived = 0;
            $totalHandled = 0;
            $iteration = 0;

            $io->writeln('Polling started. Press Ctrl+C to stop.');

            do {
                ++$iteration;

                $response = $this->maxApiClient->getUpdates($limit, $timeout, $marker, $types);
                $updates = $response->getUpdates();
                $marker = $response->getMarker();

                $batchHandled = 0;
                foreach ($updates as $update) {
                    if ($this->maxUpdateHandler->handle($maxBot, $update)) {
                        ++$batchHandled;
                    }
                }

                $batchReceived = count($updates);
                $totalReceived += $batchReceived;
                $totalHandled += $batchHandled;

                $io->writeln(sprintf(
                    '[%s] iteration=%d received=%d handled=%d next_marker=%s',
                    date('Y-m-d H:i:s'),
                    $iteration,
                    $batchReceived,
                    $batchHandled,
                    $marker !== null ? (string) $marker : 'null',
                ));

                if ($input->getOption('once')) {
                    break;
                }

                if ($sleepMs > 0) {
                    usleep($sleepMs * 1000);
                }
            } while (true);

            $io->success(sprintf(
                'Polling finished. Total received=%d, handled=%d, last marker=%s.',
                $totalReceived,
                $totalHandled,
                $marker !== null ? (string) $marker : 'null',
            ));
        } catch (\Throwable $exception) {
            return $this->renderException($io, $exception);
        }

        return self::SUCCESS;
    }
}
