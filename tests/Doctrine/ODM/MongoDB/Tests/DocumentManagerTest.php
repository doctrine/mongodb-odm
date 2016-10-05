<?php

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\Mocks\DocumentManagerMock;

class DocumentManagerTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testCustomRepository()
    {
        $this->assertInstanceOf('Documents\CustomRepository\Repository', $this->dm->getRepository('Documents\CustomRepository\Document'));
    }

    public function testCustomRepositoryMappedsuperclass()
    {
        $this->assertInstanceOf('Documents\BaseCategoryRepository', $this->dm->getRepository('Documents\BaseCategory'));
    }

    public function testCustomRepositoryMappedsuperclassChild()
    {
        $this->assertInstanceOf('Documents\BaseCategoryRepository', $this->dm->getRepository('Documents\Category'));
    }

    public function testGetConnection()
    {
        $this->assertInstanceOf('\Doctrine\MongoDB\Connection', $this->dm->getConnection());
    }

    public function testGetMetadataFactory()
    {
        $this->assertInstanceOf('\Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory', $this->dm->getMetadataFactory());
    }

    public function testGetConfiguration()
    {
        $this->assertInstanceOf('\Doctrine\ODM\MongoDB\Configuration', $this->dm->getConfiguration());
    }

    public function testGetUnitOfWork()
    {
        $this->assertInstanceOf('\Doctrine\ODM\MongoDB\UnitOfWork', $this->dm->getUnitOfWork());
    }

    public function testGetProxyFactory()
    {
        $this->assertInstanceOf('\Doctrine\ODM\MongoDB\Proxy\ProxyFactory', $this->dm->getProxyFactory());
    }

    public function testGetEventManager()
    {
        $this->assertInstanceOf('\Doctrine\Common\EventManager', $this->dm->getEventManager());
    }

    public function testGetSchemaManager()
    {
        $this->assertInstanceOf('\Doctrine\ODM\MongoDB\SchemaManager', $this->dm->getSchemaManager());
    }

    public function testCreateQueryBuilder()
    {
        $this->assertInstanceOf('\Doctrine\ODM\MongoDB\Query\Builder', $this->dm->createQueryBuilder());
    }

    public function testGetFilterCollection()
    {
        $this->assertInstanceOf('\Doctrine\ODM\MongoDB\Query\FilterCollection', $this->dm->getFilterCollection());
    }  
    
    public function testGetPartialReference()
    {
        $id = new \MongoId();
        $user = $this->dm->getPartialReference('Documents\CmsUser', $id);
        $this->assertTrue($this->dm->contains($user));
        $this->assertEquals($id, $user->id);
        $this->assertNull($user->getName());
    }

    public function testDocumentManagerIsClosedAccessor()
    {
        $this->assertTrue($this->dm->isOpen());
        $this->dm->close();
        $this->assertFalse($this->dm->isOpen());
    }

    public function dataMethodsAffectedByNoObjectArguments()
    {
        return array(
            array('persist'),
            array('remove'),
            array('merge'),
            array('refresh'),
            array('detach')
        );
    }

    /**
     * @dataProvider dataMethodsAffectedByNoObjectArguments
     * @expectedException \InvalidArgumentException
     * @param string $methodName
     */
    public function testThrowsExceptionOnNonObjectValues($methodName)
    {
        $this->dm->$methodName(null);
    }

    public function dataAffectedByErrorIfClosedException()
    {
        return array(
            array('flush'),
            array('persist'),
            array('remove'),
            array('merge'),
            array('refresh'),
        );
    }

    /**
     * @dataProvider dataAffectedByErrorIfClosedException
     * @param string $methodName
     */
    public function testAffectedByErrorIfClosedException($methodName)
    {
        $this->setExpectedException('Doctrine\ODM\MongoDB\MongoDBException', 'closed');

        $this->dm->close();
        if ($methodName === 'flush') {
            $this->dm->$methodName();
        } else {
            $this->dm->$methodName(new \stdClass());
        }
    }

    public function testGetDocumentCollectionAppliesClassMetadataSlaveOkay()
    {
        $cm1 = new ClassMetadataInfo('a');
        $cm1->collection = 'a';

        $cm2 = new ClassMetadataInfo('b');
        $cm2->collection = 'b';
        $cm2->slaveOkay = true;

        $cm3 = new ClassMetadataInfo('c');
        $cm3->collection = 'c';
        $cm3->slaveOkay = false;

        $map = array(
            array('a', $cm1),
            array('b', $cm2),
            array('c', $cm3),
        );

        $metadataFactory = $this->getMockClassMetadataFactory();
        $metadataFactory->expects($this->any())
            ->method('getMetadataFor')
            ->will($this->returnValueMap($map));

        $coll1 = $this->getMockCollection();
        $coll1->expects($this->never())
            ->method('setSlaveOkay');

        $coll2 = $this->getMockCollection();
        $coll2->expects($this->once())
            ->method('setSlaveOkay')
            ->with(true);

        $coll3 = $this->getMockCollection();
        $coll3->expects($this->once())
            ->method('setSlaveOkay')
            ->with(false);

        $dm = new DocumentManagerMock();
        $dm->metadataFactory = $metadataFactory;
        $dm->documentCollections = array(
            'a' => $coll1,
            'b' => $coll2,
            'c' => $coll3,
        );

        $dm->getDocumentCollection('a');
        $dm->getDocumentCollection('b');
        $dm->getDocumentCollection('c');
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Cannot create a DBRef for class Documents\User without an identifier. Have you forgotten to persist/merge the document first?
     */
    public function testCannotCreateDbRefWithoutId()
    {
        $d = new \Documents\User();
        $this->dm->createDBRef($d);
    }

    public function testCreateDbRefWithNonNullEmptyId()
    {
        $phonenumber = new \Documents\CmsPhonenumber();
        $phonenumber->phonenumber = 0;
        $this->dm->persist($phonenumber);

        $dbRef = $this->dm->createDBRef($phonenumber);

        $this->assertSame(array('$ref' => 'CmsPhonenumber', '$id' => 0), $dbRef);
    }

    /**
     * @expectedException \Doctrine\ODM\MongoDB\Mapping\MappingException
     * @expectedExceptionMessage Simple reference must not target document using Single Collection Inheritance, Documents\Tournament\Participant targeted.
     */
    public function testDisriminatedSimpleReferenceFails()
    {
        $d = new WrongSimpleRefDocument();
        $r = new \Documents\Tournament\ParticipantSolo('Maciej');
        $this->dm->persist($r);
        $class = $this->dm->getClassMetadata(get_class($d));
        $this->dm->createDBRef($r, $class->associationMappings['ref']);
    }

    public function testDifferentStoreAsDbReferences()
    {
        $r = new \Documents\User();
        $this->dm->persist($r);
        $d = new ReferenceStoreAsDocument();
        $class = $this->dm->getClassMetadata(get_class($d));

        $dbRef = $this->dm->createDBRef($r, $class->associationMappings['ref1']);
        $this->assertInstanceOf('MongoId', $dbRef);

        $dbRef = $this->dm->createDBRef($r, $class->associationMappings['ref2']);
        $this->assertCount(2, $dbRef);
        $this->assertArrayHasKey('$ref', $dbRef);
        $this->assertArrayHasKey('$id', $dbRef);

        $dbRef = $this->dm->createDBRef($r, $class->associationMappings['ref3']);
        $this->assertCount(3, $dbRef);
        $this->assertArrayHasKey('$ref', $dbRef);
        $this->assertArrayHasKey('$id', $dbRef);
        $this->assertArrayHasKey('$db', $dbRef);
    }

    private function getMockClassMetadataFactory()
    {
        return $this->createMock('Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory');
    }

    private function getMockCollection()
    {
        return $this->createMock('Doctrine\MongoDB\Collection');
    }
}

/** @ODM\Document */
class WrongSimpleRefDocument
{
    /** @ODM\Id */
    public $id;

    /** @ODM\ReferenceOne(targetDocument="Documents\Tournament\Participant", simple=true) */
    public $ref;
}

/** @ODM\Document */
class ReferenceStoreAsDocument
{
    /** @ODM\Id */
    public $id;

    /** @ODM\ReferenceOne(targetDocument="Documents\User", storeAs="id") */
    public $ref1;

    /** @ODM\ReferenceOne(targetDocument="Documents\User", storeAs="dbRef") */
    public $ref2;

    /** @ODM\ReferenceOne(targetDocument="Documents\User", storeAs="dbRefWithDb") */
    public $ref3;
}
