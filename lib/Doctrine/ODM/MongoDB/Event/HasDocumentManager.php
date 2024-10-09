<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Event;

use Doctrine\ODM\MongoDB\DocumentManager;

trait HasDocumentManager
{
    public function __construct(private DocumentManager $documentManager)
    {
    }

    public function getDocumentManager(): DocumentManager
    {
        return $this->documentManager;
    }
}
