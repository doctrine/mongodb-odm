<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use MongoDB\BSON\ObjectId;

class ReadOnlyDocumentTest extends BaseTest
{
    public function testCanBeInserted(): void
    {
        $rod = new ReadOnlyDocument('yay');
        $this->dm->persist($rod);
        $this->dm->flush();
        $this->dm->clear();

        $rod = $this->dm->find(ReadOnlyDocument::class, $rod->id);
        self::assertNotNull($rod);
        self::assertSame('yay', $rod->value);
    }

    public function testCanBeUpserted(): void
    {
        $rod     = new ReadOnlyDocument('yay');
        $rod->id = new ObjectId();
        $this->dm->persist($rod);
        $this->dm->flush();
        $this->dm->clear();

        $rod = $this->dm->find(ReadOnlyDocument::class, $rod->id);
        self::assertNotNull($rod);
        self::assertSame('yay', $rod->value);
    }

    public function testCanBeRemoved(): void
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
        self::assertNull($rod);
    }

    public function testChangingValueDoesNotProduceChangeSet(): void
    {
        $rod = new ReadOnlyDocument('yay');
        $this->dm->persist($rod);
        $this->dm->flush();
        $rod->value = 'o.O';
        $this->uow->recomputeSingleDocumentChangeSet($this->dm->getClassMetadata(ReadOnlyDocument::class), $rod);
        self::assertEmpty($this->uow->getDocumentChangeSet($rod));
    }

    public function testCantBeUpdated(): void
    {
        $rod = new ReadOnlyDocument('yay');
        $this->dm->persist($rod);
        $this->dm->flush();
        $this->dm->clear();

        $rod        = $this->dm->find(ReadOnlyDocument::class, $rod->id);
        $rod->value = 'o.O';
        $this->dm->flush();
        $this->dm->clear();

        $rod = $this->dm->find(ReadOnlyDocument::class, $rod->id);
        self::assertSame('yay', $rod->value);
    }
}

/** @ODM\Document(readOnly=true) */
class ReadOnlyDocument
{
    /**
     * @ODM\Id
     *
     * @var ObjectId|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    public $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }
}
