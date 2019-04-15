<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Event;

use Doctrine\Common\Persistence\Event\OnClearEventArgs as BaseOnClearEventArgs;
use Doctrine\ODM\MongoDB\DocumentManager;
use const E_USER_DEPRECATED;
use function assert;
use function sprintf;
use function trigger_error;

/**
 * Provides event arguments for the onClear event.
 *
 * @final
 */
class OnClearEventArgs extends BaseOnClearEventArgs
{
    public function __construct($objectManager, $entityClass = null)
    {
        if (self::class !== static::class) {
            @trigger_error(sprintf('The class "%s" extends "%s" which will be final in MongoDB ODM 2.0.', static::class, self::class), E_USER_DEPRECATED);
        }
        parent::__construct($objectManager, $entityClass);
    }

    public function getDocumentManager() : DocumentManager
    {
        $dm = $this->getObjectManager();
        assert($dm instanceof DocumentManager);
        return $dm;
    }

    public function getDocumentClass() : ?string
    {
        return $this->getEntityClass();
    }

    /**
     * Returns whether this event clears all documents.
     */
    public function clearsAllDocuments() : bool
    {
        return $this->clearsAllEntities();
    }
}
