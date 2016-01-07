<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class MappedSuperclassTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testCRUD()
    {
        $e = new DocumentSubClass;
        $e->setId(1);
        $e->setName('Roman');
        $e->setMapped1(42);
        $e->setMapped2('bar');
        
        $related = new MappedSuperclassRelated1();
        $related->setId(1);
        $related->setName('Related');
        $e->setMappedRelated1($related);

        $this->dm->persist($related);
        $this->dm->persist($e);
        $this->dm->flush();
        $this->dm->clear();

        $e2 = $this->dm->find('Doctrine\ODM\MongoDB\Tests\Functional\DocumentSubClass', 1);
        $this->assertNotNull($e2);
        $this->assertEquals(1, $e2->getId());
        $this->assertEquals('Roman', $e2->getName());
        $this->assertNotNull($e2->getMappedRelated1());
        $this->assertInstanceOf(__NAMESPACE__.'\MappedSuperclassRelated1', $e2->getMappedRelated1());
        $this->assertEquals(42, $e2->getMapped1());
        $this->assertEquals('bar', $e2->getMapped2());
    }
}

/** @ODM\MappedSuperclass */
class MappedSuperclassBase
{
    /** @ODM\Field(type="string") */
    private $mapped1;

    /** @ODM\Field(type="string") */
    private $mapped2;

    /**
     * @ODM\ReferenceOne(targetDocument="MappedSuperclassRelated1")
     */
    private $mappedRelated1;

    private $transient;

    public function setMapped1($val)
    {
        $this->mapped1 = $val;
    }

    public function getMapped1()
    {
        return $this->mapped1;
    }

    public function setMapped2($val)
    {
        $this->mapped2 = $val;
    }

    public function getMapped2()
    {
        return $this->mapped2;
    }

    public function setMappedRelated1($mappedRelated1)
    {
        $this->mappedRelated1 = $mappedRelated1;
    }

    public function getMappedRelated1()
    {
        return $this->mappedRelated1;
    }
}

/** @ODM\Document */
class MappedSuperclassRelated1
{
    /** @ODM\Id(strategy="none") */
    private $id;

    /** @ODM\Field(type="string") */
    private $name;

    public function setName($name)
    {
        $this->name = $name;
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function setId($id)
    {
        $this->id = $id;
    }
    
    public function getId()
    {
        return $this->id;
    }
}

/** @ODM\Document */
class DocumentSubClass extends MappedSuperclassBase
{
    /** @ODM\Id(strategy="none") */
    private $id;

    /** @ODM\Field(type="string") */
    private $name;
    
    public function setName($name)
    {
        $this->name = $name;
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function setId($id)
    {
        $this->id = $id;
    }
    
    public function getId()
    {
        return $this->id;
    }
}
