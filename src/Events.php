<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB;

/**
 * Container for all ODM events.
 *
 * This class cannot be instantiated.
 */
final class Events
{
    private function __construct()
    {
    }

    /**
     * The preRemove event occurs for a given document before the respective
     * DocumentManager remove operation for that document is executed.
     *
     * This is a document lifecycle event.
     */
    public const string preRemove = 'preRemove';

    /**
     * The postRemove event occurs for a document after the document has
     * been deleted. It will be invoked after the database delete operations.
     *
     * This is a document lifecycle event.
     */
    public const string postRemove = 'postRemove';

    /**
     * The prePersist event occurs for a given document before the respective
     * DocumentManager persist operation for that document is executed.
     *
     * This is a document lifecycle event.
     */
    public const string prePersist = 'prePersist';

    /**
     * The postPersist event occurs for a document after the document has
     * been made persistent. It will be invoked after the database insert operations.
     * Generated primary key values are available in the postPersist event.
     *
     * This is a document lifecycle event.
     */
    public const string postPersist = 'postPersist';

    /**
     * The preUpdate event occurs before the database update operations to
     * document data.
     *
     * This is a document lifecycle event.
     */
    public const string preUpdate = 'preUpdate';

    /**
     * The postUpdate event occurs after the database update operations to
     * document data.
     *
     * This is a document lifecycle event.
     */
    public const string postUpdate = 'postUpdate';

    /**
     * The preLoad event occurs for a document before the document has been loaded
     * into the current DocumentManager from the database or before the refresh operation
     * has been applied to it.
     *
     * This is a document lifecycle event.
     */
    public const string preLoad = 'preLoad';

    /**
     * The postLoad event occurs for a document after the document has been loaded
     * into the current DocumentManager from the database or after the refresh operation
     * has been applied to it.
     *
     * Note that the postLoad event occurs for a document before any associations have been
     * initialized. Therefore it is not safe to access associations in a postLoad callback
     * or event handler.
     *
     * This is a document lifecycle event.
     */
    public const string postLoad = 'postLoad';

    /**
     * The loadClassMetadata event occurs after the mapping metadata for a class
     * has been loaded from a mapping source (annotations/xml).
     */
    public const string loadClassMetadata = 'loadClassMetadata';

    /**
     * The onClassMetadataNotFound event occurs whenever loading metadata for a class
     * failed.
     */
    public const string onClassMetadataNotFound = 'onClassMetadataNotFound';

    /**
     * The preFlush event occurs when the DocumentManager#flush() operation is invoked,
     * but before any changes to managed documents have been calculated. This event is
     * always raised right after DocumentManager#flush() call.
     */
    public const string preFlush = 'preFlush';

    /**
     * The onFlush event occurs when the DocumentManager#flush() operation is invoked,
     * after any changes to managed documents have been determined but before any
     * actual database operations are executed. The event is only raised if there is
     * actually something to do for the underlying UnitOfWork. If nothing needs to be done,
     * the onFlush event is not raised.
     */
    public const string onFlush = 'onFlush';

    /**
     * The postFlush event occurs when the DocumentManager#flush() operation is invoked and
     * after all actual database operations are executed successfully. The event is only raised if there is
     * actually something to do for the underlying UnitOfWork. If nothing needs to be done,
     * the postFlush event is not raised. The event won't be raised if an error occurs during the
     * flush operation.
     */
    public const string postFlush = 'postFlush';

    /**
     * The onClear event occurs when the DocumentManager#clear() operation is invoked,
     * after all references to documents have been removed from the unit of work.
     */
    public const string onClear = 'onClear';

    /**
     * The documentNotFound event occurs if a lazy object could not be found in
     * the database.
     */
    public const string documentNotFound = 'documentNotFound';

    /**
     * The postCollectionLoad event occurs after collection is initialized (loaded).
     */
    public const string postCollectionLoad = 'postCollectionLoad';
}
