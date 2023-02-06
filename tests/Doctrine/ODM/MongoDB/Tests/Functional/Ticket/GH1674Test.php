<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use MongoDB\BSON\ObjectId;

class GH1674Test extends BaseTest
{
    public function testElemMatchUsesCorrectMapping(): void
    {
        $builder = $this->dm->createQueryBuilder(GH1674Document::class);
        $builder
            ->field('embedded')
            ->elemMatch(
                $builder->expr()
                    ->field('id')
                    ->equals(1),
            );

        self::assertSame(
            [
                'embedded' => [
                    '$elemMatch' => ['id' => '1'],
                ],
            ],
            $builder->getQueryArray(),
        );
    }
}

/** @ODM\Document */
class GH1674Document
{
    /**
     * @ODM\Id
     *
     * @var ObjectId|null
     */
    protected $id;

    /**
     * @ODM\EmbedMany(targetDocument=GH1674Embedded::class)
     *
     * @var Collection<int, GH1674Embedded>
     */
    protected $embedded;

    public function __construct()
    {
        $this->id       = new ObjectId();
        $this->embedded = new ArrayCollection();
    }
}

/** @ODM\EmbeddedDocument */
class GH1674Embedded
{
    /**
     * @ODM\Field
     *
     * @var string|null
     */
    public $id;
}
