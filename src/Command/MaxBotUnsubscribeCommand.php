<?php

declare(strict_types=1);

namespace MaxMessenger\Bot\Bundle\Command;

use MaxMessenger\Bot\MaxApiClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'max-bot:unsubscribe', description: 'Delete webhook subscription by URL')]
final class MaxBotUnsubscribeCommand extends AbstractMaxBotCommand
{
    public function __construct(
        private readonly MaxApiClient $maxApiClient,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('url', InputArgument::REQUIRED, 'Webhook URL to unsubscribe');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = $this->createIo($input, $output);
        $url = (string) $input->getArgument('url');

        try {
            $this->maxApiClient->unsubscribe($url);
        } catch (\Throwable $exception) {
            return $this->renderException($io, $exception);
        }

        $io->success(sprintf('Subscription removed for %s.', $url));

        return self::SUCCESS;
    }
}
