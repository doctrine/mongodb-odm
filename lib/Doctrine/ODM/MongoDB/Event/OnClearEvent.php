<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Event;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Persistence\Event\OnClearEventArgs as BaseOnClearEventArgs;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Provides event arguments for the onClear event.
 *
 * @template-extends BaseOnClearEventArgs<DocumentManager>
 */
final class OnClearEvent extends Event
{
    use HasDocumentManager;
}
