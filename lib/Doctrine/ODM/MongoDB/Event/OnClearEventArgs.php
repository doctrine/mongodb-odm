<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Event;

use Doctrine\Common\Persistence\Event\OnClearEventArgs as BaseOnClearEventArgs;
use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * Provides event arguments for the onClear event.
 *
 */
class OnClearEventArgs extends BaseOnClearEventArgs
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
