<?php

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\Common\Collections\Criteria;

/**
 * @author Rudolph Gottesheim <r.gottesheim@loot.at>
 */
class DocumentRepositoryTest extends BaseTest
{
    public function testMatchingAcceptsCriteriaWithNullWhereExpression()
    {
        $repository = $this->dm->getRepository('Documents\User');
        $criteria = new Criteria();

        $this->assertNull($criteria->getWhereExpression());
        $this->assertInstanceOf('Doctrine\Common\Collections\Collection', $repository->matching($criteria));
    }
}
