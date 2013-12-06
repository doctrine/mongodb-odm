<?php

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

class GH520Test extends BaseTest
{
    public function testPrimeWithGetSingleResult()
    {
        $document = new GH520Document();
        $document->ref = new GH520Document();

        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        $query = $this->dm->createQueryBuilder(__NAMESPACE__ . '\GH520Document')
            ->field('id')->equals($document->id)
            ->field('ref')->prime(true)
            ->getQuery();

        $document = $query->getSingleResult();

        $this->assertInstanceOf('Doctrine\ODM\MongoDB\Proxy\Proxy', $document->ref);
        $this->assertTrue($document->ref->__isInitialized());
    }

    public function testPrimeWithGetSingleResultWillPrimeEntireResultSet()
    {
        /* Since Query::getSingleResult() exists for Doctrine MongoDB's
         * IteratorAggregate interface, it executes the query and obtains an
         * iterator before calling Iterator::getSingleResult(). If priming is
         * configured, references among all documents in the result set will be
         * primed -- not simply those in the first document.
         *
         * This behavior is not ideal, but a workaround would further complicate
         * ODM's Query class. A userland solution would be to simply enforce a
         * limit on the query with Builder::limit(). That limit can then be
         * applied to both queries and commands (where applicable).
         */
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
        $primer = function(DocumentManager $dm, ClassMetadata $class, array $ids, array $hints) use (&$primedIds) {
            $primedIds = $ids;
        };

        $query = $this->dm->createQueryBuilder(__NAMESPACE__ . '\GH520Document')
            ->field('ref')->exists(true)->prime($primer)
            ->getQuery();

        $result = $query->getSingleResult();

        $this->assertContains($document2->id, $primedIds);
        $this->assertContains($document4->id, $primedIds);
    }
}

/** @ODM\Document */
class GH520Document
{
    /** @ODM\Id */
    public $id;

    /** @ODM\ReferenceOne(targetDocument="GH520Document", cascade={"persist"}) */
    public $ref;
}
