<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\Common\Collections\Criteria;
use Documents\Account;
use Documents\Address;
use Documents\Phonenumber;
use Documents\User;

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

        $this->assertInternalType('array', $users);
        $this->assertCount(1, $users);
    }

    public function testFind()
    {
        $user2 = $this->repository->find($this->user->getId());
        $this->assertTrue($this->user === $user2);

        $user3 = $this->repository->findOneBy(array('username' => 'w00ting'));
        $this->assertTrue($user2 === $user3);
    }

    public function testCriteria()
    {
        $exprBuilder = Criteria::expr();
        $expr = $exprBuilder->eq('username', 'lolcat');

        $users = $this->repository->matching(new Criteria($expr));
        $this->assertCount(0, $users);

        $expr = $exprBuilder->eq('username', 'w00ting');

        $users = $this->repository->matching(new Criteria($expr));
        $this->assertCount(1, $users);
    }
}
