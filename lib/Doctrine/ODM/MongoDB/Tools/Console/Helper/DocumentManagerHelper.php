<?php

namespace Doctrine\ODM\MongoDB\Tools\Console\Helper;

use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Console\Helper\Helper;

/**
 * Symfony console component helper for accessing a DocumentManager instance.
 *
 * @since  1.0
 */
class DocumentManagerHelper extends Helper
{
    protected $dm;

    /**
     * Constructor.
     *
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    /**
     * Get the DocumentManager instance.
     *
     * @return DocumentManager
     */
    public function getDocumentManager()
    {
        return $this->dm;
    }

    /**
     * Get the canonical name of this helper.
     *
     * @see \Symfony\Component\Console\Helper\HelperInterface::getName()
     * @return string
     */
    public function getName()
    {
        return 'documentManager';
    }
}
