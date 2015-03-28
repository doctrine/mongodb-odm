<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH928Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testNullIdCriteriaShouldNotRemoveEverything()
    {
        $docA = new GH928Document();
        $docB = new GH928Document();

        $this->dm->persist($docA);
        $this->dm->persist($docB);
        $this->dm->flush();
        $this->dm->clear();

        $collection = $this->dm->getDocumentCollection(__NAMESPACE__.'\GH928Document');

        $this->assertEquals(2, $collection->count());

        $qb = $this->dm->createQueryBuilder(__NAMESPACE__.'\GH928Document')
            ->remove()
            ->field('id')->equals(null)
            ->getQuery()
            ->execute();

        $this->assertEquals(2, $collection->count());
    }
}

/** @ODM\Document */
class GH928Document
{
    /** @ODM\Id */
    public $id;
}
