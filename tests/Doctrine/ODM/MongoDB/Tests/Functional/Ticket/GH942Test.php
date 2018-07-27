<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use MongoDB\BSON\ObjectId;

class GH942Test extends BaseTest
{
    public function testDiscriminatorValueUsesClassNameIfMapIsNotDefined()
    {
        $doc = new GH942Document();
        $doc->name = 'foo';

        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $doc = $this->dm->getDocumentCollection(GH942Document::CLASSNAME)
            ->findOne(['_id' => new ObjectId($doc->id)]);

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
            ->findOne(['_id' => new ObjectId($parent->id)]);

        $this->assertSame('parent', $parent['name']);
        $this->assertSame('p', $parent['type']);

        $child = $this->dm->getDocumentCollection(GH942DocumentChild::CLASSNAME)
            ->findOne(['_id' => new ObjectId($child->id)]);

        $this->assertSame('child', $child['name']);
        $this->assertSame(GH942DocumentChild::CLASSNAME, $child['type']);
    }
}

/**
 * @ODM\Document
 * @ODM\InheritanceType("SINGLE_COLLECTION")
 * @ODM\DiscriminatorField("type")
 */
class GH942Document
{
    public const CLASSNAME = __CLASS__;

    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;
}

/**
 * @ODM\Document
 * @ODM\InheritanceType("SINGLE_COLLECTION")
 * @ODM\DiscriminatorField("type")
 * @ODM\DiscriminatorMap({"p"=GH942DocumentParent::class})
 */
class GH942DocumentParent
{
    public const CLASSNAME = __CLASS__;

    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;
}

/** @ODM\Document */
class GH942DocumentChild extends GH942DocumentParent
{
    public const CLASSNAME = __CLASS__;
}
