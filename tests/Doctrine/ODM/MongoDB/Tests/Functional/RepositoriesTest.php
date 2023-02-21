<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\User;

class RepositoriesTest extends BaseTest
{
    private User $user;

    /** @var DocumentRepository<User> */
    private DocumentRepository $repository;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = new User();
        $this->user->setUsername('w00ting');

        $this->dm->persist($this->user);
        $this->dm->flush();

        $this->repository = $this->dm->getRepository(User::class);
    }

    public function testFindAll(): void
    {
        $users = $this->repository->findAll();

        self::assertIsArray($users);
        self::assertCount(1, $users);
    }

    public function testFind(): void
    {
        $user2 = $this->repository->find($this->user->getId());
        self::assertSame($this->user, $user2);

        $user3 = $this->repository->findOneBy(['username' => 'w00ting']);
        self::assertSame($user2, $user3);
    }

    public function testCriteria(): void
    {
        $exprBuilder = Criteria::expr();
        $expr        = $exprBuilder->eq('username', 'lolcat');

        $users = $this->repository->matching(new Criteria($expr));
        self::assertEmpty($users);

        $expr = $exprBuilder->eq('username', 'w00ting');

        $users = $this->repository->matching(new Criteria($expr));
        self::assertCount(1, $users);
    }
}
