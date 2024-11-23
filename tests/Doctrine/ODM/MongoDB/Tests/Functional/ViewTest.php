<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Repository\ViewRepository;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Documents\CmsUser;
use Documents\UserName;
use Documents\ViewReference;

use function assert;

class ViewTest extends BaseTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->dm->getSchemaManager()->createDocumentCollection(UserName::class);

        foreach (['alcaeus', 'jmikola', 'jwage', 'malarzm'] as $username) {
            $user           = new CmsUser();
            $user->username = $username;
            $this->dm->persist($user);
        }

        $this->dm->flush();
        $this->dm->clear();
    }

    public function testViewAggregationPipeline(): void
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

        self::assertSame($expectedPipeline, $builder->getPipeline());
    }

    public function testQueryOnView(): void
    {
        $results = $this->dm->createQueryBuilder(UserName::class)
            ->sort('username')
            ->limit(1)
            ->getQuery()
            ->getIterator();

        self::assertCount(1, $results);
        $user = $results->toArray()[0];

        self::assertInstanceOf(UserName::class, $user);
        self::assertSame('alcaeus', $user->getUsername());

        self::assertSame(UnitOfWork::STATE_MANAGED, $this->dm->getUnitOfWork()->getDocumentState($user));
    }

    public function testViewReferences(): void
    {
        $alcaeus = $this->dm->getRepository(UserName::class)->findOneBy(['username' => 'alcaeus']);
        self::assertInstanceOf(UserName::class, $alcaeus);
        $malarzm = $this->dm->getRepository(UserName::class)->findOneBy(['username' => 'malarzm']);
        self::assertInstanceOf(UserName::class, $malarzm);

        $viewReference = new ViewReference($alcaeus->getId());
        $viewReference->setReferenceOneView($malarzm);
        $viewReference->addReferenceManyView($malarzm);

        $this->dm->persist($viewReference);
        $this->dm->flush();

        $this->dm->clear();

        // Load users to avoid proxy objects

        $alcaeus = $this->dm->getRepository(UserName::class)->findOneBy(['username' => 'alcaeus']);
        self::assertInstanceOf(UserName::class, $alcaeus);
        $malarzm = $this->dm->getRepository(UserName::class)->findOneBy(['username' => 'malarzm']);
        self::assertInstanceOf(UserName::class, $malarzm);

        $viewReference = $this->dm->find(ViewReference::class, $alcaeus->getId());
        self::assertInstanceOf(ViewReference::class, $viewReference);

        self::assertSame($malarzm, $viewReference->getReferenceOneView());

        // No proxies for inverse referenceOne
        self::assertSame($alcaeus, $viewReference->getReferenceOneViewMappedBy());

        self::assertCount(1, $viewReference->getReferenceManyView());
        self::assertSame($malarzm, $viewReference->getReferenceManyView()[0]);

        // No proxies for inverse referenceMany
        self::assertCount(1, $viewReference->getReferenceManyViewMappedBy());
        self::assertSame($alcaeus, $viewReference->getReferenceManyViewMappedBy()[0]);

        // Clear document manager again, load ViewReference without loading users first

        $this->dm->clear();

        $viewReference = $this->dm->find(ViewReference::class, $alcaeus->getId());
        self::assertInstanceOf(ViewReference::class, $viewReference);

        self::assertTrue(self::isLazyObject($viewReference->getReferenceOneView()));
        self::assertSame($malarzm->getId(), $viewReference->getReferenceOneView()->getId());

        // No proxies for inverse referenceOne
        self::assertInstanceOf(UserName::class, $viewReference->getReferenceOneViewMappedBy());
        self::assertSame($alcaeus->getId(), $viewReference->getReferenceOneViewMappedBy()->getId());

        self::assertCount(1, $viewReference->getReferenceManyView());
        self::assertTrue(self::isLazyObject($viewReference->getReferenceManyView()[0]));
        self::assertSame($malarzm->getId(), $viewReference->getReferenceManyView()[0]->getId());

        // No proxies for inverse referenceMany
        self::assertCount(1, $viewReference->getReferenceManyViewMappedBy());
        self::assertInstanceOf(UserName::class, $viewReference->getReferenceManyViewMappedBy()[0]);
        self::assertSame($alcaeus->getId(), $viewReference->getReferenceManyViewMappedBy()[0]->getId());
    }
}
