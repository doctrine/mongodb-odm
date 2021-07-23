<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Closure;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use MongoDB\BSON\Binary;
use ProxyManager\Proxy\GhostObjectInterface;

use function get_class;

class GH852Test extends BaseTest
{
    /**
     * @dataProvider provideIdGenerators
     */
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

        $parent = $this->dm->find(get_class($parent), $idGenerator('parent'));
        $this->assertNotNull($parent);
        $this->assertEquals($idGenerator('parent'), $parent->id);
        $this->assertEquals('parent', $parent->name);

        $this->assertInstanceOf(GhostObjectInterface::class, $parent->refOne);
        $this->assertFalse($parent->refOne->isProxyInitialized());
        $this->assertEquals($idGenerator('childA'), $parent->refOne->id);
        $this->assertEquals('childA', $parent->refOne->name);
        $this->assertTrue($parent->refOne->isProxyInitialized());

        $this->assertCount(2, $parent->refMany);

        /* These proxies will be initialized when we first access the collection
         * by DocumentPersister::loadReferenceManyCollectionOwningSide().
         */
        $this->assertInstanceOf(GhostObjectInterface::class, $parent->refMany[0]);
        $this->assertTrue($parent->refMany[0]->isProxyInitialized());
        $this->assertEquals($idGenerator('childB'), $parent->refMany[0]->id);
        $this->assertEquals('childB', $parent->refMany[0]->name);

        $this->assertInstanceOf(GhostObjectInterface::class, $parent->refMany[1]);
        $this->assertTrue($parent->refMany[1]->isProxyInitialized());
        $this->assertEquals($idGenerator('childC'), $parent->refMany[1]->id);
        $this->assertEquals('childC', $parent->refMany[1]->name);

        // these lines are relevant for $useKeys = false in ReferencePrimer::__construct()
        $this->dm->clear();
        $docs = $this->dm->createQueryBuilder(get_class($parent))
                ->field('name')->equals('parent')
                ->field('refMany')->prime()
                ->getQuery()->execute();
        $this->assertCount(1, $docs);
        $this->assertCount(2, $docs->current()->refMany);

        $this->dm->clear();
        $docs = $this->dm->createQueryBuilder(get_class($parent))
                ->getQuery()->execute();
        $this->assertCount(4, $docs);

        // these lines are relevant for $useKeys = false in DocumentRepository::matching()
        $this->dm->clear();
        $docs = $this->dm->getRepository(get_class($parent))
                ->matching(new Criteria());
        $this->assertCount(4, $docs);
    }

    public function provideIdGenerators(): array
    {
        $binDataType = Binary::TYPE_GENERIC;

        return [
            [
                static function ($id) {
                    return ['foo' => $id];
                },
            ],
            [
                static function ($id) use ($binDataType) {
                    return new Binary($id, $binDataType);
                },
            ],
        ];
    }
}

/** @ODM\Document */
class GH852Document
{
    /** @ODM\Id(strategy="NONE", type="custom_id") */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;

    /**
     * @ODM\ReferenceOne(targetDocument=GH852Document::class, cascade="all")
     *
     * @var GH852Document
     */
    public $refOne;

    /**
     * @ODM\ReferenceMany(targetDocument=GH852Document::class, cascade="all")
     *
     * @var Collection<int, GH852Document>
     */
    public $refMany;

    public function __construct()
    {
        $this->refMany = new ArrayCollection();
    }
}
