<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;

class GH1428Test extends BaseTestCase
{
    #[DoesNotPerformAssertions]
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

#[ODM\Document]
class GH1428Document
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var GH1428EmbeddedDocument|null */
    #[ODM\EmbedOne(targetDocument: GH1428EmbeddedDocument::class)]
    public $embedded;
}

#[ODM\EmbeddedDocument]
class GH1428EmbeddedDocument
{
    /** @var GH1428NestedEmbeddedDocument|null */
    #[ODM\EmbedOne(targetDocument: GH1428NestedEmbeddedDocument::class, name: 'shortNameThatDoesntExist')]
    public $nestedEmbedded;
}

#[ODM\EmbeddedDocument]
class GH1428NestedEmbeddedDocument
{
}
