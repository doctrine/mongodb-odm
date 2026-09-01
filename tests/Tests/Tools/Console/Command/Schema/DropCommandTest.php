<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Tools\Console\Command\Schema;

use Doctrine\ODM\MongoDB\Tests\Tools\Console\Command\AbstractCommandTestCase;
use Doctrine\ODM\MongoDB\Tools\Console\Command\Schema\DropCommand;
use Documents\CmsArticle;
use Documents\User;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Console\Tester\CommandTester;

use function array_values;
use function preg_grep;
use function preg_split;

class DropCommandTest extends AbstractCommandTestCase
{
    private CommandTester $commandTester;

    public function setUp(): void
    {
        parent::setUp();

        $this->application->addCommands([new DropCommand()]);
        $this->commandTester = new CommandTester($this->application->find('odm:schema:drop'));

        // Pre-create the User collection so index-drop operations do not error
        // with "ns not found".
        $this->dm->getSchemaManager()->createDocumentCollection(User::class);
    }

    public function testClassScopedDropExcludesDatabase(): void
    {
        $this->commandTester->execute([
            '--class' => User::class,
            '--skip-search-indexes' => true,
        ]);

        self::assertSame([
            'Dropped index(es) for Documents\User',
            'Dropped collection for Documents\User',
        ], $this->droppedLines());
    }

    public function testClassScopedDropWithExplicitDbFlagDropsDatabase(): void
    {
        $this->commandTester->execute([
            '--class' => User::class,
            '--db' => true,
        ]);

        self::assertSame(
            ['Dropped database for Documents\User'],
            $this->droppedLines(),
        );
    }

    public function testCollectionOnly(): void
    {
        $this->commandTester->execute([
            '--class' => User::class,
            '--collection' => true,
        ]);

        self::assertSame(
            ['Dropped collection for Documents\User'],
            $this->droppedLines(),
        );
    }

    public function testIndexOnly(): void
    {
        $this->commandTester->execute([
            '--class' => User::class,
            '--index' => true,
        ]);

        self::assertSame(
            ['Dropped index(es) for Documents\User'],
            $this->droppedLines(),
        );
    }

    public function testClassScopedDropCombinedFlagsPreservesOrder(): void
    {
        $this->commandTester->execute([
            '--class' => User::class,
            '--collection' => true,
            '--index' => true,
        ]);

        self::assertSame([
            'Dropped index(es) for Documents\User',
            'Dropped collection for Documents\User',
        ], $this->droppedLines());
    }

    public function testAllClassesDropRunsFullOrderIncludingDatabase(): void
    {
        $this->commandTester->execute([
            '--collection' => true,
            '--db' => true,
        ]);

        self::assertSame([
            'Dropped collections for all classes',
            'Dropped databases for all classes',
        ], $this->droppedLines());
    }

    #[Group('atlas')]
    public function testClassScopedDropIncludesSearchIndex(): void
    {
        $sm = $this->dm->getSchemaManager();
        $sm->createDocumentCollection(CmsArticle::class);
        $sm->createDocumentSearchIndexes(CmsArticle::class);

        $this->commandTester->execute(['--class' => CmsArticle::class]);

        self::assertSame([
            'Dropped search index(es) for Documents\CmsArticle',
            'Dropped index(es) for Documents\CmsArticle',
            'Dropped collection for Documents\CmsArticle',
        ], $this->droppedLines());
    }

    /** @return list<string> */
    private function droppedLines(): array
    {
        $lines = preg_split('/\R/', $this->commandTester->getDisplay()) ?: [];

        return array_values(preg_grep('/^Dropped /', $lines) ?: []);
    }
}
