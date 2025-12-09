<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class GH520Test extends BaseTestCase
{
    public function testPrimeWithGetSingleResult(): void
    {
        $document      = new GH520Document();
        $document->ref = new GH520Document();

        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        $query = $this->dm->createQueryBuilder(GH520Document::class)
            ->field('id')->equals($document->id)
            ->field('ref')->prime(true)
            ->getQuery();

        $document = $query->getSingleResult();

        self::assertInstanceOf(GH520Document::class, $document);
        self::assertTrue(self::isLazyObject($document->ref));
        self::assertFalse($this->uow->isUninitializedObject($document->ref));
    }

    public function testPrimeWithGetSingleResultWillNotPrimeEntireResultSet(): void
    {
        $document1 = new GH520Document();
        $document2 = new GH520Document();
        $document3 = new GH520Document();
        $document4 = new GH520Document();

        $document1->ref = $document2;
        $document3->ref = $document4;

        $this->dm->persist($document1);
        $this->dm->persist($document3);
        $this->dm->flush();
        $this->dm->clear();

        $primedIds = null;
        $primer    = static function (DocumentManager $dm, ClassMetadata $class, array $ids, array $hints) use (&$primedIds) {
            $primedIds = $ids;
        };

        $query = $this->dm->createQueryBuilder(GH520Document::class)
            ->field('ref')->exists(true)->prime($primer)
            ->getQuery();

        $query->getSingleResult();

        self::assertContains($document2->id, $primedIds);
        self::assertNotContains($document4->id, $primedIds, 'getSingleResult() does not prime the entire dataset');
    }
}

#[ODM\Document]
class GH520Document
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var GH520Document|null */
    #[ODM\ReferenceOne(targetDocument: self::class, cascade: ['persist'])]
    public $ref;
}
