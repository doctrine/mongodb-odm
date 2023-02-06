<?php

declare(strict_types=1);

namespace Doctrine\ODM\ODM\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class GH2157Test extends BaseTest
{
    public function testFacetDiscriminatorMapCreation(): void
    {
        $this->dm->persist(new GH2157FirstType());
        $this->dm->persist(new GH2157FirstType());
        $this->dm->persist(new GH2157FirstType());
        $this->dm->persist(new GH2157FirstType());
        $this->dm->flush();

        $result = $this->dm->createAggregationBuilder(GH2157FirstType::class)
            ->project()
                ->includeFields(['id'])
            ->facet()
                ->field('count')
                ->pipeline(
                    $this->dm->createAggregationBuilder(GH2157FirstType::class)
                        ->count('count'),
                )
                ->field('limitedResults')
                ->pipeline(
                    $this->dm->createAggregationBuilder(GH2157FirstType::class)
                        ->limit(2),
                )
            ->execute()->toArray();

        self::assertEquals(4, $result[0]['count'][0]['count']);
        self::assertCount(2, $result[0]['limitedResults']);
    }
}

/**
 * @ODM\Document(collection="documents")
 * @ODM\InheritanceType("SINGLE_COLLECTION")
 * @ODM\DiscriminatorField("type")
 * @ODM\DiscriminatorMap({"firsttype"=GH2157FirstType::class, "secondtype"=GH2157SecondType::class})
 */
abstract class GH2157Abstract
{
    /**
     * @ODM\Id
     *
     * @var string
     */
    protected $id;
}

/** @ODM\Document */
class GH2157FirstType extends GH2157Abstract
{
}

/** @ODM\Document */
class GH2157SecondType extends GH2157Abstract
{
}
