<?php

namespace Doctrine\ODM\MongoDB\Tests\Query;

class FilterCollectionTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testEnable()
    {
        $filterCollection = $this->dm->getFilterCollection();

        $this->assertCount(0, $filterCollection->getEnabledFilters());

        $filterCollection->enable('testFilter');

        $enabledFilters = $filterCollection->getEnabledFilters();
        $this->assertCount(1, $enabledFilters);
        $this->assertContainsOnly('Doctrine\ODM\MongoDB\Query\Filter\BsonFilter', $enabledFilters);

        $filterCollection->disable('testFilter');
        $this->assertCount(0, $filterCollection->getEnabledFilters());
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
        $this->assertInstanceOf('Doctrine\ODM\MongoDB\Tests\Query\Filter\Filter', $filterCollection->getFilter('testFilter'));
    }

    public function testGetFilterCriteria()
    {
        $filterCollection = $this->dm->getFilterCollection();
        $metadata = $this->dm->getClassMetadata('Documents\User');
        $criteria = $filterCollection->getFilterCriteria($metadata);

        $this->assertTrue(is_array($criteria));
        $this->assertEquals(0, count($criteria));

        $this->enableUserFilter();
        $criteria = $filterCollection->getFilterCriteria($metadata);
        $this->assertTrue(is_array($criteria));
        $this->assertArrayHasKey('username', $criteria);
        $this->assertEquals($criteria['username'], 'Tim');
    }

    protected function enableUserFilter()
    {
        $this->dm->getFilterCollection()->enable('testFilter');
        $testFilter = $this->dm->getFilterCollection()->getFilter('testFilter');
        $testFilter->setParameter('class', 'Documents\User');
        $testFilter->setParameter('field', 'username');
        $testFilter->setParameter('value', 'Tim');
    }
}
