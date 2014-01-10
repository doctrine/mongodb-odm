<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @author Tyler Stroud <tyler@tylerstroud.com>
 */
class GH67Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
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

        $foundDocument = $this->repository->findAll()->getNext();

        $this->dm->remove($foundDocument);
        $this->dm->flush($foundDocument);
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
