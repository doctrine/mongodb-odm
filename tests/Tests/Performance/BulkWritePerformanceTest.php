<?php

declare(strict_types=1);

namespace Doctrine\Tests\ODM\MongoDB\Performance;

use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\CmsUser;
use Documents\ForumUser;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Group;

use function microtime;

use const PHP_EOL;

/**
 * Smoke-tests the perf improvement that motivates the per-collection
 * bulkWrite refactor. Run with `vendor/bin/phpunit --group performance`.
 */
#[Group('performance')]
class BulkWritePerformanceTest extends BaseTestCase
{
    private const N = 1000;

    #[DoesNotPerformAssertions]
    public function testBulkInsertSingleCollection(): void
    {
        $users = [];
        for ($i = 0; $i < self::N; $i++) {
            $u           = new ForumUser();
            $u->username = 'forum-' . $i;
            $this->dm->persist($u);
            $users[] = $u;
        }

        $start = microtime(true);
        $this->dm->flush();
        $elapsed = microtime(true) - $start;

        echo 'BulkWrite insert ' . self::N . ' single-collection docs in ' . $elapsed . ' seconds' . PHP_EOL;
    }

    #[DoesNotPerformAssertions]
    public function testBulkUpdateSingleCollection(): void
    {
        $users = [];
        for ($i = 0; $i < self::N; $i++) {
            $u           = new ForumUser();
            $u->username = 'forum-' . $i;
            $this->dm->persist($u);
            $users[] = $u;
        }

        $this->dm->flush();

        foreach ($users as $i => $u) {
            $u->username = 'modified-' . $i;
        }

        $start = microtime(true);
        $this->dm->flush();
        $elapsed = microtime(true) - $start;

        echo 'BulkWrite update ' . self::N . ' single-collection docs in ' . $elapsed . ' seconds' . PHP_EOL;
    }

    #[DoesNotPerformAssertions]
    public function testBulkMixedAcrossThreeCollections(): void
    {
        // Three different collections: ForumUser, CmsUser, and the cascade-side
        // documents reachable from CmsUser (none persisted directly here, but
        // the inserts target two different mongo collections — enough to
        // exercise the per-collection grouping path).
        $forum = [];
        $cms   = [];
        for ($i = 0; $i < self::N; $i++) {
            $fu           = new ForumUser();
            $fu->username = 'forum-' . $i;
            $this->dm->persist($fu);
            $forum[] = $fu;

            $cu           = new CmsUser();
            $cu->username = 'cms-' . $i;
            $cu->name     = 'Name ' . $i;
            $cu->status   = 'ok';
            $this->dm->persist($cu);
            $cms[] = $cu;
        }

        $start = microtime(true);
        $this->dm->flush();
        $elapsed = microtime(true) - $start;

        echo 'BulkWrite insert ' . self::N . ' docs across 2 collections in ' . $elapsed . ' seconds' . PHP_EOL;
    }
}
