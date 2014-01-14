<?php

namespace Doctrine\Tests\ODM\MongoDB\Performance;

use Documents\CmsUser;

/**
 * @group performance
 */
class UnitOfWorkPerformanceTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    /**
     * [jwage: compute changesets for 10000 objects in ~10 seconds]
     */
    public function testComputeChanges()
    {
        $users = array();
        for ($i = 1; $i <= 10000; ++$i) {
            $user = new CmsUser;
            $user->status = 'user';
            $user->username = 'user' . $i;
            $user->name = 'Mr.Smith-' . $i;
            $this->dm->persist($user);
            $users[] = $user;
        }
        $this->dm->flush();

        foreach ($users as $user) {
            $user->status = 'other';
            $user->username = $user->username . '++';
            $user->name = str_replace('Mr.', 'Mrs.', $user->name);
        }

        $s = microtime(true);
        $this->dm->flush();
        $e = microtime(true);

        echo 'Compute ChangeSet '.$n.' objects in ' . ($e - $s) . ' seconds' . PHP_EOL;
    }
}
