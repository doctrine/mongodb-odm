<?php

declare(strict_types=1);

namespace Doctrine\Tests\ODM\MongoDB\Performance;

use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\CmsUser;

use function microtime;
use function str_replace;

use const PHP_EOL;

/**
 * @group performance
 */
class UnitOfWorkPerformanceTest extends BaseTest
{
    /**
     * [jwage: compute changesets for 10000 objects in ~10 seconds]
     */
    public function testComputeChanges()
    {
        $n     = 10000;
        $users = [];
        for ($i = 1; $i <= $n; ++$i) {
            $user           = new CmsUser();
            $user->status   = 'user';
            $user->username = 'user' . $i;
            $user->name     = 'Mr.Smith-' . $i;
            $this->dm->persist($user);
            $users[] = $user;
        }

        $this->dm->flush();

        foreach ($users as $user) {
            $user->status    = 'other';
            $user->username .= '++';
            $user->name      = str_replace('Mr.', 'Mrs.', $user->name);
        }

        $s = microtime(true);
        $this->dm->flush();
        $e = microtime(true);

        echo 'Compute ChangeSet ' . $n . ' objects in ' . ($e - $s) . ' seconds' . PHP_EOL;
    }
}
