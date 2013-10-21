<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class HasLifecycleCallbacksTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testHasLifecycleCallbacksSubExtendsSuper()
    {
        $document = new HasLifecycleCallbacksSubExtendsSuper();
        $this->dm->persist($document);
        $this->dm->flush();

        $this->assertEquals(0, $document->prePersistCount);
    }

    public function testHasLifecycleCallbacksSubExtendsSuperAnnotated()
    {
        $document = new HasLifecycleCallbacksSubExtendsSuperAnnotated();
        $this->dm->persist($document);
        $this->dm->flush();

        $this->assertEquals(1, $document->prePersistCount);
    }

    public function testHasLifecycleCallbacksSubAnnotatedExtendsSuper()
    {
        $document = new HasLifecycleCallbacksSubAnnotatedExtendsSuper();
        $this->dm->persist($document);
        $this->dm->flush();

        $this->assertEquals(0, $document->prePersistCount);
    }

    public function testHasLifecycleCallbacksSubAnnotatedExtendsSuperAnnotated()
    {
        $document = new HasLifecycleCallbacksSubAnnotatedExtendsSuperAnnotated();
        $this->dm->persist($document);
        $this->dm->flush();

        $this->assertEquals(1, $document->prePersistCount);
    }

    public function testHasLifecycleCallbacksSubOverrideExtendsSuper()
    {
        $document = new HasLifecycleCallbacksSubOverrideExtendsSuper();
        $this->dm->persist($document);
        $this->dm->flush();

        $this->assertEquals(0, $document->prePersistCount);
    }

    public function testHasLifecycleCallbacksSubOverrideExtendsSuperAnnotated()
    {
        $document = new HasLifecycleCallbacksSubOverrideExtendsSuperAnnotated();
        $this->dm->persist($document);
        $this->dm->flush();

        $this->assertEquals(1, $document->prePersistCount);
    }

    public function testHasLifecycleCallbacksSubOverrideAnnotatedExtendsSuper()
    {
        $document = new HasLifecycleCallbacksSubOverrideAnnotatedExtendsSuper();
        $this->dm->persist($document);
        $this->dm->flush();

        $this->assertEquals(1, $document->prePersistCount);
    }

    public function testHasLifecycleCallbacksSubOverrideAnnotatedExtendsSuperAnnotated()
    {
        $document = new HasLifecycleCallbacksSubOverrideAnnotatedExtendsSuperAnnotated();
        $this->dm->persist($document);
        $this->dm->flush();

        $this->assertEquals(2, $document->prePersistCount);
    }
}

/** @ODM\MappedSuperclass */
abstract class HasLifecycleCallbacksSuper
{
    /** @ODM\Id */
    public $id;

    public $prePersistCount = 0;

    /** @ODM\PrePersist */
    public function prePersist()
    {
        $this->prePersistCount++;
    }
}

/** @ODM\MappedSuperclass @ODM\HasLifecycleCallbacks */
abstract class HasLifecycleCallbacksSuperAnnotated
{
    /** @ODM\Id */
    public $id;

    public $prePersistCount = 0;

    /** @ODM\PrePersist */
    public function prePersist()
    {
        $this->prePersistCount++;
    }
}

/** @ODM\Document */
class HasLifecycleCallbacksSubExtendsSuper extends HasLifecycleCallbacksSuper
{
}

/** @ODM\Document */
class HasLifecycleCallbacksSubExtendsSuperAnnotated extends HasLifecycleCallbacksSuperAnnotated
{
}

/** @ODM\Document @ODM\HasLifecycleCallbacks */
class HasLifecycleCallbacksSubAnnotatedExtendsSuper extends HasLifecycleCallbacksSuper
{
}

/** @ODM\Document @ODM\HasLifecycleCallbacks */
class HasLifecycleCallbacksSubAnnotatedExtendsSuperAnnotated extends HasLifecycleCallbacksSuperAnnotated
{
}

/** @ODM\Document */
class HasLifecycleCallbacksSubOverrideExtendsSuper extends HasLifecycleCallbacksSuper
{
    /** @ODM\PrePersist */
    public function prePersist()
    {
        parent::prePersist();
    }
}

/** @ODM\Document */
class HasLifecycleCallbacksSubOverrideExtendsSuperAnnotated extends HasLifecycleCallbacksSuperAnnotated
{
    /** @ODM\PrePersist */
    public function prePersist()
    {
        parent::prePersist();
    }
}

/** @ODM\Document @ODM\HasLifecycleCallbacks */
class HasLifecycleCallbacksSubOverrideAnnotatedExtendsSuper extends HasLifecycleCallbacksSuper
{
    /** @ODM\PrePersist */
    public function prePersist()
    {
        parent::prePersist();
    }
}

/** @ODM\Document @ODM\HasLifecycleCallbacks */
class HasLifecycleCallbacksSubOverrideAnnotatedExtendsSuperAnnotated extends HasLifecycleCallbacksSuperAnnotated
{
    /** @ODM\PrePersist */
    public function prePersist()
    {
        parent::prePersist();
    }
}
