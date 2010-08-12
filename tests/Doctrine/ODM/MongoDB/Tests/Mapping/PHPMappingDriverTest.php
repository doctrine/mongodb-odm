<?php

namespace Doctrine\ODM\MongoDB\Tests\Mapping;

use Doctrine\ODM\MongoDB\Mapping\Driver\PHPDriver;

require_once __DIR__ . '/../../../../../TestInit.php';

class PHPMappingDriverTest extends AbstractMappingDriverTest
{
    protected function _loadDriver()
    {
        return new PHPDriver(__DIR__ . DIRECTORY_SEPARATOR . 'php');
    }
}