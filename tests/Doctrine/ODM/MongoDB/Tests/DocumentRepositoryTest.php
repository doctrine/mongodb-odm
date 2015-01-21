<?php

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use PHPUnit_Framework_MockObject_MockObject;

/**
 * @author Rudolph Gottesheim <r.gottesheim@loot.at>
 */
class DocumentRepositoryTest extends BaseTest
{
    /**
     * @var DocumentRepository
     */
    private $repository;

    public function setUp()
    {
        parent::setUp();

        $this->repository = new DocumentRepository($this->dm, $this->uow, new ClassMetadata('Documents\User'));
    }

    /**
     * This test raises an error if DocumentRepository::matching() called QueryExpressionVisitor::dispatch() with NULL
     * as the argument.
     */
    public function testMatchingAcceptsCriteriaWithoutWhere()
    {
        /** @var Criteria|PHPUnit_Framework_MockObject_MockObject $criteria */
        $criteria = $this->getMock('Doctrine\Common\Collections\Criteria');
        $criteria->expects($this->any())
            ->method('getWhereExpression')
            ->will($this->returnValue(null));
        $this->repository->matching($criteria);
    }
}
