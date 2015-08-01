<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH529Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testAutoIdWithConsistentValues()
    {
        $mongoId = new \MongoId();
        $doc = new GH529AutoIdDocument();
        $doc->id = $mongoId;

        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $doc = $this->dm->find(get_class($doc), $doc->id);

        $this->assertNotNull($doc);
        $this->assertEquals($mongoId, $doc->id);
    }

    public function testCustomIdType()
    {
        /* All values are consistent for CustomIdType, since the PHP and DB
         * conversions return the value as-is.
         */
        $doc = new GH529CustomIdDocument();
        $doc->id = 'foo';

        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $doc = $this->dm->find(get_class($doc), $doc->id);

        $this->assertNotNull($doc);
        $this->assertSame('foo', $doc->id);
    }

    public function testIntIdWithConsistentValues()
    {
        $doc = new GH529IntIdDocument();
        $doc->id = 1;

        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $doc = $this->dm->find(get_class($doc), $doc->id);

        $this->assertNotNull($doc);
        $this->assertSame(1, $doc->id);
    }

    public function testIntIdWithInconsistentValues()
    {
        $doc = new GH529IntIdDocument();
        $doc->id = 3.14;

        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $doc = $this->dm->find(get_class($doc), $doc->id);

        $this->assertNotNull($doc);
        $this->assertNotEquals(3.14, $doc->id);
    }
}

/** @ODM\Document */
class GH529AutoIdDocument
{
    /** @ODM\Id */
    public $id;
}

/** @ODM\Document */
class GH529CustomIdDocument
{
    /** @ODM\Id(strategy="none", type="custom_id") */
    public $id;
}

/** @ODM\Document */
class GH529IntIdDocument
{
    /** @ODM\Id(strategy="none", type="int") */
    public $id;
}
