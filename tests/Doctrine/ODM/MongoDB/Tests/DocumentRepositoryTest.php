<?php

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ODM\MongoDB\LockMode;

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

    public function testFindWithOptimisticLockAndNoDocumentFound()
    {
        $invalidId = 'test';

        $repository = $this->dm->getRepository('Documents\VersionedDocument');

        $document = $repository->find($invalidId, LockMode::OPTIMISTIC);
        $this->assertNull($document);
    }
}
