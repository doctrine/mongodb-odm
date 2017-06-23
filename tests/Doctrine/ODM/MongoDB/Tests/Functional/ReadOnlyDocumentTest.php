<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class ReadOnlyDocumentTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testCanBeInserted()
    {
        $rod = new ReadOnlyDocument('yay');
        $this->dm->persist($rod);
        $this->dm->flush();
        $this->dm->clear();

        $rod = $this->dm->find(ReadOnlyDocument::class, $rod->id);
        $this->assertNotNull($rod);
        $this->assertSame('yay', $rod->value);
    }

    public function testCanBeUpserted()
    {
        $rod = new ReadOnlyDocument('yay');
        $rod->id = new \MongoId();
        $this->dm->persist($rod);
        $this->dm->flush();
        $this->dm->clear();

        $rod = $this->dm->find(ReadOnlyDocument::class, $rod->id);
        $this->assertNotNull($rod);
        $this->assertSame('yay', $rod->value);
    }

    public function testCanBeRemoved()
    {
        $rod = new ReadOnlyDocument('yay');
        $this->dm->persist($rod);
        $this->dm->flush();
        $this->dm->clear();

        $rod = $this->dm->find(ReadOnlyDocument::class, $rod->id);
        $this->dm->remove($rod);
        $this->dm->flush();
        $this->dm->clear();

        $rod = $this->dm->find(ReadOnlyDocument::class, $rod->id);
        $this->assertNull($rod);
    }

    public function testChangingValueDoesNotProduceChangeSet()
    {
        $rod = new ReadOnlyDocument('yay');
        $this->dm->persist($rod);
        $this->dm->flush();
        $rod->value = "o.O";
        $this->uow->recomputeSingleDocumentChangeSet($this->dm->getClassMetadata(ReadOnlyDocument::class), $rod);
        $this->assertEmpty($this->uow->getDocumentChangeSet($rod));
    }

    public function testCantBeUpdated()
    {
        $rod = new ReadOnlyDocument('yay');
        $this->dm->persist($rod);
        $this->dm->flush();
        $this->dm->clear();

        $rod = $this->dm->find(ReadOnlyDocument::class, $rod->id);
        $rod->value = "o.O";
        $this->dm->flush();
        $this->dm->clear();

        $rod = $this->dm->find(ReadOnlyDocument::class, $rod->id);
        $this->assertSame('yay', $rod->value);
    }
}

/** @ODM\Document(readOnly=true) */
class ReadOnlyDocument
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $value;

    public function __construct($value)
    {
        $this->value = $value;
    }
}
