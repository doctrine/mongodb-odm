<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tools\Console\Helper;

use Doctrine\ODM\MongoDB\DocumentManager;
use ReflectionMethod;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Helper\HelperInterface;

if ((new ReflectionMethod(HelperInterface::class, 'getName'))->hasReturnType()) {
    /** @internal */
    trait DocumentManagerHelperCompatibility
    {
        public function getName(): string
        {
            return 'documentManager';
        }
    }
} else {
    /** @internal */
    trait DocumentManagerHelperCompatibility
    {
        /** @return string */
        public function getName()
        {
            return 'documentManager';
        }
    }
}

/**
 * Symfony console component helper for accessing a DocumentManager instance.
 */
class DocumentManagerHelper extends Helper
{
    use DocumentManagerHelperCompatibility;

    /** @var DocumentManager */
    protected $dm;

    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    public function getDocumentManager(): DocumentManager
    {
        return $this->dm;
    }
}
