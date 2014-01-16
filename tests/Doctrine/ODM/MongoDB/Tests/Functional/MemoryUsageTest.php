<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Documents\CmsPhonenumber;
use Documents\CmsUser;

/**
 * @group performance
 */
class MemoryUsageTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    /**
     * Output for jwage "Memory increased by 14.09 kb"
     */
    public function testMemoryUsage()
    {
        $memoryUsage = array();
        for ($i = 0; $i < 100; $i++) {
            $ph1 = new CmsPhonenumber();
            $ph1->phonenumber = '12345';
            $ph2 = new CmsPhonenumber();
            $ph2->phonenumber = '12346';

            $user = new CmsUser();
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
        $end = end($memoryUsage);

        $increase = $end - $start;

        echo sprintf('Memory increased by %s', $this->formatMemory($increase));
    }

    private function formatMemory($size)
    {
        $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
        return round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
    }
}
