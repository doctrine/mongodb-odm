<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class MODM116Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testIssue()
    {
        $parent = new MODM116Parent();
        $parent->setName('test');
        $parent->setChild(new MODM116Child());
        $this->dm->persist($parent->getChild());
        $this->dm->persist($parent);
        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find(get_class($parent), $parent->getId());

        $parent->getChild()->setName('ok');
        $this->dm->flush();

        $check = $this->dm->getDocumentCollection(get_class($parent))->find()->toArray();
        $check = array_values($check);
        $this->assertEquals(1, count($check));
        $this->assertEquals('test', $check[0]['name']);

        $check = $this->dm->getDocumentCollection(get_class($parent->getChild()))->find()->toArray();
        $check = array_values($check);
        $this->assertEquals(1, count($check));
        $this->assertEquals('ok', $check[0]['name']);
    }
}

/** @ODM\Document @ODM\InheritanceType("COLLECTION_PER_CLASS") **/
class MODM116Parent
{
    /** @ODM\Id */
    private $id;

    /** @ODM\Field(type="string") */
    private $name;

    /** @ODM\ReferenceOne(targetDocument="MODM116Child") **/
    private $child;

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getChild()
    {
        return $this->child;
    }

    public function setChild(MODM116Child $child)
    {
        $this->child = $child;
    }
}

/** @ODM\Document **/
class MODM116Child extends MODM116Parent
{
}
