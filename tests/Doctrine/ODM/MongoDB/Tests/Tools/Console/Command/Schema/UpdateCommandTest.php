<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Tools\Console\Command\Schema;

use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;
use Doctrine\ODM\MongoDB\Tests\Tools\Console\Command\AbstractCommandTest;
use Doctrine\ODM\MongoDB\Tools\Console\Command\Schema\UpdateCommand;
use Documents\SchemaValidated;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class UpdateCommandTest extends AbstractCommandTest
{
    /** @var Command */
    protected $command;

    /** @var CommandTester */
    protected $commandTester;

    public function setUp(): void
    {
        parent::setUp();

        $this->application->addCommands(
            [
                new UpdateCommand(),
            ]
        );
        $command       = $this->application->find('odm:schema:update');
        $commandTester = new CommandTester($command);

        $this->command       = $command;
        $this->commandTester = $commandTester;
    }

    public function tearDown(): void
    {
        parent::tearDown();
        unset($this->command);
        unset($this->commandTester);
    }

    public function testProcessValidator()
    {
        $this->commandTester->execute(
            [
                '--class' => SchemaValidated::class,
            ]
        );
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Updated validation for Documents\SchemaValidated', $output);
    }

    public function testProcessValidators()
    {
        // Only load a subset of documents with legit annotations
        $annotationDriver = AnnotationDriver::create(__DIR__ . '/../../../../../../../../Documents/Ecommerce');
        $this->dm->getConfiguration()->setMetadataDriverImpl($annotationDriver);
        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Updated validation for all classes', $output);
    }
}
