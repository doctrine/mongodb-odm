<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Registry;

use Doctrine\ODM\MongoDB\Registry\DocumentRegistry;
use Doctrine\ODM\MongoDB\Registry\PersistenceState;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\Address;
use Documents\CmsUser;
use MongoDB\BSON\ObjectId;

use function array_map;
use function spl_object_id;

class DocumentRegistryTest extends BaseTestCase
{
    private DocumentRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = new DocumentRegistry();
    }

    public function testGetObjectStateReturnsNullForUnknownDocument(): void
    {
        self::assertNull($this->registry->getObjectState(new CmsUser()));
    }

    public function testGetOrCreateObjectStateCreatesAndReusesState(): void
    {
        $document = new CmsUser();

        $state = $this->registry->getOrCreateObjectState($document, PersistenceState::Managed);

        self::assertSame(PersistenceState::Managed, $state->state);
        self::assertSame($state, $this->registry->getObjectState($document));
        self::assertSame($state, $this->registry->getOrCreateObjectState($document));
    }

    public function testRemoveObjectStateForgetsTrackedState(): void
    {
        $document = new CmsUser();
        $this->registry->getOrCreateObjectState($document);

        $this->registry->removeObjectState($document);

        self::assertNull($this->registry->getObjectState($document));
    }

    public function testAddToIdentityMapAndGetById(): void
    {
        $class                                                         = $this->dm->getClassMetadata(CmsUser::class);
        $document                                                      = new CmsUser();
        $id                                                            = new ObjectId();
        $this->registry->getOrCreateObjectState($document)->identifier = (string) $id;

        self::assertTrue($this->registry->addToIdentityMap($class, $document));
        self::assertSame($document, $this->registry->getById((string) $id, $class));
        self::assertSame($document, $this->registry->tryGetById((string) $id, $class));
    }

    public function testAddToIdentityMapReturnsFalseForDuplicateIdentifier(): void
    {
        $class = $this->dm->getClassMetadata(CmsUser::class);
        $id    = (string) new ObjectId();

        $first                                                      = new CmsUser();
        $this->registry->getOrCreateObjectState($first)->identifier = $id;
        self::assertTrue($this->registry->addToIdentityMap($class, $first));

        $second                                                      = new CmsUser();
        $this->registry->getOrCreateObjectState($second)->identifier = $id;
        self::assertFalse($this->registry->addToIdentityMap($class, $second));
    }

    public function testTryGetByIdReturnsFalseWhenNotFound(): void
    {
        $class = $this->dm->getClassMetadata(CmsUser::class);

        self::assertFalse($this->registry->tryGetById((string) new ObjectId(), $class));
    }

    public function testRemoveFromIdentityMap(): void
    {
        $class                                                         = $this->dm->getClassMetadata(CmsUser::class);
        $document                                                      = new CmsUser();
        $id                                                            = (string) new ObjectId();
        $this->registry->getOrCreateObjectState($document)->identifier = $id;
        $this->registry->addToIdentityMap($class, $document);

        self::assertTrue($this->registry->removeFromIdentityMap($class, $document));
        self::assertFalse($this->registry->tryGetById($id, $class));
        self::assertFalse($this->registry->removeFromIdentityMap($class, $document));
    }

    public function testIsInIdentityMapIsFalseWithoutAnIdentifier(): void
    {
        $class    = $this->dm->getClassMetadata(CmsUser::class);
        $document = new CmsUser();

        self::assertFalse($this->registry->isInIdentityMap($class, $document));
    }

    public function testIsInIdentityMapUsesSplObjectIdForDocumentsWithoutAnIdentifierField(): void
    {
        $class    = $this->dm->getClassMetadata(Address::class);
        $document = new Address();

        self::assertFalse($this->registry->isInIdentityMap($class, $document));

        // Documents without an identifier field are keyed by spl_object_id,
        // mirroring what UnitOfWork::persistNew() does for embedded documents.
        $this->registry->getOrCreateObjectState($document)->identifier = spl_object_id($document);
        $this->registry->addToIdentityMap($class, $document);

        self::assertTrue($this->registry->isInIdentityMap($class, $document));
    }

    public function testContainsId(): void
    {
        $class                                                         = $this->dm->getClassMetadata(CmsUser::class);
        $document                                                      = new CmsUser();
        $id                                                            = (string) new ObjectId();
        $this->registry->getOrCreateObjectState($document)->identifier = $id;
        $this->registry->addToIdentityMap($class, $document);

        self::assertTrue($this->registry->containsId(new ObjectId($id), $class->name));
        self::assertFalse($this->registry->containsId(new ObjectId(), $class->name));
    }

    public function testGetIdentityMapAndSize(): void
    {
        $class = $this->dm->getClassMetadata(CmsUser::class);

        self::assertSame(0, $this->registry->size());
        self::assertSame([], $this->registry->getIdentityMap());

        $document                                                      = new CmsUser();
        $this->registry->getOrCreateObjectState($document)->identifier = (string) new ObjectId();
        $this->registry->addToIdentityMap($class, $document);

        self::assertSame(1, $this->registry->size());
        self::assertSame([CmsUser::class => [$document]], array_map('array_values', $this->registry->getIdentityMap()));
    }

    public function testClearResetsBothObjectStatesAndIdentityMap(): void
    {
        $class                                                         = $this->dm->getClassMetadata(CmsUser::class);
        $document                                                      = new CmsUser();
        $this->registry->getOrCreateObjectState($document)->identifier = (string) new ObjectId();
        $this->registry->addToIdentityMap($class, $document);

        $this->registry->clear();

        self::assertNull($this->registry->getObjectState($document));
        self::assertSame(0, $this->registry->size());
        self::assertSame([], $this->registry->getIdentityMap());
    }
}
