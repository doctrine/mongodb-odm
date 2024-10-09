<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Utility;

use Doctrine\Common\EventArgs;
use Doctrine\Common\EventManager;
use InvalidArgumentException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Used to wrap a Doctrine EventManager as a Symfony EventDispatcherInterface
 * when a Doctrine EventManager is injected into the DocumentManager.
 *
 * @internal
 */
class EventDispatcher implements EventDispatcherInterface
{
    public function __construct(private EventManager $eventManager)
    {
    }

    /**
     * Dispatches an event to all registered listeners.
     *
     * @param T      $event     The event to pass to the event handlers/listeners
     * @param string $eventName The name of the event to dispatch.
     *
     * @return T The passed $event MUST be returned
     *
     * @template T of EventArgs
     */
    public function dispatch(object $event, string|null $eventName = null): object
    {
        if (! $eventName) {
            throw new InvalidArgumentException('Event name is required, none given.');
        }

        $this->eventManager->dispatchEvent($eventName, $event);

        return $event;
    }
}
