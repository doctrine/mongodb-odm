<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use MongoDB\BSON\ObjectId;

class GH942Test extends BaseTestCase
{
    public function testDiscriminatorValueUsesClassNameIfMapIsNotDefined(): void
    {
        $doc       = new GH942Document();
        $doc->name = 'foo';

        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $doc = $this->dm->getDocumentCollection(GH942Document::CLASSNAME)
            ->findOne(['_id' => new ObjectId($doc->id)]);

        self::assertSame('foo', $doc['name']);
        self::assertSame(GH942Document::CLASSNAME, $doc['type']);
    }

    public function testDiscriminatorValueUsesClassNameIfNotInMap(): void
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

        self::assertSame('parent', $parent['name']);
        self::assertSame('p', $parent['type']);

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

    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
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

    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $name;
}

/** @ODM\Document */
class GH942DocumentChild extends GH942DocumentParent
{
    public const CLASSNAME = self::class;
}
