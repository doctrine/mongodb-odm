<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class MODM29Test extends BaseTest
{
    public function testTest()
    {
        $collection = new ArrayCollection([
            new MODM29Embedded('0'),
            new MODM29Embedded('1'),
            new MODM29Embedded('2'),
        ]);

        // TEST CASE:
        $doc = new MODM29Doc($collection);

        $this->dm->persist($doc);
        $this->dm->flush();

        // place element '0' after '1'
        $collection = new ArrayCollection([
            $collection[1],
            $collection[0],
            $collection[2],
        ]);

        $doc->set($collection);

        // changing value together with reordering causes issue when saving:
        $collection[1]->set('tmp');

        $this->dm->persist($doc);
        $this->dm->flush();

        $this->dm->refresh($doc);

        $array = [];
        foreach ($doc->get() as $value) {
            $array[] = $value->get();
        }
        $this->assertEquals(['1', 'tmp', '2'], $array);
    }
}

/** @ODM\Document */
class MODM29Doc
{
    /** @ODM\Id */
    protected $id;

    /** @ODM\EmbedMany(targetDocument=MODM29Embedded::class, strategy="set") */
    protected $collection;

    public function __construct($c)
    {
        $this->set($c);
    }

    public function set($c)
    {
        $this->collection = $c;
    }

    public function get()
    {
        return $this->collection;
    }
}

/** @ODM\EmbeddedDocument */
class MODM29Embedded
{
    /** @ODM\Field(type="string") */
    protected $val;

    public function __construct($val)
    {
        $this->set($val);
    }

    public function get()
    {
        return $this->val;
    }

    public function set($val)
    {
        $this->val = $val;
    }
}
