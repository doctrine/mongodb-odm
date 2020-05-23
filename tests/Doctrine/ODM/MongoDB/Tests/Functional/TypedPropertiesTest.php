<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents74\TypedDocument;
use Documents74\TypedEmbeddedDocument;
use MongoDB\BSON\ObjectId;
use function phpversion;
use function version_compare;

class TypedPropertiesTest extends BaseTest
{
    public function setUp() : void
    {
        if (version_compare((string) phpversion(), '7.4.0', '<')) {
            $this->markTestSkipped('PHP 7.4 is required to run this test');
        }

        parent::setUp();
    }

    public function testPersistNew() : void
    {
        $doc = new TypedDocument();
        $doc->setName('Maciej');
        $doc->setEmbedOne(new TypedEmbeddedDocument('The answer', 42));
        $doc->getEmbedMany()->add(new TypedEmbeddedDocument('Lucky number', 7));
        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        /** @var TypedDocument $saved */
        $saved = $this->dm->find(TypedDocument::class, $doc->getId());
        $this->assertEquals($doc->getId(), $saved->getId());
        $this->assertSame($doc->getName(), $saved->getName());
        $this->assertEquals($doc->getEmbedOne(), $saved->getEmbedOne());
        $this->assertEquals($doc->getEmbedMany()->getValues(), $saved->getEmbedMany()->getValues());
    }

    public function testMerge() : void
    {
        $doc = new TypedDocument();
        $doc->setId((string) new ObjectId());
        $doc->setName('Maciej');
        $doc->setEmbedOne(new TypedEmbeddedDocument('The answer', 42));
        $doc->getEmbedMany()->add(new TypedEmbeddedDocument('Lucky number', 7));

        $merged = $this->dm->merge($doc);
        $this->assertEquals($doc->getId(), $merged->getId());
        $this->assertSame($doc->getName(), $merged->getName());
        $this->assertEquals($doc->getEmbedOne(), $merged->getEmbedOne());
        $this->assertEquals($doc->getEmbedMany()->getValues(), $merged->getEmbedMany()->getValues());
    }

    public function testProxying() : void
    {
        $doc = new TypedDocument();
        $doc->setName('Maciej');
        $doc->setEmbedOne(new TypedEmbeddedDocument('The answer', 42));
        $doc->getEmbedMany()->add(new TypedEmbeddedDocument('Lucky number', 7));
        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        /** @var TypedDocument $proxy */
        $proxy = $this->dm->getReference(TypedDocument::class, $doc->getId());
        $this->assertEquals($doc->getId(), $proxy->getId());
        $this->assertSame($doc->getName(), $proxy->getName());
        $this->assertEquals($doc->getEmbedOne(), $proxy->getEmbedOne());
        $this->assertEquals($doc->getEmbedMany()->getValues(), $proxy->getEmbedMany()->getValues());
    }
}
