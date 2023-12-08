<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class VersionTest extends BaseTestCase
{
    public function testVersioningWhenManipulatingEmbedMany(): void
    {
        $expectedVersion  = 1;
        $doc              = new VersionedDocument();
        $doc->name        = 'test';
        $doc->embedMany[] = new VersionedEmbeddedDocument('embed 1');
        $doc->embedMany[] = new VersionedEmbeddedDocument('embed 2');
        $this->dm->persist($doc);
        $this->dm->flush();
        self::assertEquals($expectedVersion++, $doc->version);

        $doc->embedMany[] = new VersionedEmbeddedDocument('embed 3');
        $this->dm->flush();
        self::assertEquals($expectedVersion++, $doc->version);

        $doc->embedMany[0]->embedMany[] = new VersionedEmbeddedDocument('deeply embed 1');
        $this->dm->flush();
        self::assertEquals($expectedVersion++, $doc->version);

        unset($doc->embedMany[1]);
        $this->dm->flush();
        self::assertEquals($expectedVersion++, $doc->version);

        $doc->embedMany->clear();
        $this->dm->flush();
        self::assertEquals($expectedVersion++, $doc->version);

        $doc->embedMany = null;
        $this->dm->flush();
        self::assertEquals($expectedVersion++, $doc->version);
    }
}

#[ODM\Document]
class VersionedDocument
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var int */
    #[ODM\Field(type: 'int', name: '_version')]
    #[ODM\Version]
    public $version = 1;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $name;

    /** @var Collection<int, VersionedEmbeddedDocument>|array<VersionedEmbeddedDocument>|null */
    #[ODM\EmbedMany(targetDocument: VersionedEmbeddedDocument::class)]
    public $embedMany = [];

    public function __construct()
    {
        $this->embedMany = new ArrayCollection();
    }
}

#[ODM\EmbeddedDocument]
class VersionedEmbeddedDocument
{
    /** @var string */
    #[ODM\Field(type: 'string')]
    public $value;

    /** @var Collection<int, VersionedEmbeddedDocument> */
    #[ODM\EmbedMany(targetDocument: self::class)]
    public $embedMany;

    public function __construct(string $value)
    {
        $this->value     = $value;
        $this->embedMany = new ArrayCollection();
    }
}
