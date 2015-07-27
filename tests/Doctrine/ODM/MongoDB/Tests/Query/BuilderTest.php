<?php

namespace Doctrine\ODM\MongoDB\Tests\Query;

use Doctrine\ODM\MongoDB\Query\Builder;

class BuilderTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    /**
     * @expectedException InvalidArgumentException
     */
    public function testPrimeRequiresBooleanOrCallable()
    {
        $this->dm->createQueryBuilder('Documents\User')
            ->field('groups')->prime(1);
    }
}
