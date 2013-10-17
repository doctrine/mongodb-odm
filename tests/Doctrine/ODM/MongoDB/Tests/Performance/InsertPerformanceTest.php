<?php

namespace Doctrine\ODM\MongoDB\Tests\Performance;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @group performance
 */
class InsertPerformanceTest extends PerformanceTest
{
    public function testInsertPerformance()
    {
        $this->setMaxRunningTime(10);

        $s = microtime(true);

        echo "Memory usage before: " . (memory_get_usage() / 1024) . " KB" . PHP_EOL;

        $n = 10000;

        $batchSize = 20;
        for ($i = 1; $i <= $n; ++$i) {
            $user = new InsertPerformanceUser();
            $user->username = 'jwage' . $i;
            $user->name = 'Jon Wage-' . $i;
            $user->password = 'test';
            $this->dm->persist($user);
            if (($i % $batchSize) == 0) {
                $this->dm->flush();
                $this->dm->clear();
            }
        }

        gc_collect_cycles();
        echo "Memory usage after: " . (memory_get_usage() / 1024) . " KB" . PHP_EOL;

        $e = microtime(true);

        echo ' Inserted 10000 objects in ' . ($e - $s) . ' seconds' . PHP_EOL;
    }
}

/** @ODM\Document */
class InsertPerformanceUser
{
    /** @ODM\Id */
    public $id;

    /** @ODM\String */
    public $username;

    /** @ODM\String */
    public $name;

    /** @ODM\String */
    public $password;
}
