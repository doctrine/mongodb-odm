<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Event;

use Doctrine\Common\Persistence\Event\ManagerEventArgs as BaseManagerEventArgs;
use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * Provides event arguments for the flush events.
 *
 */
class ManagerEventArgs extends BaseManagerEventArgs
{
    /**
     * Retrieves the associated DocumentManager.
     *
     * @return DocumentManager
     */
    public function getDocumentManager()
    {
        return $this->getObjectManager();
    }
}
