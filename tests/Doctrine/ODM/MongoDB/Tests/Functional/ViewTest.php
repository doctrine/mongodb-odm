<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Repository\ViewRepository;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Documents\CmsUser;
use Documents\UserName;
use function assert;

class ViewTest extends BaseTest
{
    public function testViewAggregationPipeline()
    {
        $repository = $this->dm->getRepository(UserName::class);
        assert($repository instanceof ViewRepository);

        $builder = $this->dm->createAggregationBuilder(CmsUser::class);

        $repository->createViewAggregation($builder);

        $expectedPipeline = [
            [
                '$project' => ['username' => true],
            ],
        ];

        $this->assertSame($expectedPipeline, $builder->getPipeline());
    }

    public function testQueryOnView()
    {
        $this->dm->getSchemaManager()->createDocumentCollection(UserName::class);

        foreach (['alcaeus', 'jmikola', 'jwage', 'malarzm'] as $username) {
            $user           = new CmsUser();
            $user->username = $username;
            $this->dm->persist($user);
        }

        $this->dm->flush();
        $this->dm->clear();

        $results = $this->dm->createQueryBuilder(UserName::class)
            ->sort('username')
            ->limit(1)
            ->getQuery()
            ->getIterator();

        $this->assertCount(1, $results);
        $user = $results->toArray()[0];

        $this->assertInstanceOf(UserName::class, $user);
        $this->assertSame('alcaeus', $user->getUsername());

        $this->assertSame(UnitOfWork::STATE_MANAGED, $this->dm->getUnitOfWork()->getDocumentState($user));
    }
}
