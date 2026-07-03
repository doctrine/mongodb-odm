<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Tools\Console\Command\Schema;

use Doctrine\ODM\MongoDB\Mapping\Driver\AttributeDriver;
use Doctrine\ODM\MongoDB\Tests\Tools\Console\Command\AbstractCommandTestCase;
use Doctrine\ODM\MongoDB\Tools\Console\Command\Schema\UpdateCommand;
use Doctrine\Persistence\Mapping\Driver\ClassNames;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Documents\CmsArticle;
use Documents\Ecommerce;
use Documents\SchemaValidated;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

use function array_values;
use function class_exists;
use function preg_grep;
use function preg_split;

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

    public function testClassScopedUpdateOrder(): void
    {
        $this->commandTester->execute([
            '--class' => SchemaValidated::class,
            '--skip-search-indexes' => true,
        ]);

        self::assertSame([
            'Updated index(es) for Documents\SchemaValidated',
            'Updated validation for Documents\SchemaValidated',
        ], $this->updatedLines());
    }

    #[Group('atlas')]
    public function testClassScopedUpdateIncludesSearchIndex(): void
    {
        $sm = $this->dm->getSchemaManager();
        $sm->createDocumentCollection(CmsArticle::class);
        $sm->createDocumentSearchIndexes(CmsArticle::class);

        $this->commandTester->execute(['--class' => CmsArticle::class]);

        self::assertSame([
            'Updated index(es) for Documents\CmsArticle',
            'Updated validation for Documents\CmsArticle',
            'Updated search index(es) for Documents\CmsArticle',
        ], $this->updatedLines());
    }

    /** @return list<string> */
    private function updatedLines(): array
    {
        $lines = preg_split('/\R/', $this->commandTester->getDisplay()) ?: [];

        return array_values(preg_grep('/^Updated /', $lines) ?: []);
    }

    private function createDriver(): MappingDriver
    {
        $paths = [__DIR__ . '/../../../../../Documents/Ecommerce'];
        // Available in Doctrine Persistence 4.1+
        if (class_exists(ClassNames::class)) {
            $paths = new ClassNames([
                Ecommerce\Basket::class,
                Ecommerce\ConfigurableProduct::class,
                Ecommerce\Currency::class,
                Ecommerce\Money::class,
                Ecommerce\Option::class,
                Ecommerce\Order::class,
                Ecommerce\StockItem::class,
            ]);
        }

        return AttributeDriver::create($paths);
    }
}
