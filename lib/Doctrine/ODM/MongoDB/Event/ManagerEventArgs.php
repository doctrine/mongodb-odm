<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Event;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Persistence\Event\ManagerEventArgs as BaseManagerEventArgs;

/**
 * Provides event arguments for the flush events.
 *
 * @template-extends BaseManagerEventArgs<DocumentManager>
 */
class ManagerEventArgs extends BaseManagerEventArgs
{
    public function getDocumentManager(): DocumentManager
    {
        return $this->getObjectManager();
    }
}
