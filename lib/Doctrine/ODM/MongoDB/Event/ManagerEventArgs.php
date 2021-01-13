<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Event;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Persistence\Event\ManagerEventArgs as BaseManagerEventArgs;

use function assert;

/**
 * Provides event arguments for the flush events.
 */
class ManagerEventArgs extends BaseManagerEventArgs
{
    public function getDocumentManager(): DocumentManager
    {
        $dm = $this->getObjectManager();
        assert($dm instanceof DocumentManager);

        return $dm;
    }
}
