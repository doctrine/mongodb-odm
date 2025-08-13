<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Tools\Console\Command\Schema;

use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;
use Doctrine\ODM\MongoDB\Tests\Tools\Console\Command\AbstractCommandTestCase;
use Doctrine\ODM\MongoDB\Tools\Console\Command\Schema\UpdateCommand;
use Doctrine\Persistence\Mapping\Driver\FileClassLocator;
use Documents\SchemaValidated;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

use function class_exists;

class UpdateCommandTest extends AbstractCommandTestCase
{
    protected ?Command $command;

    protected ?CommandTester $commandTester;

    public function setUp(): void
    {
        parent::setUp();

        $this->application->addCommands(
            [
                new UpdateCommand(),
            ],
        );
        $command       = $this->application->find('odm:schema:update');
        $commandTester = new CommandTester($command);

        $this->command       = $command;
        $this->commandTester = $commandTester;
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->command       = null;
        $this->commandTester = null;
    }

    public function testProcessValidator(): void
    {
        $this->commandTester->execute(
            [
                '--class' => SchemaValidated::class,
            ],
        );
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('Updated validation for Documents\SchemaValidated', $output);
    }

    public function testDisabledValidatorProcessing(): void
    {
        $this->commandTester->execute(
            [
                '--class' => SchemaValidated::class,
                '--disable-validators' => true,
            ],
        );
        $output = $this->commandTester->getDisplay();
        self::assertStringNotContainsString('Updated validation for Documents\SchemaValidated', $output);
    }

    public function testProcessValidators(): void
    {
        // Only load a subset of documents with legit annotations
        $annotationDriver = $this->createDriver();
        $this->dm->getConfiguration()->setMetadataDriverImpl($annotationDriver);
        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('Updated validation for all classes', $output);
    }

    public function testDisabledValidatorsProcessing(): void
    {
        // Only load a subset of documents with legit annotations
        $annotationDriver = $this->createDriver();
        $this->dm->getConfiguration()->setMetadataDriverImpl($annotationDriver);
        $this->commandTester->execute(['--disable-validators' => true]);
        $output = $this->commandTester->getDisplay();
        self::assertStringNotContainsString('Updated validation for all classes', $output);
    }

    private function createDriver(): AnnotationDriver
    {
        $paths = [__DIR__ . '/../../../../Documents/SchemaValidated'];
        // Available in Doctrine Persistence 4.1+
        if (class_exists(FileClassLocator::class)) {
            $paths = FileClassLocator::createFromDirectories($paths);
        }

        return AnnotationDriver::create($paths);
    }
}
