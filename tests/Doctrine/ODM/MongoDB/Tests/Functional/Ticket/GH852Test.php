<?php

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH852Test extends BaseTest
{
    /**
     * @dataProvider provideIdGenerators
     */
    public function testA(\Closure $idGenerator)
    {
        $parent = new GH852Document();
        $parent->id = $idGenerator('parent');
        $parent->name = 'parent';

        $childA = new GH852Document();
        $childA->id = $idGenerator('childA');
        $childA->name = 'childA';

        $childB = new GH852Document();
        $childB->id = $idGenerator('childB');
        $childB->name = 'childB';

        $childC = new GH852Document();
        $childC->id = $idGenerator('childC');
        $childC->name = 'childC';

        $parent->refOne = $childA;
        $parent->refMany[] = $childB;
        $parent->refMany[] = $childC;

        $this->dm->persist($parent);
        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find(get_class($parent), $idGenerator('parent'));
        $this->assertNotNull($parent);
        $this->assertEquals($idGenerator('parent'), $parent->id);
        $this->assertEquals('parent', $parent->name);

        $this->assertInstanceOf('Doctrine\ODM\MongoDB\Proxy\Proxy', $parent->refOne);
        $this->assertFalse($parent->refOne->__isInitialized());
        $this->assertEquals($idGenerator('childA'), $parent->refOne->id);
        $this->assertEquals('childA', $parent->refOne->name);
        $this->assertTrue($parent->refOne->__isInitialized());

        $this->assertCount(2, $parent->refMany);

        /* These proxies will be initialized when we first access the collection
         * by DocumentPersister::loadReferenceManyCollectionOwningSide().
         */
        $this->assertInstanceOf('Doctrine\ODM\MongoDB\Proxy\Proxy', $parent->refMany[0]);
        $this->assertTrue($parent->refMany[0]->__isInitialized());
        $this->assertEquals($idGenerator('childB'), $parent->refMany[0]->id);
        $this->assertEquals('childB', $parent->refMany[0]->name);

        $this->assertInstanceOf('Doctrine\ODM\MongoDB\Proxy\Proxy', $parent->refMany[1]);
        $this->assertTrue($parent->refMany[1]->__isInitialized());
        $this->assertEquals($idGenerator('childC'), $parent->refMany[1]->id);
        $this->assertEquals('childC', $parent->refMany[1]->name);

        // these lines are relevant for $useKeys = false in ReferencePrimer::__construct()
        $this->dm->clear();
        $docs = $this->dm->createQueryBuilder(get_class($parent))
                ->field('name')->equals('parent')
                ->field('refMany')->prime()
                ->getQuery()->execute();
        $this->assertCount(1, $docs);
        $this->assertCount(2, $docs->current()->refMany);

        // these lines are relevant for $useKeys = false in EagerCursor::initialize()
        $this->dm->clear();
        $docs = $this->dm->createQueryBuilder(get_class($parent))
                ->eagerCursor()->getQuery()->execute();
        $this->assertCount(4, $docs);

        // these lines are relevant for $useKeys = false in DocumentRepository::matching()
        $this->dm->clear();
        $docs = $this->dm->getRepository(get_class($parent))
                ->matching(new \Doctrine\Common\Collections\Criteria());
        $this->assertCount(4, $docs);
    }

    public function provideIdGenerators()
    {
        // MongoBinData::GENERIC may not be defined for driver versions before 1.5.0
        $binDataType = defined('MongoBinData::GENERIC') ? \MongoBinData::GENERIC : 0;

        return array(
            array(function($id) { return array('foo' => $id); }),
            array(function($id) use ($binDataType) { return new \MongoBinData($id, $binDataType); }),
        );
    }
}

/** @ODM\Document */
class GH852Document
{
    /** @ODM\Id(strategy="NONE", type="custom_id") */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;

    /**
     * @ODM\ReferenceOne(targetDocument="GH852Document", cascade="all")
     */
    public $refOne;

    /**
     * @ODM\ReferenceMany(targetDocument="GH852Document", cascade="all")
     */
    public $refMany;

    public function __construct()
    {
        $this->refMany = new ArrayCollection();
    }
}
