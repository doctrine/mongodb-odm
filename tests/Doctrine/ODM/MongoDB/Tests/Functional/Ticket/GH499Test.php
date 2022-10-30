<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use MongoDB\BSON\ObjectId;

class GH499Test extends BaseTest
{
    public function testSetRefMany(): void
    {
        $a = new GH499Document(new ObjectId());
        $b = new GH499Document(new ObjectId());
        $c = new GH499Document(new ObjectId());

        $a->addRef($b);
        $a->addRef($c);

        $this->dm->persist($a);
        $this->dm->persist($b);
        $this->dm->persist($c);
        $this->dm->flush();
        $this->dm->clear();

        $collection = $this->dm->getDocumentCollection(GH499Document::class);

        $a = $collection->findOne(['_id' => new ObjectId($a->getId())]);

        self::assertEquals(new ObjectId($b->getId()), $a['refMany'][$b->getId()]);
        self::assertEquals(new ObjectId($c->getId()), $a['refMany'][$c->getId()]);
    }
}

/** @ODM\Document */
class GH499Document
{
    /**
     * @ODM\Id
     *
     * @var string
     */
    protected $id;

    /**
     * @ODM\ReferenceMany(targetDocument=GH499Document::class, storeAs="id", strategy="set")
     *
     * @var Collection<array-key, GH499Document>
     */
    protected $refMany;

    public function __construct(?ObjectId $id = null)
    {
        $this->id      = (string) $id;
        $this->refMany = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    /** @return Collection<int, GH499Document> */
    public function getRefMany(): Collection
    {
        return $this->refMany;
    }

    public function addRef(GH499Document $doc): void
    {
        $this->refMany->set($doc->getId(), $doc);
    }

    public function removeRef(GH499Document $doc): void
    {
        $this->refMany->remove($doc->getId());
    }
}
