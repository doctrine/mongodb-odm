<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

class SingleDocumentFlushingTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testFlushSingleDocument()
    {
        $user = new \Documents\ForumUser();
        $user->username = 'сhucky';
        $this->dm->persist($user);
        $this->dm->flush($user);
    }
}
