<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Performance;

use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\CmsPhonenumber;
use Documents\CmsUser;
use const PHP_EOL;
use function current;
use function end;
use function floor;
use function gc_collect_cycles;
use function log;
use function memory_get_usage;
use function pow;
use function round;
use function sprintf;

/**
 * @group performance
 */
class MemoryUsageTest extends BaseTest
{
    /**
     * [jwage: Memory increased by 14.09 kb]
     */
    public function testMemoryUsage()
    {
        $memoryUsage = [];
        for ($i = 0; $i < 100; $i++) {
            $ph1              = new CmsPhonenumber();
            $ph1->phonenumber = '12345';
            $ph2              = new CmsPhonenumber();
            $ph2->phonenumber = '12346';

            $user           = new CmsUser();
            $user->username = 'jwage';
            $user->addPhonenumber($ph1);
            $user->addPhonenumber($ph2);

            $this->dm->persist($user);
            $this->dm->flush();
            $this->dm->clear();

            gc_collect_cycles();

            $memoryUsage[] = memory_get_usage();
        }

        $start = current($memoryUsage);
        $end   = end($memoryUsage);

        $increase = $end - $start;

        echo sprintf('Memory increased by %s', $this->formatMemory($increase)) . PHP_EOL;
    }

    private function formatMemory($size)
    {
        $unit = ['b', 'kb', 'mb', 'gb', 'tb', 'pb'];

        return round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
    }
}
