<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class GH665Test extends BaseTestCase
{
    public function testUseAddToSetStrategyOnEmbeddedDocument(): void
    {
        $document = new GH665Document();
        $document->embeddedPushAll->add(new GH665Embedded('foo'));
        $document->embeddedAddToSet->add(new GH665Embedded('bar'));

        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        $check = $this->dm->getDocumentCollection(GH665Document::class)
            ->findOne(['embeddedPushAll.name' => 'foo']);
        self::assertNotNull($check);
        self::assertSame($document->id, (string) $check['_id']);

        $check = $this->dm->getDocumentCollection(GH665Document::class)
            ->findOne(['embeddedAddToSet.name' => 'bar']);
        self::assertNotNull($check);
        self::assertSame($document->id, (string) $check['_id']);

        $persisted = $this->dm->createQueryBuilder(GH665Document::class)
            ->hydrate(false)
            ->field('id')->equals($document->id)
            ->getQuery()
            ->getSingleResult();

        $expected = [
            '_id' => $document->id,
            'embeddedPushAll' => [['name' => 'foo']],
            'embeddedAddToSet' => [['name' => 'bar']],
        ];

        self::assertEquals($expected, $persisted);
    }
}

#[ODM\Document]
class GH665Document
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var Collection<int, GH665Embedded> */
    #[ODM\EmbedMany(targetDocument: GH665Embedded::class, strategy: 'pushAll')]
    public $embeddedPushAll;

    /** @var Collection<int, GH665Embedded> */
    #[ODM\EmbedMany(targetDocument: GH665Embedded::class, strategy: 'addToSet')]
    public $embeddedAddToSet;

    public function __construct()
    {
        $this->embeddedPushAll  = new ArrayCollection();
        $this->embeddedAddToSet = new ArrayCollection();
    }
}

#[ODM\EmbeddedDocument]
class GH665Embedded
{
    /** @var string */
    #[ODM\Field(type: 'string')]
    public $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
