<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Query;

use Doctrine\ODM\MongoDB\Query\Filter\BsonFilter;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\User;
use InvalidArgumentException;

class FilterCollectionTest extends BaseTest
{
    public function testEnable(): void
    {
        $filterCollection = $this->dm->getFilterCollection();

        self::assertEmpty($filterCollection->getEnabledFilters());

        $filterCollection->enable('testFilter');

        $enabledFilters = $filterCollection->getEnabledFilters();
        self::assertCount(1, $enabledFilters);
        self::assertContainsOnly(BsonFilter::class, $enabledFilters);

        $filterCollection->disable('testFilter');
        self::assertEmpty($filterCollection->getEnabledFilters());
    }

    public function testHasFilter(): void
    {
        $filterCollection = $this->dm->getFilterCollection();

        self::assertTrue($filterCollection->has('testFilter'));
        self::assertFalse($filterCollection->has('fakeFilter'));
    }

    /** @depends testEnable */
    public function testIsEnabled(): void
    {
        $filterCollection = $this->dm->getFilterCollection();

        self::assertFalse($filterCollection->isEnabled('testFilter'));

        $filterCollection->enable('testFilter');

        self::assertTrue($filterCollection->isEnabled('testFilter'));
    }

    public function testGetFilterInvalidArgument(): void
    {
        $filterCollection = $this->dm->getFilterCollection();
        $this->expectException(InvalidArgumentException::class);
        $filterCollection->getFilter('testFilter');
    }

    public function testGetFilter(): void
    {
        $filterCollection = $this->dm->getFilterCollection();
        $filterCollection->enable('testFilter');
        self::assertInstanceOf(Filter\Filter::class, $filterCollection->getFilter('testFilter'));
    }

    public function testGetFilterCriteria(): void
    {
        $class            = $this->dm->getClassMetadata(User::class);
        $filterCollection = $this->dm->getFilterCollection();

        self::assertEmpty($filterCollection->getFilterCriteria($class));

        $filterCollection->enable('testFilter');
        $testFilter = $filterCollection->getFilter('testFilter');
        $testFilter->setParameter('class', User::class);
        $testFilter->setParameter('field', 'username');
        $testFilter->setParameter('value', 'Tim');

        self::assertSame(['username' => 'Tim'], $filterCollection->getFilterCriteria($class));
    }

    public function testGetFilterCriteriaMergesCriteria(): void
    {
        $class            = $this->dm->getClassMetadata(User::class);
        $filterCollection = $this->dm->getFilterCollection();

        $filterCollection->enable('testFilter');
        $testFilter = $filterCollection->getFilter('testFilter');
        $testFilter->setParameter('class', User::class);
        $testFilter->setParameter('field', 'username');
        $testFilter->setParameter('value', 'Tim');

        $filterCollection->enable('testFilter2');
        $testFilter = $filterCollection->getFilter('testFilter2');
        $testFilter->setParameter('class', User::class);
        $testFilter->setParameter('field', 'username');
        $testFilter->setParameter('value', 'John');

        $expectedCriteria = [
            '$and' => [
                ['username' => 'Tim'],
                ['username' => 'John'],
            ],
        ];

        self::assertSame($expectedCriteria, $filterCollection->getFilterCriteria($class));
    }
}
