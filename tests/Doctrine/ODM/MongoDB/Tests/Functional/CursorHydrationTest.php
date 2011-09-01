<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Documents\User;

class CursorHydrationTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function setUp()
    {
        parent::setUp();

        $this->user = new User();
        $this->user->setUsername('foo');

        $this->dm->persist($this->user);
        $this->dm->flush();

        $this->repository = $this->dm->getRepository('Documents\User');
    }

    public function testCursorShouldHydrateCurrent()
    {
        $cursor = $this->repository->findAll();
        $cursor->next();

        $this->assertSame($this->user, $cursor->current());
    }

    public function testCursorShouldHydrateGetNext()
    {
        $cursor = $this->repository->findAll();

        $this->assertSame($this->user, $cursor->getNext());
    }
}
