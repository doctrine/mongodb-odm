<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH880Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function test880()
    {
        $docs = array();
        $docs[] = new GH880Document('hello', 1);
        $docs[] = new GH880Document('world', 1);
        foreach ($docs as $doc) {
            $this->dm->persist($doc);
        }
        $this->dm->flush();
        $query = $this->dm->createQueryBuilder(__NAMESPACE__ . '\GH880Document');
        $cursor = $query->find()->getQuery()->execute();
        foreach ($cursor as $c) {
            $this->assertEquals(1, $c->category);
        }
        $query = $this->dm->createQueryBuilder(__NAMESPACE__ . '\GH880Document');
        $query->update()
            ->multiple(true)
            ->field('category')->equals(1)
            ->field('category')->set(3)
            ->getQuery()
            ->execute();
        $query = $this->dm->createQueryBuilder(__NAMESPACE__ . '\GH880Document');
        // here ->refresh() was needed for the test to pass
        $cursor = $query->find()->refresh()->getQuery()->execute();
        foreach ($cursor as $c) {
            $this->assertEquals(3, $c->category);
        }
    }
}

/** @ODM\Document */
class GH880Document
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $status;

    /** @ODM\Field(type="int") */
    public $category;

    public function __construct($status = "", $category = 0)
    {
        $this->status = $status;
        $this->category = $category;
    }
}
