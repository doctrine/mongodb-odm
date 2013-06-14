<?php

namespace Doctrine\ODM\MongoDB\Tests\Query\Filter;

/**
 * @group query_filter
 */
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
}
