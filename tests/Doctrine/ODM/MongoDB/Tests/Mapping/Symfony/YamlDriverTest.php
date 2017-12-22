<?php

namespace Doctrine\ODM\MongoDB\Tests\Mapping\Symfony;

use Doctrine\ODM\MongoDB\Mapping\Driver\SimplifiedYamlDriver;

/**
 * @group DDC-1418
 */
class YamlDriverTest extends AbstractDriverTest
{
    protected function getFileExtension()
    {
        return '.mongodb-odm.yml';
    }

    protected function getDriver(array $paths = array())
    {
        $driver = new SimplifiedYamlDriver(array_flip($paths));

        return $driver;
    }
}
