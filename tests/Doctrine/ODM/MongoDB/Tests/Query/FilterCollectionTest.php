<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Query;

use Doctrine\ODM\MongoDB\Query\Filter\BsonFilter;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\User;

class FilterCollectionTest extends BaseTest
{
    public function testEnable()
    {
        $filterCollection = $this->dm->getFilterCollection();

        $this->assertCount(0, $filterCollection->getEnabledFilters());

        $filterCollection->enable('testFilter');

        $enabledFilters = $filterCollection->getEnabledFilters();
        $this->assertCount(1, $enabledFilters);
        $this->assertContainsOnly(BsonFilter::class, $enabledFilters);

        $filterCollection->disable('testFilter');
        $this->assertCount(0, $filterCollection->getEnabledFilters());
    }

    public function testHasFilter()
    {
        $filterCollection = $this->dm->getFilterCollection();

        $this->assertTrue($filterCollection->has('testFilter'));
        $this->assertFalse($filterCollection->has('fakeFilter'));
    }

    /**
     * @depends testEnable
     */
    public function testIsEnabled()
    {
        $filterCollection = $this->dm->getFilterCollection();

        $this->assertFalse($filterCollection->isEnabled('testFilter'));

        $filterCollection->enable('testFilter');

        $this->assertTrue($filterCollection->isEnabled('testFilter'));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testGetFilterInvalidArgument()
    {
        $filterCollection = $this->dm->getFilterCollection();
        $filterCollection->getFilter('testFilter');
    }

    public function testGetFilter()
    {
        $filterCollection = $this->dm->getFilterCollection();
        $filterCollection->enable('testFilter');
        $this->assertInstanceOf(Filter\Filter::class, $filterCollection->getFilter('testFilter'));
    }

    public function testGetFilterCriteria()
    {
        $class = $this->dm->getClassMetadata(User::class);
        $filterCollection = $this->dm->getFilterCollection();

        $this->assertEmpty($filterCollection->getFilterCriteria($class));

        $filterCollection->enable('testFilter');
        $testFilter = $filterCollection->getFilter('testFilter');
        $testFilter->setParameter('class', User::class);
        $testFilter->setParameter('field', 'username');
        $testFilter->setParameter('value', 'Tim');

        $this->assertSame(['username' => 'Tim'], $filterCollection->getFilterCriteria($class));
    }

    public function testGetFilterCriteriaMergesCriteria()
    {
        $class = $this->dm->getClassMetadata(User::class);
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

        $this->assertSame($expectedCriteria, $filterCollection->getFilterCriteria($class));
    }
}
