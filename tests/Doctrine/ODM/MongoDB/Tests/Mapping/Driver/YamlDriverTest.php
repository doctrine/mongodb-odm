<?php

namespace Doctrine\ODM\MongoDB\Tests\Mapping\Driver;

use Doctrine\ODM\MongoDB\Mapping\Driver\YamlDriver;

/**
 * @author Bulat Shakirzyanov <bulat@theopenskyproject.com>
 * @group mapping_driver
 */
class YamlDriverTest extends AbstractDriverTest
{
    public function setUp()
    {
        if (!class_exists('Symfony\Component\Yaml\Yaml', true)) {
            $this->markTestSkipped('This test requires the Symfony YAML component');
        }

        $this->driver = new YamlDriver(__DIR__ . '/fixtures/yaml');
    }
}