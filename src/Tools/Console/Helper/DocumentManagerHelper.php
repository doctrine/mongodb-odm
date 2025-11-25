<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tools\Console\Helper;

use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Console\Helper\Helper;

/**
 * Symfony console component helper for accessing a DocumentManager instance.
 */
class DocumentManagerHelper extends Helper
{
    public function __construct(protected DocumentManager $dm)
    {
    }

    public function getDocumentManager(): DocumentManager
    {
        return $this->dm;
    }

    public function getName(): string
    {
        return 'documentManager';
    }
}
