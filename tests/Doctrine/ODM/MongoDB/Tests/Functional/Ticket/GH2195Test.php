<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use MongoDB\BSON\ObjectId;

class GH2195Test extends BaseTest
{
    /**
     * @var string
     */
    public $id;

    public function setUp() : void
    {
        parent::setUp();

        $this->id = (string) new ObjectId();

        $document     = new GH2195MainDocument();
        $document->id = $this->id;

        $document->property[] = new GH2195Level1(100, [[1, true], [2, true], [3, true]]);
        $document->property[] = new GH2195Level1(101, [[1, true], [2, true], [3, true]]);
        $document->property[] = new GH2195Level1(102, [[1, true], [2, true], [3, true]]);

        $document->property_old[] = new GH2195Level1(100, [[1, true], [2, true], [3, true]]);
        $document->property_old[] = new GH2195Level1(101, [[1, true], [2, true], [3, true]]);
        $document->property_old[] = new GH2195Level1(102, [[1, true], [2, true], [3, true]]);

        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();
    }

    public function testCollectionWithSimilarNames(): void
    {
        /** @var GH2195MainDocument $document */
        $document = $this->dm->find(GH2195MainDocument::class, $this->id);

        $this->assertNotNull($document);

        $document->property->remove(1);
        $document->property_old->remove(2);

        $this->dm->flush();
        $this->dm->refresh($document);

        $this->assertEquals(2, $document->property->count(), 'Should have deleted 2nd element');
        $this->assertEquals(2, $document->property_old->count(), 'Should have deleted 3rd element');
    }

    public function testSubcollectionRemove(): void
    {
        /** @var GH2195MainDocument $document */
        $document = $this->dm->find(GH2195MainDocument::class, $this->id);

        $this->assertNotNull($document);

        $document->property_old[1]->items->remove(0);
        $document->property_old->remove(2);

        $this->dm->flush();
        $this->dm->refresh($document);

        $this->assertEquals(2, $document->property_old[1]->items->count(), 'Should have deleted 1st element');
        $this->assertEquals(2, $document->property_old->count(), 'Should have deleted 3rd element');
    }
}

/** @ODM\Document */
class GH2195MainDocument
{
    /**
     * @ODM\Id
     *
     * @var string
     */
    public $id;

    /**
     * @ODM\EmbedMany(targetDocument=GH2195Level1::class, strategy="pushAll")
     */
    public $property;

    /**
     * @ODM\EmbedMany(targetDocument=GH2195Level1::class)
     */
    public $property_old;

    public function __construct()
    {
        $this->property = new ArrayCollection();
        $this->property_old = new ArrayCollection();
    }
}

/** @ODM\EmbeddedDocument */
class GH2195Level1
{
    /** @ODM\Field(type="int") */
    public $value;

    /** @ODM\EmbedMany(targetDocument=GH2195Level2::class, strategy="pushAll") */
    public $items;

    public function __construct(int $value, array $items)
    {
        $this->value = $value;
        $this->items = new ArrayCollection();
        foreach ($items as [$v, $f]) {
            $this->items[] = new GH2195Level2($v, $f);
        }
    }
}

/** @ODM\EmbeddedDocument */
class GH2195Level2
{
    /** @ODM\Field(type="int") */
    public $value;

    /** @ODM\Field(type="boolean") */
    public $is_flag;

    public function __construct(int $value, bool $is_flag)
    {
        $this->value   = $value;
        $this->is_flag = $is_flag;
    }
}
