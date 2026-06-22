<?php

declare(strict_types=1);

namespace MaxMessenger\Bot\Bundle\Command;

use InvalidArgumentException;
use MaxMessenger\Bot\Model\Enum\UpdateType;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

abstract class AbstractMaxBotCommand extends Command
{
    protected function createIo(InputInterface $input, OutputInterface $output): SymfonyStyle
    {
        return new SymfonyStyle($input, $output);
    }

    /**
     * @return list<UpdateType>|null
     */
    protected function parseUpdateTypes(?string $typesRaw): ?array
    {
        if ($typesRaw === null || $typesRaw === '' || strtolower($typesRaw) === 'all') {
            return null;
        }

        $typeNames = array_values(array_filter(array_map(
            static fn (string $type): string => trim($type),
            explode(',', $typesRaw),
        )));

        if ($typeNames === []) {
            return null;
        }

        $resolvedTypes = [];
        $unknownTypes = [];

        foreach ($typeNames as $typeName) {
            $resolvedType = UpdateType::tryFrom($typeName);
            if ($resolvedType === null) {
                $unknownTypes[] = $typeName;
                continue;
            }

            $resolvedTypes[$resolvedType->value] = $resolvedType;
        }

        if ($unknownTypes !== []) {
            $availableTypes = implode(', ', array_map(
                static fn (UpdateType $type): string => $type->value,
                UpdateType::cases(),
            ));

            throw new InvalidArgumentException(sprintf(
                'Unknown update type(s): %s. Available values: %s',
                implode(', ', $unknownTypes),
                $availableTypes,
            ));
        }

        return array_values($resolvedTypes);
    }

    /**
     * @param list<UpdateType>|null $types
     */
    protected function stringifyUpdateTypes(?array $types): string
    {
        if ($types === null || $types === []) {
            return 'all';
        }

        return implode(', ', array_map(
            static fn (UpdateType $type): string => $type->value,
            $types,
        ));
    }

    protected function renderException(SymfonyStyle $io, Throwable $exception): int
    {
        $io->error($exception->getMessage());

        if ($io->isVerbose()) {
            $io->writeln($exception->getTraceAsString());
        }

        return self::FAILURE;
    }
}
