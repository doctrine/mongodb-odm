<?php

namespace Doctrine\ODM\MongoDB\Event;

use Doctrine\Common\Persistence\Event\LoadClassMetadataEventArgs as BaseLoadClassMetadataEventArgs;

/**
 * Class that holds event arguments for a loadMetadata event.
 *
 * @since 1.0
 */
class LoadClassMetadataEventArgs extends BaseLoadClassMetadataEventArgs
{
    /**
     * Retrieves the associated DocumentManager.
     *
     * @return \Doctrine\ODM\MongoDB\DocumentManager
     */
    public function getDocumentManager()
    {
        return $this->getObjectManager();
    }
}
