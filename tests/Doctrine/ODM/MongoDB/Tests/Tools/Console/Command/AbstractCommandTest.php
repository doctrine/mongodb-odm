<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Tools\Console\Command;

use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Doctrine\ODM\MongoDB\Tools\Console\Helper\DocumentManagerHelper;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\HelperSet;

abstract class AbstractCommandTest extends BaseTest
{
    /** @var Application */
    protected $application;

    public function setUp(): void
    {
        parent::setUp();

        $helperSet   = new HelperSet(
            [
                'dm' => new DocumentManagerHelper($this->dm),
            ]
        );
        $application = new Application('Doctrine MongoDB ODM');
        $application->setHelperSet($helperSet);
        $this->application = $application;
    }

    public function tearDown(): void
    {
        parent::tearDown();
        unset($this->application);
    }
}
