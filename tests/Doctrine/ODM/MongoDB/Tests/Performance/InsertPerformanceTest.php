<?php

namespace Doctrine\Tests\ODM\MongoDB\Performance;

use Documents\CmsUser;

/**
 * @group performance
 */
class InsertPerformanceTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    /**
     * [jwage: 10000 objects in ~4 seconds]
     */
    public function testInsertPerformance()
    {
        $s = microtime(true);

        echo "Memory usage before: " . (memory_get_usage() / 1024) . " KB" . PHP_EOL;

        $batchSize = 20;
        for ($i = 1; $i <= 10000; ++$i) {
            $user = new CmsUser;
            $user->status = 'user';
            $user->username = 'user' . $i;
            $user->name = 'Mr.Smith-' . $i;
            $this->dm->persist($user);
            if (($i % $batchSize) == 0) {
                $this->dm->flush();
                $this->dm->clear();
            }
        }

        gc_collect_cycles();
        echo "Memory usage after: " . (memory_get_usage() / 1024) . " KB" . PHP_EOL;

        $e = microtime(true);

        echo 'Inserted 10000 objects in ' . ($e - $s) . ' seconds' . PHP_EOL;
    }
}
