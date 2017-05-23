<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @author Josh Worden <solocommand@gmail.com>
 * @since 6/16/15
 */
class GH949Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    protected $group;

    public function testDisconnectedReferencesAfterClear()
    {
        // Create our test docs
        $ref = new GH949Reference();
        $doc = new GH949Document($ref);

        // Persist/flush/clear
        $this->dm->persist($ref);
        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        // After clearing, create a new document using the existing reference model
        $doc2 = new GH949Document($ref);

        $this->dm->persist($doc2);

        // Because the $doc2->ref is referencing a document that is
        // cleared from the identity map, this will throw an exception.
        $this->dm->flush();
    }
}

/** @ODM\Document */
class GH949Document
{
    /** @ODM\Id */
    protected $id;

    /** @ODM\ReferenceOne(targetDocument="GH949Reference") */
    public $ref;

    public function __construct(GH949Reference $ref)
    {
        $this->ref = $ref;
    }
}

/** @ODM\Document */
class GH949Reference
{
    /** @ODM\Id */
    protected $id;
}
