<?php

declare(strict_types=1);

namespace Doctrine\Tests\ODM\MongoDB\Performance;

use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\CmsUser;

use function gc_collect_cycles;
use function memory_get_usage;
use function microtime;

use const PHP_EOL;

/** @group performance */
class HydrationPerformanceTest extends BaseTest
{
    /**
     * [jwage: 10000 objects in ~6 seconds]
     *
     * @doesNotPerformAssertions
     */
    public function testHydrationPerformance(): void
    {
        $s = microtime(true);

        $batchSize = 20;
        for ($i = 1; $i <= 10000; ++$i) {
            $user           = new CmsUser();
            $user->status   = 'user';
            $user->username = 'user' . $i;
            $user->name     = 'Mr.Smith-' . $i;
            $this->dm->persist($user);
            if ($i % $batchSize !== 0) {
                continue;
            }

            $this->dm->flush();
            $this->dm->clear();
        }

        gc_collect_cycles();

        echo 'Memory usage before: ' . (memory_get_usage() / 1024) . ' KB' . PHP_EOL;

        $this->dm->getRepository(CmsUser::class)->findAll();

        $this->dm->clear();
        gc_collect_cycles();

        echo 'Memory usage after: ' . (memory_get_usage() / 1024) . ' KB' . PHP_EOL;

        $e = microtime(true);

        echo 'Hydrated 10000 objects in ' . ($e - $s) . ' seconds' . PHP_EOL;
    }
}
