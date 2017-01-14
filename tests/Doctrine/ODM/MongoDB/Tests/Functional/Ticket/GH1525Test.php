<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH1525Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testEmbedCloneTwoFlushesPerDocument()
    {
        $embedded = new GH1525Embedded('embedded');

        $embedMany = new GH1525Embedded('embedMany');

        for ($i = 0; $i < 2; ++$i) {
            $parent = new GH1525Document('test' . $i);
            $this->dm->persist($parent);
            $this->dm->flush();

            $parent->embedded = $embedded;
            $parent->embedMany->add($embedMany);
            $this->dm->flush();
        }

        $this->dm->clear();

        for ($i = 0; $i < 2; ++$i) {
            $test = $this->dm->getRepository(GH1525Document::class)->findOneBy(array('name' => 'test' . $i));

            $this->assertInstanceOf(GH1525Document::class, $test);

            $this->assertInstanceOf(GH1525Embedded::class, $test->embedded);
            $this->assertSame($test->embedded->name, $embedded->name);

            $this->assertCount(1, $test->embedMany);
            $this->assertInstanceOf(GH1525Embedded::class, $test->embedMany[0]);
            $this->assertSame($test->embedMany[0]->name, $embedMany->name);
        }
    }

    public function testEmbedCloneWithIdStrategyNoneOnParentAndEarlyPersist()
    {
        $uuidGen = new \Doctrine\ODM\MongoDB\Id\UuidGenerator();
        $embedded = new GH1525Embedded('embedded');

        $count = 2;
        for ($i = 0; $i < $count; ++$i) {
            $parent = new GH1525DocumentIdStrategyNone($uuidGen->generateV4(), 'test' . $i);
            $this->dm->persist($parent);
            $parent->embedded = $embedded;
            $this->dm->flush();
        }

        $this->dm->clear();

        for ($i = 0; $i < $count; ++$i) {
            $test = $this->dm->getRepository(GH1525DocumentIdStrategyNone::class)->findOneBy(array('name' => 'test' . $i));

            $this->assertInstanceOf(GH1525DocumentIdStrategyNone::class, $test);

            $this->assertInstanceOf(GH1525Embedded::class, $test->embedded);
            $this->assertSame($test->embedded->name, $embedded->name);
        }
    }

    public function testEmbedCloneWithIdStrategyNoneOnParentAndLatePersist()
    {
        $uuidGen = new \Doctrine\ODM\MongoDB\Id\UuidGenerator();
        $embedded = new GH1525Embedded('embedded');

        $count = 2;
        for ($i = 0; $i < $count; ++$i) {
            $parent = new GH1525DocumentIdStrategyNone($uuidGen->generateV4(), 'test' . $i);
            $parent->embedded = $embedded;
            $this->dm->persist($parent);
            $this->dm->flush();
        }

        $this->dm->clear();

        for ($i = 0; $i < $count; ++$i) {
            $test = $this->dm->getRepository(GH1525DocumentIdStrategyNone::class)->findOneBy(array('name' => 'test' . $i));

            $this->assertInstanceOf(GH1525DocumentIdStrategyNone::class, $test);

            $this->assertInstanceOf(GH1525Embedded::class, $test->embedded);
            $this->assertSame($test->embedded->name, $embedded->name);
        }
    }
}

/** @ODM\Document(collection="document_test") */
class GH1525Document
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;

    /** @ODM\EmbedOne(targetDocument="GH1525Embedded") */
    public $embedded;

    /** @ODM\EmbedMany(targetDocument="GH1525Embedded") */
    public $embedMany;

    public function __construct($name)
    {
        $this->name = $name;
        $this->embedMany = new ArrayCollection();
    }
}

/** @ODM\Document(collection="document_test_with_auto_ids") */
class GH1525DocumentIdStrategyNone
{
    /** @ODM\Id(strategy="NONE") */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;

    /** @ODM\EmbedOne(targetDocument="GH1525Embedded") */
    public $embedded;

    public function __construct($id, $name)
    {
        $this->id = $id;
        $this->name = $name;
    }
}

/** @ODM\EmbeddedDocument */
class GH1525Embedded
{
    /** @ODM\Field(type="string") */
    public $name;

    public function __construct($name)
    {
        $this->name = $name;
    }
}
