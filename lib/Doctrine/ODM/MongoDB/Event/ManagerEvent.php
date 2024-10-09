<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Event;

use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Provides event arguments for the flush events.
 */
class ManagerEvent extends Event
{
    public function __construct(private DocumentManager $objectManager)
    {
    }

    public function getDocumentManager(): DocumentManager
    {
        return $this->objectManager;
    }
}
