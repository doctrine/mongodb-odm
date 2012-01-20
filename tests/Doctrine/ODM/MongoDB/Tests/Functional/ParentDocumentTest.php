<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class ParentDocumentTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testParentDocument()
    {
        $test = new ParentDocumentTestDocument();
        $test->embedOne = new ParentDocumentTestEmbedded();
        $test->embedMany[] = new ParentDocumentTestEmbedded();
        $test->embedMany[] = new ParentDocumentTestEmbedded();
        $test->referenceOne = new ParentDocumentTestDocument();
        $test->referenceMany[] = new ParentDocumentTestDocument();
        $test->referenceMany[] = new ParentDocumentTestDocument();
        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertEquals($test, $test->getEmbedOne()->parent);
        $this->assertEquals($test, $test->getEmbedManyElement(0)->parent);
        $this->assertEquals($test, $test->getEmbedManyElement(1)->parent);

        $this->assertEquals($test, $test->getReferenceOne()->parent);
        $this->assertEquals($test, $test->getReferenceManyElement(0)->parent);
        $this->assertEquals($test, $test->getReferenceManyElement(1)->parent);

        $test = $this->dm->getRepository(get_class($test))->find($test->id);
        $this->assertEquals($test, $test->getEmbedOne()->parent);
        $this->assertEquals($test, $test->getEmbedManyElement(0)->parent);
        $this->assertEquals($test, $test->getEmbedManyElement(1)->parent);
        $this->assertEquals($test, $test->getReferenceOne()->parent);
        $this->assertEquals($test, $test->getReferenceManyElement(0)->parent);
        $this->assertEquals($test, $test->getReferenceManyElement(1)->parent);
    }
}

/** @ODM\Document */
class ParentDocumentTestDocument
{
    /** @ODM\Id */
    public $id;

    /** @ODM\EmbedOne(targetDocument="ParentDocumentTestEmbedded") */
    public $embedOne;

    /** @ODM\EmbedMany(targetDocument="ParentDocumentTestEmbedded") */
    public $embedMany = array();

    /** @ODM\ReferenceOne(targetDocument="ParentDocumentTestDocument", cascade={"all"}) */
    public $referenceOne;

    /** @ODM\ReferenceMany(targetDocument="ParentDocumentTestDocument", cascade={"all"}) */
    public $referenceMany = array();

    /** @ODM\ParentDocument */
    public $parent;

    public function getEmbedOne()
    {
        return $this->embedOne;
    }

    public function getEmbedMany()
    {
        return $this->embedMany;
    }

    public function getEmbedManyElement($key)
    {
        return $this->embedMany[$key];
    }

    public function getReferenceOne()
    {
        return $this->referenceOne;
    }

    public function getReferenceMany()
    {
        return $this->referenceMany;
    }

    public function getReferenceManyElement($key)
    {
        return $this->referenceMany[$key];
    }
}

/** @ODM\EmbeddedDocument */
class ParentDocumentTestEmbedded
{
    /** @ODM\ParentDocument */
    public $parent;
}