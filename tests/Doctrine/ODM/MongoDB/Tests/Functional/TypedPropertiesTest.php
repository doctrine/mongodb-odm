<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents74\TypedDocument;
use Documents74\TypedEmbeddedDocument;
use MongoDB\BSON\ObjectId;

use function assert;
use function phpversion;
use function version_compare;

class TypedPropertiesTest extends BaseTest
{
    public function setUp(): void
    {
        if (version_compare((string) phpversion(), '7.4.0', '<')) {
            $this->markTestSkipped('PHP 7.4 is required to run this test');
        }

        parent::setUp();
    }

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
        $this->assertEquals($doc->id, $saved->id);
        $this->assertSame($doc->name, $saved->name);
        $this->assertEquals($doc->embedOne, $saved->embedOne);
        $this->assertSame($ref, $saved->referenceOne);
        $this->assertEquals($doc->getEmbedMany()->getValues(), $saved->getEmbedMany()->getValues());
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
        $this->assertEquals($doc->id, $merged->id);
        $this->assertSame($doc->name, $merged->name);
        $this->assertEquals($doc->embedOne, $merged->embedOne);
        $this->assertEquals($doc->referenceOne, $merged->referenceOne);
        $this->assertEquals($doc->getEmbedMany()->getValues(), $merged->getEmbedMany()->getValues());
    }

    public function testMergeWithUninitializedAssociations(): void
    {
        $doc       = new TypedDocument();
        $doc->id   = (string) new ObjectId();
        $doc->name = 'Maciej';
        $doc->getEmbedMany()->add(new TypedEmbeddedDocument('Lucky number', 7));

        $merged = $this->dm->merge($doc);
        assert($merged instanceof TypedDocument);
        $this->assertEquals($doc->id, $merged->id);
        $this->assertSame($doc->name, $merged->name);
        $this->assertEquals($doc->getEmbedMany()->getValues(), $merged->getEmbedMany()->getValues());
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
        $this->assertEquals($doc->id, $proxy->id);
        $this->assertSame($doc->name, $proxy->name);
        $this->assertEquals($doc->embedOne, $proxy->embedOne);
        $this->assertEquals($doc->getEmbedMany()->getValues(), $proxy->getEmbedMany()->getValues());
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
        $this->assertNull($saved->nullableEmbedOne);
        $this->assertNull($saved->initializedNullableEmbedOne);
        $this->assertNull($saved->nullableReferenceOne);
        $this->assertNull($saved->initializedNullableReferenceOne);
    }
}
