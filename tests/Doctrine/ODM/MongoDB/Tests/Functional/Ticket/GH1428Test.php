<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use PHPUnit_Framework_Error_Notice;

class GH1428Test extends BaseTest
{
    public function testShortNameLoss_OnReplacingMiddleEmbeddedDoc_OfNestedEmbedding()
    {
        $owner = new GH1428Document();
        $nestedEmbedded = new GH1428NestedEmbeddedDocument();
        $this->dm->persist($owner);
        $this->dm->flush();

        $owner->embedded = new GH1428EmbeddedDocument();
        $owner->embedded->nestedEmbedded = $nestedEmbedded;
        $this->dm->flush();

        $owner->embedded = new GH1428EmbeddedDocument();
        $owner->embedded->nestedEmbedded = $nestedEmbedded;

        try {
            $this->dm->flush();
            $this->assertTrue(true);
        } catch (PHPUnit_Framework_Error_Notice $ex) {
            $this->fail($ex);
        }
    }
}

/** @ODM\Document */
class GH1428Document
{
    /** @ODM\Id */
    public $id;

    /** @ODM\EmbedOne(targetDocument="GH1428EmbeddedDocument") */
    public $embedded;
}

/** @ODM\EmbeddedDocument */
class GH1428EmbeddedDocument
{
    /** @ODM\EmbedOne(targetDocument="GH1428NestedEmbeddedDocument", name="shortNameThatDoesntExist") */
    public $nestedEmbedded;
}

/** @ODM\EmbeddedDocument */
class GH1428NestedEmbeddedDocument
{
}
