<?php

namespace Doctrine\ODM\MongoDB\Event;

use Doctrine\Common\Persistence\Event\OnClearEventArgs as BaseOnClearEventArgs;

/**
 * Provides event arguments for the onClear event.
 *
 * @since 1.0
 */
class OnClearEventArgs extends BaseOnClearEventArgs
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

    /**
     * Returns the name of the document class that is cleared, or null if all
     * are cleared.
     *
     * @return string|null
     */
    public function getDocumentClass()
    {
        return $this->getEntityClass();
    }

    /**
     * Returns whether this event clears all documents.
     *
     * @return bool
     */
    public function clearsAllDocuments()
    {
        return $this->clearsAllEntities();
    }
}
