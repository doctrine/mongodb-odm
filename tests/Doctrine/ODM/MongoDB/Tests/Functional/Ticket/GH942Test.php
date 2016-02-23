<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use MongoId;

class GH942Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testDiscriminatorValueUsesClassNameIfMapIsNotDefined()
    {
        $doc = new GH942Document();
        $doc->name = 'foo';

        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $doc = $this->dm->getDocumentCollection(GH942Document::CLASSNAME)
            ->findOne(array('_id' => new MongoId($doc->id)));

        $this->assertSame('foo', $doc['name']);
        $this->assertSame(GH942Document::CLASSNAME, $doc['type']);
    }

    public function testDiscriminatorValueUsesClassNameIfNotInMap()
    {
        $parent = new GH942DocumentParent();
        $parent->name = 'parent';
        $child = new GH942DocumentChild();
        $child->name = 'child';

        $this->dm->persist($parent);
        $this->dm->persist($child);
        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->getDocumentCollection(GH942DocumentParent::CLASSNAME)
            ->findOne(array('_id' => new MongoId($parent->id)));

        $this->assertSame('parent', $parent['name']);
        $this->assertSame('p', $parent['type']);

        $child = $this->dm->getDocumentCollection(GH942DocumentChild::CLASSNAME)
            ->findOne(array('_id' => new MongoId($child->id)));

        $this->assertSame('child', $child['name']);
        $this->assertSame(GH942DocumentChild::CLASSNAME, $child['type']);
    }
}

/**
 * @ODM\Document
 * @ODM\InheritanceType("SINGLE_COLLECTION")
 * @ODM\DiscriminatorField(fieldName="type")
 */
class GH942Document
{
    const CLASSNAME = __CLASS__;

    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;
}

/**
 * @ODM\Document
 * @ODM\InheritanceType("SINGLE_COLLECTION")
 * @ODM\DiscriminatorField(fieldName="type")
 * @ODM\DiscriminatorMap({"p"="GH942DocumentParent"})
 */
class GH942DocumentParent
{
    const CLASSNAME = __CLASS__;

    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;
}

/** @ODM\Document */
class GH942DocumentChild extends GH942DocumentParent
{
    const CLASSNAME = __CLASS__;
}
