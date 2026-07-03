<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Tools\Console\Command\Schema;

use Doctrine\ODM\MongoDB\Tests\Tools\Console\Command\AbstractCommandTestCase;
use Doctrine\ODM\MongoDB\Tools\Console\Command\Schema\CreateCommand;
use Documents\CmsArticle;
use Documents\User;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Console\Tester\CommandTester;

use function array_values;
use function preg_grep;
use function preg_split;

class CreateCommandTest extends AbstractCommandTestCase
{
    private CommandTester $commandTester;

    public function setUp(): void
    {
        parent::setUp();

        $this->application->addCommands([new CreateCommand()]);
        $this->commandTester = new CommandTester($this->application->find('odm:schema:create'));
    }

    public function testClassScopedCreateOrder(): void
    {
        $this->commandTester->execute([
            '--class' => User::class,
            '--skip-search-indexes' => true,
        ]);

        self::assertSame([
            'Created collection for Documents\User',
            'Created index(es) for Documents\User',
        ], $this->createdLines());
    }

    public function testCollectionOnly(): void
    {
        $this->commandTester->execute([
            '--class' => User::class,
            '--collection' => true,
        ]);

        self::assertSame(
            ['Created collection for Documents\User'],
            $this->createdLines(),
        );
    }

    public function testIndexOnly(): void
    {
        $this->commandTester->execute([
            '--class' => User::class,
            '--index' => true,
        ]);

        self::assertSame(
            ['Created index(es) for Documents\User'],
            $this->createdLines(),
        );
    }

    #[Group('atlas')]
    public function testClassScopedCreateIncludesSearchIndex(): void
    {
        $this->commandTester->execute(['--class' => CmsArticle::class]);

        self::assertSame([
            'Created collection for Documents\CmsArticle',
            'Created index(es) for Documents\CmsArticle',
            'Created search index(es) for Documents\CmsArticle',
        ], $this->createdLines());
    }

    /** @return list<string> */
    private function createdLines(): array
    {
        $lines = preg_split('/\R/', $this->commandTester->getDisplay()) ?: [];

        return array_values(preg_grep('/^Created /', $lines) ?: []);
    }
}
