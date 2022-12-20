<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\APM;

use Countable;
use MongoDB\Driver\Monitoring\CommandFailedEvent;
use MongoDB\Driver\Monitoring\CommandStartedEvent;
use MongoDB\Driver\Monitoring\CommandSucceededEvent;

use function count;
use function MongoDB\Driver\Monitoring\addSubscriber;
use function MongoDB\Driver\Monitoring\removeSubscriber;

final class CommandLogger implements Countable, CommandLoggerInterface
{
    /** @var Command[] */
    private array $commands = [];

    /** @var CommandStartedEvent[] */
    private array $startedCommands = [];

    private bool $registered = false;

    public function register(): void
    {
        if ($this->registered) {
            return;
        }

        $this->registered = true;
        addSubscriber($this);
    }

    public function unregister(): void
    {
        if (! $this->registered) {
            return;
        }

        removeSubscriber($this);
        $this->registered = false;
    }

    public function commandStarted(CommandStartedEvent $event): void
    {
        $this->startedCommands[$event->getRequestId()] = $event;
    }

    public function commandSucceeded(CommandSucceededEvent $event): void
    {
        $commandStartedEvent = $this->findAndRemoveCommandStartedEvent($event->getRequestId());
        if (! $commandStartedEvent) {
            return;
        }

        $this->logCommand(Command::createForSucceededCommand($commandStartedEvent, $event));
    }

    public function commandFailed(CommandFailedEvent $event): void
    {
        $commandStartedEvent = $this->findAndRemoveCommandStartedEvent($event->getRequestId());
        if (! $commandStartedEvent) {
            return;
        }

        $this->logCommand(Command::createForFailedCommand($commandStartedEvent, $event));
    }

    public function clear(): void
    {
        $this->commands = [];
    }

    public function count(): int
    {
        return count($this->commands);
    }

    /** @return Command[] */
    public function getAll(): array
    {
        return $this->commands;
    }

    private function findAndRemoveCommandStartedEvent(string $requestId): ?CommandStartedEvent
    {
        $startedEvent = $this->startedCommands[$requestId] ?? null;
        unset($this->startedCommands[$requestId]);

        return $startedEvent;
    }

    private function logCommand(Command $command): void
    {
        $this->commands[] = $command;
    }
}
