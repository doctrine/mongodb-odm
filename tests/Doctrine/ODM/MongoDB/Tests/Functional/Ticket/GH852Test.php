<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Closure;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ODM\MongoDB\Iterator\Iterator;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use MongoDB\BSON\Binary;
use PHPUnit\Framework\Attributes\DataProvider;

class GH852Test extends BaseTestCase
{
    #[DataProvider('provideIdGenerators')]
    public function testA(Closure $idGenerator): void
    {
        $parent       = new GH852Document();
        $parent->id   = $idGenerator('parent');
        $parent->name = 'parent';

        $childA       = new GH852Document();
        $childA->id   = $idGenerator('childA');
        $childA->name = 'childA';

        $childB       = new GH852Document();
        $childB->id   = $idGenerator('childB');
        $childB->name = 'childB';

        $childC       = new GH852Document();
        $childC->id   = $idGenerator('childC');
        $childC->name = 'childC';

        $parent->refOne    = $childA;
        $parent->refMany[] = $childB;
        $parent->refMany[] = $childC;

        $this->dm->persist($parent);
        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find($parent::class, $idGenerator('parent'));
        self::assertNotNull($parent);
        self::assertEquals($idGenerator('parent'), $parent->id);
        self::assertEquals('parent', $parent->name);

        self::assertTrue(self::isLazyObject($parent->refOne));
        self::assertInstanceOf(GH852Document::class, $parent->refOne);
        self::assertTrue($this->uow->isUninitializedObject($parent->refOne));
        self::assertEquals($idGenerator('childA'), $parent->refOne->id);
        self::assertEquals('childA', $parent->refOne->name);
        self::assertFalse($this->uow->isUninitializedObject($parent->refOne));

        self::assertCount(2, $parent->refMany);

        /* These proxies will be initialized when we first access the collection
         * by DocumentPersister::loadReferenceManyCollectionOwningSide().
         */
        self::assertTrue(self::isLazyObject($parent->refMany[0]));
        self::assertInstanceOf(GH852Document::class, $parent->refMany[0]);
        self::assertFalse($this->uow->isUninitializedObject($parent->refMany[0]));
        self::assertEquals($idGenerator('childB'), $parent->refMany[0]->id);
        self::assertEquals('childB', $parent->refMany[0]->name);

        self::assertTrue(self::isLazyObject($parent->refMany[1]));
        self::assertInstanceOf(GH852Document::class, $parent->refMany[1]);
        self::assertFalse($this->uow->isUninitializedObject($parent->refMany[1]));
        self::assertEquals($idGenerator('childC'), $parent->refMany[1]->id);
        self::assertEquals('childC', $parent->refMany[1]->name);

        // these lines are relevant for $useKeys = false in ReferencePrimer::__construct()
        $this->dm->clear();
        $docs = $this->dm->createQueryBuilder($parent::class)
                ->field('name')->equals('parent')
                ->field('refMany')->prime()
                ->getQuery()->execute();
        self::assertInstanceOf(Iterator::class, $docs);
        self::assertCount(1, $docs);
        self::assertCount(2, $docs->current()->refMany);

        $this->dm->clear();
        $docs = $this->dm->createQueryBuilder($parent::class)
                ->getQuery()->execute();
        self::assertCount(4, $docs);

        // these lines are relevant for $useKeys = false in DocumentRepository::matching()
        $this->dm->clear();
        $docs = $this->dm->getRepository($parent::class)
                ->matching(new Criteria());
        self::assertCount(4, $docs);
    }

    public static function provideIdGenerators(): array
    {
        $binDataType = Binary::TYPE_GENERIC;

        return [
            [
                static fn ($id) => ['foo' => $id],
            ],
            [
                static fn ($id) => new Binary($id, $binDataType),
            ],
        ];
    }
}

#[ODM\Document]
class GH852Document
{
    /** @var Binary|array<string, mixed> */
    #[ODM\Id(strategy: 'NONE', type: 'custom_id')]
    public $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $name;

    /** @var GH852Document */
    #[ODM\ReferenceOne(targetDocument: self::class, cascade: 'all')]
    public $refOne;

    /** @var Collection<int, GH852Document> */
    #[ODM\ReferenceMany(targetDocument: self::class, cascade: 'all')]
    public $refMany;

    public function __construct()
    {
        $this->refMany = new ArrayCollection();
    }
}
