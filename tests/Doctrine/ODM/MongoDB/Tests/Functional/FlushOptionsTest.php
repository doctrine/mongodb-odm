<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

require_once __DIR__ . '/../../../../../TestInit.php';

use Documents\User;

class FlushOptionsTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testFlushOptions()
    {
        $user = new User();
        $user->setUsername('jwage');
        $this->dm->persist($user);
        $this->dm->flush(array('safe' => true));

        $user->setUsername('ok');
        $this->dm->flush(array('safe' => true));
    }
}