<?php

namespace Doctrine\ODM\MongoDB\Tests\Performance;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @group performance
 */
class UnitOfWorkPerformanceTest extends PerformanceTest
{
    public function testComputeChanges()
    {
        $n = 10000;

        $users = array();
        for ($i = 1; $i <= $n; ++$i) {
            $user = new UnitOfWorkPerformanceUser();
            $user->username = 'jwage' . $i;
            $user->name = 'Jon Wage-' . $i;
            $user->password = 'test';
            $this->dm->persist($user);
            $users[] = $user;
        }
        $this->dm->flush();

        foreach ($users AS $user) {
            $user->username = 'jonwage';
            $user->name = str_replace('Jon', 'Jonathan', $user->name);
            $user->password = 'newpassword';
        }

        $s = microtime(true);
        $this->dm->flush();
        $e = microtime(true);

        echo ' Compute ChangeSet '.$n.' objects in ' . ($e - $s) . ' seconds' . PHP_EOL;
    }
}

/** @ODM\Document */
class UnitOfWorkPerformanceUser
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
