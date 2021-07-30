<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Repository\ViewRepository;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Documents\CmsUser;
use Documents\UserName;
use Documents\ViewReference;
use ProxyManager\Proxy\GhostObjectInterface;

use function assert;

class ViewTest extends BaseTest
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

        $this->assertSame($expectedPipeline, $builder->getPipeline());
    }

    public function testQueryOnView(): void
    {
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

    public function testViewReferences(): void
    {
        $alcaeus = $this->dm->getRepository(UserName::class)->findOneBy(['username' => 'alcaeus']);
        $this->assertInstanceOf(UserName::class, $alcaeus);
        $malarzm = $this->dm->getRepository(UserName::class)->findOneBy(['username' => 'malarzm']);
        $this->assertInstanceOf(UserName::class, $malarzm);

        $viewReference = new ViewReference($alcaeus->getId());
        $viewReference->setReferenceOneView($malarzm);
        $viewReference->addReferenceManyView($malarzm);

        $this->dm->persist($viewReference);
        $this->dm->flush();

        $this->dm->clear();

        // Load users to avoid proxy objects

        $alcaeus = $this->dm->getRepository(UserName::class)->findOneBy(['username' => 'alcaeus']);
        $this->assertInstanceOf(UserName::class, $alcaeus);
        $malarzm = $this->dm->getRepository(UserName::class)->findOneBy(['username' => 'malarzm']);
        $this->assertInstanceOf(UserName::class, $malarzm);

        $viewReference = $this->dm->find(ViewReference::class, $alcaeus->getId());
        $this->assertInstanceOf(ViewReference::class, $viewReference);

        $this->assertSame($malarzm, $viewReference->getReferenceOneView());

        // No proxies for inverse referenceOne
        $this->assertSame($alcaeus, $viewReference->getReferenceOneViewMappedBy());

        $this->assertCount(1, $viewReference->getReferenceManyView());
        $this->assertSame($malarzm, $viewReference->getReferenceManyView()[0]);

        // No proxies for inverse referenceMany
        $this->assertCount(1, $viewReference->getReferenceManyViewMappedBy());
        $this->assertSame($alcaeus, $viewReference->getReferenceManyViewMappedBy()[0]);

        // Clear document manager again, load ViewReference without loading users first

        $this->dm->clear();

        $viewReference = $this->dm->find(ViewReference::class, $alcaeus->getId());
        $this->assertInstanceOf(ViewReference::class, $viewReference);

        $this->assertInstanceOf(GhostObjectInterface::class, $viewReference->getReferenceOneView());
        $this->assertSame($malarzm->getId(), $viewReference->getReferenceOneView()->getId());

        // No proxies for inverse referenceOne
        $this->assertInstanceOf(UserName::class, $viewReference->getReferenceOneViewMappedBy());
        $this->assertSame($alcaeus->getId(), $viewReference->getReferenceOneViewMappedBy()->getId());

        $this->assertCount(1, $viewReference->getReferenceManyView());
        $this->assertInstanceOf(GhostObjectInterface::class, $viewReference->getReferenceManyView()[0]);
        $this->assertSame($malarzm->getId(), $viewReference->getReferenceManyView()[0]->getId());

        // No proxies for inverse referenceMany
        $this->assertCount(1, $viewReference->getReferenceManyViewMappedBy());
        $this->assertInstanceOf(UserName::class, $viewReference->getReferenceManyViewMappedBy()[0]);
        $this->assertSame($alcaeus->getId(), $viewReference->getReferenceManyViewMappedBy()[0]->getId());
    }
}
