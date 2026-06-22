<?php

declare(strict_types=1);

namespace MaxMessenger\Bot\Bundle\Command;

use MaxMessenger\Bot\MaxApiClient;
use MaxMessenger\Bot\Model\Request\SubscriptionRequestBody;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'max-bot:subscribe', description: 'Create webhook subscription')]
final class MaxBotSubscribeCommand extends AbstractMaxBotCommand
{
    public function __construct(
        private readonly MaxApiClient $maxApiClient,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('url', InputArgument::REQUIRED, 'Webhook URL (https://...)')
            ->addOption('secret', null, InputOption::VALUE_REQUIRED, 'Webhook secret sent in X-Max-Bot-Api-Secret')
            ->addOption('types', null, InputOption::VALUE_REQUIRED, 'Comma-separated update types or "all"');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = $this->createIo($input, $output);

        $url = (string) $input->getArgument('url');
        $secret = $input->getOption('secret');
        $typesRaw = $input->getOption('types');

        if (!is_string($secret) && $secret !== null) {
            $io->error('Option "--secret" must be a string.');

            return self::INVALID;
        }

        if (!is_string($typesRaw) && $typesRaw !== null) {
            $io->error('Option "--types" must be a comma-separated string.');

            return self::INVALID;
        }

        if (!str_starts_with($url, 'https://') || filter_var($url, FILTER_VALIDATE_URL) === false) {
            $io->error('Argument "url" must be a valid HTTPS URL.');

            return self::INVALID;
        }

        try {
            $subscription = SubscriptionRequestBody::new(
                url: $url,
                secret: $secret ?: null,
                update_types: $this->parseUpdateTypes($typesRaw),
            );

            $this->maxApiClient->subscribe($subscription);

            $io->success(sprintf(
                'Subscription created for %s (types: %s).',
                $url,
                $this->stringifyUpdateTypes($subscription->getUpdateTypes()),
            ));
        } catch (\Throwable $exception) {
            return $this->renderException($io, $exception);
        }

        return self::SUCCESS;
    }
}
