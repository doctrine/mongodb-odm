<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH499Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testSetRefMany()
    {
        $a = new GH499Document(new \MongoDB\BSON\ObjectId());
        $b = new GH499Document(new \MongoDB\BSON\ObjectId());
        $c = new GH499Document(new \MongoDB\BSON\ObjectId());

        $a->addRef($b);
        $a->addRef($c);

        $this->dm->persist($a);
        $this->dm->persist($b);
        $this->dm->persist($c);
        $this->dm->flush();
        $this->dm->clear();

        $collection = $this->dm->getDocumentCollection(__NAMESPACE__ . '\GH499Document');

        $a = $collection->findOne(array('_id' => new \MongoDB\BSON\ObjectId($a->getId())));

        $this->assertEquals(new \MongoDB\BSON\ObjectId($b->getId()), $a['refMany'][$b->getId()]);
        $this->assertEquals(new \MongoDB\BSON\ObjectId($c->getId()), $a['refMany'][$c->getId()]);
    }
}

/** @ODM\Document */
class GH499Document
{
    /** @ODM\Id */
    protected $id;

    /** @ODM\ReferenceMany(targetDocument="GH499Document", storeAs="id", strategy="set") */
    protected $refMany;

    public function __construct($id = null)
    {
        $this->id = (string) $id;
        $this->refMany = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getRefMany()
    {
        return $this->refMany;
    }

    public function addRef(GH499Document $doc)
    {
        $this->refMany->set($doc->getId(), $doc);
    }

    public function removeRef(GH499Document $doc)
    {
        $this->refMany->remove($doc->getId());
    }
}
