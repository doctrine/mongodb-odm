<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\User;

class RepositoriesTest extends BaseTest
{
    /** @var User */
    private $user;

    /** @var DocumentRepository */
    private $repository;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = new User();
        $this->user->setUsername('w00ting');

        $this->dm->persist($this->user);
        $this->dm->flush();

        $this->repository = $this->dm->getRepository(User::class);
    }

    public function testFindAll()
    {
        $users = $this->repository->findAll();

        $this->assertIsArray($users);
        $this->assertCount(1, $users);
    }

    public function testFind()
    {
        $user2 = $this->repository->find($this->user->getId());
        $this->assertSame($this->user, $user2);

        $user3 = $this->repository->findOneBy(['username' => 'w00ting']);
        $this->assertSame($user2, $user3);
    }

    public function testCriteria()
    {
        $exprBuilder = Criteria::expr();
        $expr        = $exprBuilder->eq('username', 'lolcat');

        $users = $this->repository->matching(new Criteria($expr));
        $this->assertCount(0, $users);

        $expr = $exprBuilder->eq('username', 'w00ting');

        $users = $this->repository->matching(new Criteria($expr));
        $this->assertCount(1, $users);
    }
}
