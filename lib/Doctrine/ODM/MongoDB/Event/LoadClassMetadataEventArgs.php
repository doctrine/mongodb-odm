<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Event;

use Doctrine\Common\Persistence\Event\LoadClassMetadataEventArgs as BaseLoadClassMetadataEventArgs;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use const E_USER_DEPRECATED;
use function assert;
use function sprintf;
use function trigger_error;

/**
 * Class that holds event arguments for a loadMetadata event.
 *
 * @final
 */
class LoadClassMetadataEventArgs extends BaseLoadClassMetadataEventArgs
{
    public function __construct(ClassMetadata $classMetadata, ObjectManager $objectManager)
    {
        if (self::class !== static::class) {
            @trigger_error(sprintf('The class "%s" extends "%s" which will be final in MongoDB ODM 2.0.', static::class, self::class), E_USER_DEPRECATED);
        }
        parent::__construct($classMetadata, $objectManager);
    }

    public function getDocumentManager() : DocumentManager
    {
        $dm = $this->getObjectManager();
        assert($dm instanceof DocumentManager);
        return $dm;
    }
}
