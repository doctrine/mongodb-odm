<?php

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\DocumentRepository;

/**
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 */
class DocumentRepositoryTest extends BaseTest
{
    public function setUp()
    {
        $this->criteria      = array('name' => 'Woody');
        $this->result        = array('foo', 'bar');
        $this->classMetadata = new ClassMetadata('Doctrine\ODM\MongoDB\Tests\DocumentRepositoryTestDocument');
        $this->manager       = $this->getDocumentManagerMock();
        $this->unitOfWork    = $this->getUnitOfWorkMock();
    }

    public function testFindByCriteriaOnly()
    {
        $persister = $this->getDocumentPersisterMock();

        $this->unitOfWork->expects($this->once())
            ->method('getDocumentPersister')
            ->with($this->classMetadata->name)
            ->will($this->returnValue($persister));

        $persister->expects($this->once())
            ->method('loadAll')
            ->with($this->criteria)
            ->will($this->returnValue($this->result));

        $repository = new DocumentRepository($this->manager, $this->unitOfWork, $this->classMetadata);

        $this->assertEquals($this->result, $repository->findBy($this->criteria));
    }

    public function testFindByCriteriaAndOrderBy()
    {
        $sort = array('age', 'asc');
        $queryBuilder = $this->getQueryBuilderMock();
        $query = $this->getMockBuilder('stdClass')
            ->setMethods(array('execute'))
            ->getMock();

        $repository = $this->getRepositoryMockForQueryBuilderCreation($queryBuilder);

        $this->unitOfWork->expects($this->never())
            ->method('getDocumentPersister');
        $queryBuilder->expects($this->once())
            ->method('sort')
            ->with($sort[0], $sort[1]);
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->with()
            ->will($this->returnValue($query));
        $queryBuilder->expects($this->never())
            ->method('limit');
        $queryBuilder->expects($this->never())
            ->method('skip');

        $query->expects($this->once())
            ->method('execute')
            ->with()
            ->will($this->returnValue($this->result));

        $this->assertEquals($this->result, $repository->findBy($this->criteria, $sort));
    }

    public function testFindByCriteriaAndLimitAndOffset()
    {
        $limit = 10;
        $offset = 30;
        $queryBuilder = $this->getQueryBuilderMock();
        $query = $this->getMockBuilder('stdClass')
            ->setMethods(array('execute'))
            ->getMock();

        $repository = $this->getRepositoryMockForQueryBuilderCreation($queryBuilder);

        $this->unitOfWork->expects($this->never())
            ->method('getDocumentPersister');

        $queryBuilder->expects($this->never())
            ->method('sort');
        $queryBuilder->expects($this->once())
            ->method('limit')
            ->with($limit);
        $queryBuilder->expects($this->once())
            ->method('skip')
            ->with($offset);

        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->with()
            ->will($this->returnValue($query));

        $query->expects($this->once())
            ->method('execute')
            ->with()
            ->will($this->returnValue($this->result));

        $this->assertEquals($this->result, $repository->findBy($this->criteria, null, $limit, $offset));
    }

    protected function getRepositoryMockForQueryBuilderCreation($queryBuilder)
    {
        $repository = $this->getMockBuilder('Doctrine\ODM\MongoDB\DocumentRepository')
            ->setConstructorArgs(array($this->manager, $this->unitOfWork, $this->classMetadata))
            ->setMethods(array('createQueryBuilder'))
            ->getMock();

        $repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with()
            ->will($this->returnValue($queryBuilder));

        $queryBuilder->expects($this->once())
            ->method('setQueryArray')
            ->with($this->criteria);

        return $repository;
    }

    protected function getDocumentManagerMock()
    {
        return $this->getMockBuilder('Doctrine\ODM\MongoDB\DocumentManager')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function getDocumentPersisterMock()
    {
        return $this->getMockBuilder('Doctrine\ODM\MongoDB\Persisters\DocumentPersister')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function getUnitOfWorkMock()
    {
        return $this->getMockBuilder('Doctrine\ODM\MongoDB\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function getQueryBuilderMock()
    {
        return $this->getMockBuilder('Doctrine\ODM\MongoDB\Query\Builder')
            ->disableOriginalConstructor()
            ->getMock();
    }
}

class DocumentRepositoryTestDocument {}
