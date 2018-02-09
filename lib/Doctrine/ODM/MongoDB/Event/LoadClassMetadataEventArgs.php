<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Event;

use Doctrine\Common\Persistence\Event\LoadClassMetadataEventArgs as BaseLoadClassMetadataEventArgs;
use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * Class that holds event arguments for a loadMetadata event.
 *
 */
class LoadClassMetadataEventArgs extends BaseLoadClassMetadataEventArgs
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
