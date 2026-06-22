<?php

declare(strict_types=1);

namespace MaxMessenger\Bot\Bundle\Command;

use MaxMessenger\Bot\MaxApiClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'max-bot:delete-chats', description: 'Delete chats by IDs')]
final class MaxBotDeleteChatsCommand extends AbstractMaxBotCommand
{
    public function __construct(
        private readonly MaxApiClient $maxApiClient,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('chat_ids', InputArgument::REQUIRED, 'Comma-separated chat IDs (for example: 123,456)')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Delete without confirmation');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = $this->createIo($input, $output);

        $chatIdsRaw = (string) $input->getArgument('chat_ids');
        $chatIdParts = preg_split('/[\s,]+/', $chatIdsRaw, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $chatIds = [];
        foreach ($chatIdParts as $chatIdPart) {
            if (!preg_match('/^\d+$/', $chatIdPart)) {
                $io->error(sprintf('Invalid chat ID: %s', $chatIdPart));

                return self::INVALID;
            }

            $chatIds[(int) $chatIdPart] = (int) $chatIdPart;
        }

        if ($chatIds === []) {
            $io->error('No chat IDs provided.');

            return self::INVALID;
        }

        $chatIds = array_values($chatIds);

        if (!$input->getOption('force')) {
            if (!$io->confirm(sprintf('Delete %d chat(s): %s?', count($chatIds), implode(', ', $chatIds)), false)) {
                $io->warning('Operation cancelled.');

                return self::SUCCESS;
            }
        }

        $deleted = [];
        $failed = [];

        foreach ($chatIds as $chatId) {
            try {
                $this->maxApiClient->deleteChat($chatId);
                $deleted[] = $chatId;
            } catch (\Throwable $exception) {
                $failed[] = [$chatId, $exception->getMessage()];
            }
        }

        if ($deleted !== []) {
            $io->success(sprintf('Deleted chat IDs: %s', implode(', ', $deleted)));
        }

        if ($failed !== []) {
            $io->table(['chat_id', 'error'], $failed);

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
