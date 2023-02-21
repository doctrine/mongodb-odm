<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Event;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Persistence\Event\OnClearEventArgs as BaseOnClearEventArgs;

use function func_num_args;
use function method_exists;
use function trigger_deprecation;

/**
 * Provides event arguments for the onClear event.
 *
 * @template-extends BaseOnClearEventArgs<DocumentManager>
 */
final class OnClearEventArgs extends BaseOnClearEventArgs
{
    /**
     * @deprecated
     *
     * @var class-string|null
     */
    private ?string $entityClass;

    /** @param class-string|null $entityClass */
    public function __construct($objectManager, $entityClass = null)
    {
        if (method_exists(parent::class, 'getEntityClass') && $entityClass !== null) {
            parent::__construct($objectManager, $entityClass);
        } else {
            if (func_num_args() > 1) {
                trigger_deprecation(
                    'doctrine/mongodb-odm',
                    '2.4',
                    'Passing $entityClass argument to %s::%s() is deprecated and will not be supported in Doctrine ODM 3.0.',
                    self::class,
                    __METHOD__,
                );
            }

            parent::__construct($objectManager);
        }

        $this->entityClass = $entityClass;
    }

    public function getDocumentManager(): DocumentManager
    {
        return $this->getObjectManager();
    }

    /**
     * @deprecated no replacement planned
     *
     * @return class-string|null
     */
    public function getDocumentClass(): ?string
    {
        trigger_deprecation(
            'doctrine/mongodb-odm',
            '2.4',
            'Calling %s() is deprecated and will not be supported in Doctrine ODM 3.0.',
            __METHOD__,
        );

        return $this->entityClass;
    }

    /**
     * Returns whether this event clears all documents.
     *
     * @deprecated no replacement planned
     */
    public function clearsAllDocuments(): bool
    {
        trigger_deprecation(
            'doctrine/mongodb-odm',
            '2.4',
            'Calling %s() is deprecated and will not be supported in Doctrine ODM 3.0.',
            __METHOD__,
        );

        return $this->entityClass !== null;
    }

    /** @deprecated no replacement planned */
    public function clearsAllEntities(): bool
    {
        if (method_exists(parent::class, 'clearsAllEntities')) {
            return parent::clearsAllEntities();
        }

        trigger_deprecation(
            'doctrine/mongodb-odm',
            '2.4',
            'Calling %s() is deprecated and will not be supported in Doctrine ODM 3.0.',
            __METHOD__,
        );

        return $this->entityClass !== null;
    }

    /**
     * @deprecated no replacement planned
     *
     * @return class-string|null
     */
    public function getEntityClass(): ?string
    {
        if (method_exists(parent::class, 'getEntityClass')) {
            return parent::getEntityClass();
        }

        trigger_deprecation(
            'doctrine/mongodb-odm',
            '2.4',
            'Calling %s() is deprecated and will not be supported in Doctrine ODM 3.0.',
            __METHOD__,
        );

        return $this->entityClass;
    }
}
