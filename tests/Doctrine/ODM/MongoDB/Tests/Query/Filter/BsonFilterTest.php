<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Query\Filter;

use Doctrine\ODM\MongoDB\Tests\BaseTest;
use InvalidArgumentException;

class BsonFilterTest extends BaseTest
{
    public function testGetParameterInvalidArgument(): void
    {
        $filter = new Filter($this->dm);
        $this->expectException(InvalidArgumentException::class);
        $filter->getParameter('doesnotexist');
    }

    public function testSetParameter(): void
    {
        $filter = new Filter($this->dm);
        $filter->setParameter('username', 'Tim');
        self::assertEquals('Tim', $filter->getParameter('username'));
    }

    public function testGetNullParameter(): void
    {
        $filter = new Filter($this->dm);
        $filter->setParameter('foo', null);
        self::assertNull($filter->getParameter('foo'));
    }
}
