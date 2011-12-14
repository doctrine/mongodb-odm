<?php

namespace Doctrine\ODM\MongoDB\Tests\Mapping\Driver;

use Doctrine\ODM\MongoDB\Mapping\Driver\XmlDriver;

/**
 * @author Bulat Shakirzyanov <bulat@theopenskyproject.com>
 */
class XmlDriverTest extends AbstractDriverTest
{
    public function setUp()
    {
        $this->driver = new XmlDriver(__DIR__ . '/fixtures/xml');
    }
}