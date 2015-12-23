<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH567Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    protected $repository;

    public function setUp()
    {
        parent::setUp();
        $class = __NAMESPACE__ . '\GH567Document';
        $this->repository = $this->dm->getRepository($class);
    }

    public function testRemoveSingleDocument()
    {
        $document = new GH567Document();
        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        $foundDocument = $this->repository->find($document->id);

        $this->dm->remove($foundDocument);
        $this->dm->flush($foundDocument);
        $this->dm->clear();

        $foundDocument = $this->repository->find($document->id);
        $this->assertNull($foundDocument);
    }
}

/**
 * @ODM\Document
 */
class GH567Document
{
    /**
     * @ODM\Id
     */
    public $id;
}
