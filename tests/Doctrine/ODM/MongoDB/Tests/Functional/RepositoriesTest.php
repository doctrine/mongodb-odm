<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Documents\Account,
    Documents\Address,
    Documents\Group,
    Documents\Phonenumber,
    Documents\Profile,
    Documents\File,
    Documents\User;

class RepositoriesTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function setUp()
    {
        parent::setUp();

        $this->user = new User();
        $this->user->setUsername('w00ting');

        $this->dm->persist($this->user);
        $this->dm->flush();

        $this->repository = $this->dm->getRepository('Documents\User');
    }

    public function testMagicMethods()
    {
        $user = $this->repository->findOneByUsername('w00ting');
        $this->assertEquals('w00ting', $user->getUsername());
    }

    public function testFindAll()
    {
        $users = $this->repository->findAll();

        $this->assertInstanceOf('Doctrine\ODM\MongoDB\Cursor', $users);
        $this->assertEquals(1, count($users));
    }

    public function testFind()
    {
        $user2 = $this->repository->find($this->user->getId());
        $this->assertTrue($this->user === $user2);

        $user3 = $this->repository->findOneBy(array('username' => 'w00ting'));
        $this->assertTrue($user2 === $user3);
    }
}