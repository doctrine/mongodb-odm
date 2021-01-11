<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\APM;

use MongoDB\Driver\Monitoring\CommandSubscriber;

interface CommandLoggerInterface extends CommandSubscriber
{
    /**
     * Registers this command logger instance with the MongoDB library so it can receive command events
     */
    public function register(): void;

    /**
     * Unregisters this command logger instance with the MongoDB library so it no longer receives command events
     */
    public function unregister(): void;
}
