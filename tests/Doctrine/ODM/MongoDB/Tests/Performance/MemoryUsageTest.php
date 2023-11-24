<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Performance;

use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\CmsPhonenumber;
use Documents\CmsUser;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Group;

use function current;
use function end;
use function floor;
use function gc_collect_cycles;
use function log;
use function memory_get_usage;
use function round;
use function sprintf;

use const PHP_EOL;

#[Group('performance')]
class MemoryUsageTest extends BaseTestCase
{
    /**
     * [jwage: Memory increased by 14.09 kb]
     */
    #[DoesNotPerformAssertions]
    public function testMemoryUsage(): void
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

    private function formatMemory(int $size): string
    {
        $unit = ['b', 'kb', 'mb', 'gb', 'tb', 'pb'];

        return round($size / 1024 ** ($i = (int) floor(log($size, 1024))), 2) . ' ' . $unit[$i];
    }
}
