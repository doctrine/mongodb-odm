<?php

namespace Doctrine\ODM\MongoDB\Tests\Persisters;

use Documents\Ecommerce\ConfigurableProduct;

use Doctrine\ODM\MongoDB\Persisters\DataPreparer;

class DataPreparerTest extends \PHPUnit_Framework_TestCase
{
    private $dataPreparer;
    private $dm;
    private $uow;

    public function setUp()
    {
        $this->dm = $this->getMockDocumentManager();
        $this->uow = $this->getMockUnitOfWork();
        $this->dataPreparer = new DataPreparer($this->dm, $this->uow, '$');
    }

    public function testPrepareInsertData()
    {
        $document = new ConfigurableProduct('Test Product');

        $classMetadata = $this->getMockClassMetadata();
        $classMetadata->fieldMappings = array(
            'id' => array(
                'name'      => 'id',
                'fieldName' => 'id',
                'type'      => 'id',
                'nullable' => 'false',
            ),
            'name' => array(
                'name'      => 'name',
                'fieldName' => 'name',
                'type'      => 'string',
                'nullable' => 'false',
            ),
        );
        $classMetadata->identifier = 'id';
        $classMetadata->expects($this->exactly(2))
            ->method('isIdentifier')
            ->will($this->returnCallback(function ($name)
            {
                return 'id' === $name;
            }))
        ;

        $this->dm->expects($this->once())
            ->method('getClassMetadata')
            ->with(get_class($document))
            ->will($this->returnValue($classMetadata))
        ;

        $this->uow->expects($this->once())
            ->method('getDocumentChangeSet')
            ->with($document)
            ->will($this->returnValue(array(
                'name' => array(null, 'Test Product'),
            )))
        ;

        $this->assertEquals(array(
            'name' => 'Test Product',
            '_id'   => new \MongoId(),
        ), $this->dataPreparer->prepareInsertData($document));
    }

    private function getMockDocumentManager()
    {
        return $this->getMockBuilder('Doctrine\ODM\MongoDB\DocumentManager')
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getMockUnitOfWork()
    {
        return $this->getMockBuilder('Doctrine\ODM\MongoDB\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getMockClassMetadata()
    {
        return $this->getMockBuilder('Doctrine\ODM\MongoDB\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
    }

}