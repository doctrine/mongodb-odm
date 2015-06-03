<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\MongoDB\Events;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @author Vaidas Lažauskas <vaidas@notrix.lt>
 */
class DiscriminatorsArrayCacheTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    /**
     * @var FindEventListener
     */
    protected $listener;

    public function testFindDocumentWithSingleCollectionInheritance()
    {
        $id = 123;

        $this->dm->persist(new DocumentChildSingle($id));
        $this->dm->flush();
        $this->dm->clear();

        $result1 = $this->dm->find(__NAMESPACE__ . '\DocumentChildSingle' , $id);
        $this->assertNotEmpty($result1);

        $result2 = $this->dm->find(__NAMESPACE__ . '\DocumentWithSingleDiscriminator' , $id);
        $this->assertNotEmpty($result2);
        $this->assertEquals(1, $this->listener->findCount);
    }

    public function testFindDocumentWithPerClassCollectionInheritance()
    {
        $id = 123;

        $this->dm->persist(new DocumentChildPerClass1($id));
        $this->dm->persist(new DocumentChildPerClass2($id));
        $this->dm->flush();
        $this->dm->clear();

        $result1 = $this->dm->find(__NAMESPACE__ . '\DocumentChildPerClass1' , $id);
        $this->assertNotEmpty($result1);
        $result2 = $this->dm->find(__NAMESPACE__ . '\DocumentChildPerClass2' , $id);
        $this->assertNotEmpty($result1);
        $this->assertNotSame($result1, $result2);

        $result3 = $this->dm->find(__NAMESPACE__ . '\DocumentWithPerClassDiscriminator' , $id);
        $this->assertNotEmpty($result3);
        $this->assertEquals(3, $this->listener->findCount);
    }

    public function setUp()
    {
        parent::setUp();

        $this->listener = new FindEventListener();

        $evm = $this->dm->getConnection()->getEventManager();
        $events = array(Events::preFind);
        $evm->addEventListener($events, $this->listener);
    }
}

class FindEventListener
{
    public $findCount = 0;

    public function collectionPreFind()
    {
        $this->findCount++;
    }
}

/** @ODM\Document(collection="discriminator_test1") */
abstract class AbstractParentDocument
{
    /** @ODM\Id(strategy="NONE") */
    protected $id;

    public function __construct($id = null)
    {
        $this->setId($id);
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }
}

/**
 * @ODM\Document(collection="discriminator_test1")
 * @ODM\InheritanceType("SINGLE_COLLECTION")
 * @ODM\DiscriminatorField(fieldName="type")
 * @ODM\DiscriminatorMap({"single"="DocumentChildSingle"})
 * @ODM\DefaultDiscriminatorValue("single")
 */
class DocumentWithSingleDiscriminator extends AbstractParentDocument
{
}

/** @ODM\Document(collection="discriminator_test1") */
class DocumentChildSingle extends DocumentWithSingleDiscriminator
{
}

/**
 * @ODM\Document(collection="discriminator_test1")
 * @ODM\InheritanceType("COLLECTION_PER_CLASS")
 * @ODM\DiscriminatorField(fieldName="type")
 * @ODM\DiscriminatorMap({"child1"="DocumentChildPerClass1","child2"="DocumentChildPerClass2"})
 * @ODM\DefaultDiscriminatorValue("child1")
 */
class DocumentWithPerClassDiscriminator extends AbstractParentDocument
{
}

/** @ODM\Document(collection="discriminator_test1") */
class DocumentChildPerClass1 extends DocumentWithPerClassDiscriminator
{
}

/** @ODM\Document(collection="discriminator_test2") */
class DocumentChildPerClass2 extends DocumentWithPerClassDiscriminator
{
}
