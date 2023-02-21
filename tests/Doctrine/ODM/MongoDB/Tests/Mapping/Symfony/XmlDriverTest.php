<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Mapping\Symfony;

use Doctrine\ODM\MongoDB\Mapping\Driver\SimplifiedXmlDriver;

use function array_flip;

/** @group DDC-1418 */
class XmlDriverTest extends AbstractDriverTest
{
    protected function getFileExtension(): string
    {
        return '.mongodb-odm.xml';
    }

    protected function getDriver(array $paths = []): SimplifiedXmlDriver
    {
        return new SimplifiedXmlDriver(array_flip($paths));
    }
}
