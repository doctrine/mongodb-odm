<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class GH1428Test extends BaseTest
{
    /** @doesNotPerformAssertions */
    public function testShortNameLossOnReplacingMiddleEmbeddedDocOfNestedEmbedding(): void
    {
        $owner          = new GH1428Document();
        $nestedEmbedded = new GH1428NestedEmbeddedDocument();
        $this->dm->persist($owner);
        $this->dm->flush();

        $owner->embedded                 = new GH1428EmbeddedDocument();
        $owner->embedded->nestedEmbedded = $nestedEmbedded;
        $this->dm->flush();

        $owner->embedded                 = new GH1428EmbeddedDocument();
        $owner->embedded->nestedEmbedded = $nestedEmbedded;

        $this->dm->flush();
    }
}

/** @ODM\Document */
class GH1428Document
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\EmbedOne(targetDocument=GH1428EmbeddedDocument::class)
     *
     * @var GH1428EmbeddedDocument|null
     */
    public $embedded;
}

/** @ODM\EmbeddedDocument */
class GH1428EmbeddedDocument
{
    /**
     * @ODM\EmbedOne(targetDocument=GH1428NestedEmbeddedDocument::class, name="shortNameThatDoesntExist")
     *
     * @var GH1428NestedEmbeddedDocument|null
     */
    public $nestedEmbedded;
}

/** @ODM\EmbeddedDocument */
class GH1428NestedEmbeddedDocument
{
}
