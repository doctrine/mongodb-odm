<?php

namespace Doctrine\ODM\MongoDB\Tests\Mapping\Driver;

use Doctrine\ODM\MongoDB\Mapping\Driver\YamlDriver;

/**
 * @author Bulat Shakirzyanov <bulat@theopenskyproject.com>
 */
class YamlDriverTest extends AbstractDriverTest
{
    public function setUp()
    {
        $this->driver = new YamlDriver(__DIR__ . '/fixtures/yaml');
    }
}