<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests;

use MongoDB\Driver\Monitoring\CommandFailedEvent;
use MongoDB\Driver\Monitoring\CommandStartedEvent;
use MongoDB\Driver\Monitoring\CommandSubscriber;
use MongoDB\Driver\Monitoring\CommandSucceededEvent;
use function count;
use function MongoDB\Driver\Monitoring\addSubscriber;
use function MongoDB\Driver\Monitoring\removeSubscriber;

class CommandLogger implements \Countable, CommandSubscriber
{
    /** @var (CommandSucceededEvent|CommandSucceededEvent)[] */
    private $commands = [];

    /** @var bool */
    private $registered = false;

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

    public function commandStarted(CommandStartedEvent $event)
    {
    }

    public function commandSucceeded(CommandSucceededEvent $event)
    {
        $this->commands[] = $event;
    }

    public function commandFailed(CommandFailedEvent $event)
    {
        $this->commands[] = $event;
    }

    public function clear(): void
    {
        $this->commands = [];
    }

    public function count(): int
    {
        return count($this->commands);
    }

    /**
     * @return (CommandSucceededEvent|CommandSucceededEvent)[]
     */
    public function getAll(): array
    {
        return $this->commands;
    }
}
