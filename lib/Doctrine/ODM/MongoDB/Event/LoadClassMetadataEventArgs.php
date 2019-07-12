<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Event;

use Doctrine\Common\Persistence\Event\LoadClassMetadataEventArgs as BaseLoadClassMetadataEventArgs;
use Doctrine\ODM\MongoDB\DocumentManager;
use function assert;

/**
 * Class that holds event arguments for a loadMetadata event.
 */
final class LoadClassMetadataEventArgs extends BaseLoadClassMetadataEventArgs
{
    public function getDocumentManager() : DocumentManager
    {
        $dm = $this->getObjectManager();
        assert($dm instanceof DocumentManager);

        return $dm;
    }
}
