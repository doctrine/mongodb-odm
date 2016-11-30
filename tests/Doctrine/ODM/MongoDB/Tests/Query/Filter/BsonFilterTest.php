<?php

namespace Doctrine\ODM\MongoDB\Tests\Query\Filter;

class BsonFilterTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    /**
     * @expectedException InvalidArgumentException
     */
    public function testGetParameterInvalidArgument()
    {
        $filter = new Filter($this->dm);
        $filter->getParameter('doesnotexist');
    }

    public function testSetParameter()
    {
        $filter = new Filter($this->dm);
        $filter->setParameter('username', 'Tim');
        $this->assertEquals('Tim', $filter->getParameter('username'));
    }

    public function testGetNullParameter()
    {
        $filter = new Filter($this->dm);
        $filter->setParameter('foo', null);
        $this->assertNull($filter->getParameter('foo'));
    }
 
    public function testCreateMockOfFilter()
    {
        $this->createMock('\Doctrine\ODM\MongoDB\Query\Filter\BsonFilter');
    }
}
