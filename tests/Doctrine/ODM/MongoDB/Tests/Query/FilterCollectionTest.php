<?php

namespace Doctrine\ODM\MongoDB\Tests\Query;

class FilterCollectionTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testEnableFilterByConfiguration()
    {
        $filterCollection = $this->dm->getFilterCollection();

        $this->assertCount(1, $filterCollection->getEnabledFilters());
        $filterCollection->isEnabled('testFilter');
    }

    public function testEnable()
    {
        $filterCollection = $this->dm->getFilterCollection();

        $this->assertCount(1, $filterCollection->getEnabledFilters());

        $filterCollection->enable('testFilter');

        $enabledFilters = $filterCollection->getEnabledFilters();
        $this->assertCount(2, $enabledFilters);
        $this->assertContainsOnly('Doctrine\ODM\MongoDB\Query\Filter\BsonFilter', $enabledFilters);
    }

    public function testDisable()
    {
        $filterCollection = $this->dm->getFilterCollection();

        $this->assertCount(1, $filterCollection->getEnabledFilters());

        $filterCollection->disable('testFilter2');
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
        $this->assertInstanceOf('Doctrine\ODM\MongoDB\Tests\Query\Filter\Filter', $filterCollection->getFilter('testFilter'));
    }

    public function testGetFilterCriteria()
    {
        $class = $this->dm->getClassMetadata('Documents\User');
        $filterCollection = $this->dm->getFilterCollection();

        $this->assertSame(array(), $filterCollection->getFilterCriteria($class));

        $filterCollection->enable('testFilter');
        $testFilter = $filterCollection->getFilter('testFilter');
        $testFilter->setParameter('class', 'Documents\User');
        $testFilter->setParameter('field', 'username');
        $testFilter->setParameter('value', 'Tim');

        $this->assertSame(array('username' => 'Tim'), $filterCollection->getFilterCriteria($class));
    }

    public function testGetFilterCriteriaMergesCriteria()
    {
        $class = $this->dm->getClassMetadata('Documents\User');
        $filterCollection = $this->dm->getFilterCollection();

        $filterCollection->enable('testFilter');
        $testFilter = $filterCollection->getFilter('testFilter');
        $testFilter->setParameter('class', 'Documents\User');
        $testFilter->setParameter('field', 'username');
        $testFilter->setParameter('value', 'Tim');

        $filterCollection->enable('testFilter2');
        $testFilter = $filterCollection->getFilter('testFilter2');
        $testFilter->setParameter('class', 'Documents\User');
        $testFilter->setParameter('field', 'username');
        $testFilter->setParameter('value', 'John');

        $expectedCriteria = array('$and' => array(
            array('username' => 'John'),
            array('username' => 'Tim'),
        ));

        $this->assertSame($expectedCriteria, $filterCollection->getFilterCriteria($class));
    }
}
