<?php

namespace Doctrine\ODM\MongoDB\Tests\Mapping\Symfony;

use Doctrine\ODM\MongoDB\Mapping\Driver\SimplifiedXmlDriver;

/**
 * @group DDC-1418
 */
class XmlDriverTest extends AbstractDriverTest
{
    protected function getFileExtension()
    {
        return '.mongodb-odm.xml';
    }

    protected function getDriver(array $paths = array())
    {
        $driver = new SimplifiedXmlDriver(array_flip($paths));

        return $driver;
    }
}
