<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use MongoDB\BSON\ObjectId;

class GH942Test extends BaseTest
{
    public function testDiscriminatorValueUsesClassNameIfMapIsNotDefined()
    {
        $doc       = new GH942Document();
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
        $parent       = new GH942DocumentParent();
        $parent->name = 'parent';
        $child        = new GH942DocumentChild();
        $child->name  = 'child';

        $this->dm->persist($parent);
        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->getDocumentCollection(GH942DocumentParent::CLASSNAME)
            ->findOne(['_id' => new ObjectId($parent->id)]);

        $this->assertSame('parent', $parent['name']);
        $this->assertSame('p', $parent['type']);

        $this->dm->persist($child);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Document class "' . GH942DocumentChild::class . '" is unlisted in the discriminator map.');
        $this->dm->flush();
    }
}

/**
 * @ODM\Document
 * @ODM\InheritanceType("SINGLE_COLLECTION")
 * @ODM\DiscriminatorField("type")
 */
class GH942Document
{
    public const CLASSNAME = self::class;

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
    public const CLASSNAME = self::class;

    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;
}

/** @ODM\Document */
class GH942DocumentChild extends GH942DocumentParent
{
    public const CLASSNAME = self::class;
}
