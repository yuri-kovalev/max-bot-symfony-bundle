<?php

declare(strict_types=1);

namespace MaxMessenger\Bot\Bundle\Command;

use MaxMessenger\Bot\MaxApiClient;
use MaxMessenger\Bot\Model\Response\Chat;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'max-bot:chats', description: 'Show bot chats')]
final class MaxBotChatsCommand extends AbstractMaxBotCommand
{
    public function __construct(
        private readonly MaxApiClient $maxApiClient,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('count', null, InputOption::VALUE_REQUIRED, 'Chats per page (1-100)', '20')
            ->addOption('marker', null, InputOption::VALUE_REQUIRED, 'Pagination marker');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = $this->createIo($input, $output);

        $countRaw = $input->getOption('count');
        $markerRaw = $input->getOption('marker');

        if (!is_string($countRaw) || !preg_match('/^\d+$/', $countRaw)) {
            $io->error('Option "--count" must be a number from 1 to 100.');

            return self::INVALID;
        }

        if ($markerRaw !== null && (!is_string($markerRaw) || !preg_match('/^\d+$/', $markerRaw))) {
            $io->error('Option "--marker" must be a positive integer.');

            return self::INVALID;
        }

        $count = (int) $countRaw;
        if ($count < 1 || $count > 100) {
            $io->error('Option "--count" must be between 1 and 100.');

            return self::INVALID;
        }

        $marker = $markerRaw !== null ? (int) $markerRaw : null;

        try {
            $botInfo = $this->maxApiClient->getMyInfo();
            $response = $this->maxApiClient->getChats($count, $marker);
            $chats = $response->getChats();

            if ($chats === []) {
                $io->warning('No chats found.');

                return self::SUCCESS;
            }

            $rows = array_map(
                static function (Chat $chat) use ($botInfo): array {
                    return [
                        $chat->getChatId(),
                        $chat->getTitle() ?? '-',
                        $chat->getType()?->value ?? $chat->getTypeRaw(),
                        $chat->getStatus()?->value ?? $chat->getStatusRaw(),
                        $chat->getParticipantsCount(),
                        $chat->getOwnerId() === $botInfo->getUserId() ? 'yes' : 'no',
                        $chat->isPublic() ? 'yes' : 'no',
                        $chat->getLastEventTime()->format('Y-m-d H:i:s'),
                    ];
                },
                $chats,
            );

            $io->table(
                ['chat_id', 'title', 'type', 'status', 'participants', 'is_owner', 'is_public', 'last_event_time'],
                $rows,
            );

            if ($response->getMarker() !== null) {
                $io->note(sprintf('Next marker: %d', $response->getMarker()));
            }
        } catch (\Throwable $exception) {
            return $this->renderException($io, $exception);
        }

        return self::SUCCESS;
    }
}
