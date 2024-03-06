<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class GH928Test extends BaseTestCase
{
    public function testNullIdCriteriaShouldNotRemoveEverything(): void
    {
        $docA = new GH928Document();
        $docB = new GH928Document();

        $this->dm->persist($docA);
        $this->dm->persist($docB);
        $this->dm->flush();
        $this->dm->clear();

        $collection = $this->dm->getDocumentCollection(GH928Document::class);

        self::assertEquals(2, $collection->count());

        $qb = $this->dm->createQueryBuilder(GH928Document::class)
            ->remove()
            ->field('id')->equals(null)
            ->getQuery()
            ->execute();

        self::assertEquals(2, $collection->count());
    }
}

#[ODM\Document]
class GH928Document
{
    /** @var string|null */
    #[ODM\Id]
    public $id;
}
