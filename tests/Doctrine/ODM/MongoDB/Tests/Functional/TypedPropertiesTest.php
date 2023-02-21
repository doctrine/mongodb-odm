<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents74\TypedDocument;
use Documents74\TypedEmbeddedDocument;
use MongoDB\BSON\ObjectId;

use function assert;

class TypedPropertiesTest extends BaseTest
{
    public function testPersistNew(): void
    {
        $ref       = new TypedDocument();
        $ref->name = 'alcaeus';
        $this->dm->persist($ref);

        $doc               = new TypedDocument();
        $doc->name         = 'Maciej';
        $doc->embedOne     = new TypedEmbeddedDocument('The answer', 42);
        $doc->referenceOne = $ref;
        $doc->getEmbedMany()->add(new TypedEmbeddedDocument('Lucky number', 7));
        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $ref   = $this->dm->find(TypedDocument::class, $ref->id);
        $saved = $this->dm->find(TypedDocument::class, $doc->id);
        assert($saved instanceof TypedDocument);
        self::assertEquals($doc->id, $saved->id);
        self::assertSame($doc->name, $saved->name);
        self::assertEquals($doc->embedOne, $saved->embedOne);
        self::assertSame($ref, $saved->referenceOne);
        self::assertEquals($doc->getEmbedMany()->getValues(), $saved->getEmbedMany()->getValues());
    }

    public function testMerge(): void
    {
        $ref       = new TypedDocument();
        $ref->name = 'alcaeus';
        $this->dm->persist($ref);

        $doc               = new TypedDocument();
        $doc->id           = (string) new ObjectId();
        $doc->name         = 'Maciej';
        $doc->embedOne     = new TypedEmbeddedDocument('The answer', 42);
        $doc->referenceOne = $ref;
        $doc->getEmbedMany()->add(new TypedEmbeddedDocument('Lucky number', 7));

        $merged = $this->dm->merge($doc);
        assert($merged instanceof TypedDocument);
        self::assertEquals($doc->id, $merged->id);
        self::assertSame($doc->name, $merged->name);
        self::assertEquals($doc->embedOne, $merged->embedOne);
        self::assertEquals($doc->referenceOne, $merged->referenceOne);
        self::assertEquals($doc->getEmbedMany()->getValues(), $merged->getEmbedMany()->getValues());
    }

    public function testMergeWithUninitializedAssociations(): void
    {
        $doc       = new TypedDocument();
        $doc->id   = (string) new ObjectId();
        $doc->name = 'Maciej';
        $doc->getEmbedMany()->add(new TypedEmbeddedDocument('Lucky number', 7));

        $merged = $this->dm->merge($doc);
        assert($merged instanceof TypedDocument);
        self::assertEquals($doc->id, $merged->id);
        self::assertSame($doc->name, $merged->name);
        self::assertEquals($doc->getEmbedMany()->getValues(), $merged->getEmbedMany()->getValues());
    }

    public function testProxying(): void
    {
        $doc           = new TypedDocument();
        $doc->name     = 'Maciej';
        $doc->embedOne = new TypedEmbeddedDocument('The answer', 42);
        $doc->getEmbedMany()->add(new TypedEmbeddedDocument('Lucky number', 7));
        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $proxy = $this->dm->getReference(TypedDocument::class, $doc->id);
        assert($proxy instanceof TypedDocument);
        self::assertEquals($doc->id, $proxy->id);
        self::assertSame($doc->name, $proxy->name);
        self::assertEquals($doc->embedOne, $proxy->embedOne);
        self::assertEquals($doc->getEmbedMany()->getValues(), $proxy->getEmbedMany()->getValues());
    }

    public function testNullableProperties(): void
    {
        $doc       = new TypedDocument();
        $doc->name = 'webmozart';
        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $saved = $this->dm->find(TypedDocument::class, $doc->id);
        assert($saved instanceof TypedDocument);
        self::assertNull($saved->nullableEmbedOne);
        self::assertNull($saved->initializedNullableEmbedOne);
        self::assertNull($saved->nullableReferenceOne);
        self::assertNull($saved->initializedNullableReferenceOne);
    }
}
