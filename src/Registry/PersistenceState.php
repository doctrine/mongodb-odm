<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Registry;

/**
 * The persistence state of a document tracked by the DocumentManager.
 *
 * @internal
 */
enum PersistenceState
{
    /** The document has just been instantiated and is not (yet) managed by a DocumentManager. */
    case New;

    /** The document's persistence is managed by a DocumentManager. */
    case Managed;

    /** The document has a persistent identity but is not (or no longer) associated with a DocumentManager. */
    case Detached;

    /** The document's persistent state has been deleted (or is scheduled for deletion). */
    case Removed;
}
