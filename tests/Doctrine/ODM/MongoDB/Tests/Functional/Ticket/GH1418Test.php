<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Query\Query;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

use function assert;

class GH1418Test extends BaseTestCase
{
    public function testManualHydrateAndMerge(): void
    {
        $document = new GH1418Document();
        $this->dm->getHydratorFactory()->hydrate($document, [
            '_id' => 1,
            'name' => 'maciej',
            'embedOne' => ['name' => 'maciej', 'sourceId' => 1],
            'embedMany' => [
                ['name' => 'maciej', 'sourceId' => 2],
            ],
        ], [Query::HINT_READ_ONLY => true]);

        self::assertEquals(1, $document->embedOne->id);
        self::assertEquals(2, $document->embedMany->first()->id);

        $this->dm->merge($document);
        $this->dm->flush();
        $this->dm->clear();

        $document = $this->dm->getRepository(GH1418Document::class)->find(1);
        self::assertEquals(1, $document->id);
        self::assertEquals('maciej', $document->embedOne->name);
        self::assertEquals(1, $document->embedOne->id);
        self::assertEquals(1, $document->embedMany->count());
        self::assertEquals('maciej', $document->embedMany->first()->name);
        self::assertEquals(2, $document->embedMany->first()->id);
    }

    public function testReadDocumentAndManage(): void
    {
        $document     = new GH1418Document();
        $document->id = 1;

        $embedded       = new GH1418Embedded();
        $embedded->id   = 1;
        $embedded->name = 'maciej';

        $document->embedOne    = clone $embedded;
        $document->embedMany[] = clone $embedded;

        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        $document = $this->dm->createQueryBuilder(GH1418Document::class)
            ->readOnly(true)
            ->field('id')
            ->equals(1)
            ->getQuery()
            ->getSingleResult();
        assert($document instanceof GH1418Document);

        self::assertEquals(1, $document->id);
        self::assertEquals('maciej', $document->embedOne->name);
        self::assertEquals(1, $document->embedOne->id);
        self::assertEquals(1, $document->embedMany->count());
        self::assertEquals('maciej', $document->embedMany->first()->name);
        self::assertEquals(1, $document->embedMany->first()->id);

        $document = $this->dm->merge($document);

        $document->embedOne->name     = 'alcaeus';
        $document->embedMany[0]->name = 'alcaeus';

        $this->dm->flush();
        $this->dm->clear();

        $document = $this->dm->getRepository(GH1418Document::class)->find(1);
        self::assertEquals(1, $document->id);
        self::assertEquals('alcaeus', $document->embedOne->name);
        self::assertEquals(1, $document->embedOne->id);
        self::assertEquals(1, $document->embedMany->count());
        self::assertEquals('alcaeus', $document->embedMany->first()->name);
        self::assertEquals(1, $document->embedMany->first()->id);

        $document->embedMany[] = clone $embedded;

        $document = $this->dm->merge($document);
        $this->dm->flush();
        $this->dm->clear();

        $document = $this->dm->getRepository(GH1418Document::class)->find(1);
        self::assertEquals(1, $document->id);
        self::assertEquals('alcaeus', $document->embedOne->name);
        self::assertEquals(2, $document->embedMany->count());
        self::assertEquals('maciej', $document->embedMany->last()->name);
    }
}

#[ODM\Document]
class GH1418Document
{
    /** @var int|null */
    #[ODM\Id(strategy: 'none')]
    public $id;

    /** @var GH1418Embedded|null */
    #[ODM\EmbedOne(targetDocument: GH1418Embedded::class)]
    public $embedOne;

    /** @var Collection<int, GH1418Embedded> */
    #[ODM\EmbedMany(targetDocument: GH1418Embedded::class)]
    public $embedMany;
}

#[ODM\EmbeddedDocument]
class GH1418Embedded
{
    /** @var int|null */
    #[ODM\Id(strategy: 'none', type: 'int')]
    #[ODM\AlsoLoad('sourceId')]
    public $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $name;
}
