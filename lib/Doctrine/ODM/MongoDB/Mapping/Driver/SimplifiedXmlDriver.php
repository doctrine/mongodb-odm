<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Driver;

use Doctrine\Persistence\Mapping\Driver\SymfonyFileLocator;

/**
 * XmlDriver that additionally looks for mapping information in a global file.
 */
class SimplifiedXmlDriver extends XmlDriver
{
    public const DEFAULT_FILE_EXTENSION = '.mongodb-odm.xml';

    /** @param string[] $prefixes */
    public function __construct($prefixes, $fileExtension = self::DEFAULT_FILE_EXTENSION)
    {
        $locator = new SymfonyFileLocator((array) $prefixes, $fileExtension);

        parent::__construct($locator, $fileExtension);
    }
}
