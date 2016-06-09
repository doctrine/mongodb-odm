<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class SplObjectHashCollisionsTest extends BaseTest
{
    /**
     * @dataProvider provideParentAssociationsIsCleared
     */
    public function testParentAssociationsIsCleared($f)
    {
        $d = new SplColDoc();
        $d->one = new SplColEmbed('d.one.v1');
        $d->many[] = new SplColEmbed('d.many.0.v1');
        $d->many[] = new SplColEmbed('d.many.1.v1');

        $this->dm->persist($d);
        $this->expectCount('parentAssociations', 3);
        $this->expectCount('embeddedDocumentsRegistry', 3);
        $f($this->dm, $d);
        $this->expectCount('parentAssociations', 0);
        $this->expectCount('embeddedDocumentsRegistry', 0);
    }

    /**
     * @dataProvider provideParentAssociationsIsCleared
     */
    public function testParentAssociationsLeftover($f, $leftover)
    {
        $d = new SplColDoc();
        $d->one = new SplColEmbed('d.one.v1');
        $d->many[] = new SplColEmbed('d.many.0.v1');
        $d->many[] = new SplColEmbed('d.many.1.v1');
        $this->dm->persist($d);
        $d->one = new SplColEmbed('d.one.v2');
        $this->dm->flush();

        $this->expectCount('parentAssociations', 4);
        $this->expectCount('embeddedDocumentsRegistry', 4);
        $f($this->dm, $d);
        $this->expectCount('parentAssociations', $leftover);
        $this->expectCount('embeddedDocumentsRegistry', $leftover);
    }

    public function provideParentAssociationsIsCleared()
    {
        return array(
            array( function (DocumentManager $dm) { $dm->clear(); }, 0 ),
            array( function (DocumentManager $dm, $doc) { $dm->clear(get_class($doc)); }, 1 ),
            array( function (DocumentManager $dm, $doc) { $dm->detach($doc); }, 1 ),
        );
    }

    private function expectCount($prop, $expected)
    {
        $ro = new \ReflectionObject($this->uow);
        $rp = $ro->getProperty($prop);
        $rp->setAccessible(true);
        $this->assertCount($expected, $rp->getValue($this->uow));
    }
}

/** @ODM\Document */
class SplColDoc
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;

    /** @ODM\EmbedOne */
    public $one;

    /** @ODM\EmbedMany */
    public $many = array();
}

/** @ODM\EmbeddedDocument */
class SplColEmbed
{
    /** @ODM\Field(type="string") */
    public $name;

    public function __construct($name)
    {
        $this->name = $name;
    }
}
