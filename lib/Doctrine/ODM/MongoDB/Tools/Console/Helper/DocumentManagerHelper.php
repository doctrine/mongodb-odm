<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tools\Console\Helper;

use Doctrine\ODM\MongoDB\DocumentManagerInterface;
use Symfony\Component\Console\Helper\Helper;

/**
 * Symfony console component helper for accessing a DocumentManager instance.
 */
class DocumentManagerHelper extends Helper
{
    /** @var DocumentManagerInterface */
    protected $dm;

    public function __construct(DocumentManagerInterface $dm)
    {
        $this->dm = $dm;
    }

    public function getDocumentManager() : DocumentManagerInterface
    {
        return $this->dm;
    }

    /**
     * Get the canonical name of this helper.
     *
     * @see \Symfony\Component\Console\Helper\HelperInterface::getName()
     */
    public function getName() : string
    {
        return 'documentManager';
    }
}
