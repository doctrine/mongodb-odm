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

        // Neither class is annotated. No callback is invoked.
        $this->assertCount(0, $document->invoked);
    }

    public function testHasLifecycleCallbacksSubExtendsSuperAnnotated()
    {
        $document = new HasLifecycleCallbacksSubExtendsSuperAnnotated();
        $this->dm->persist($document);
        $this->dm->flush();

        /* The sub-class is not annotated, so the callback in the annotated
         * super-class is invokved.
         */
        $this->assertCount(1, $document->invoked);
        $this->assertEquals('super', $document->invoked[0]);
    }

    public function testHasLifecycleCallbacksSubAnnotatedExtendsSuper()
    {
        $document = new HasLifecycleCallbacksSubAnnotatedExtendsSuper();
        $this->dm->persist($document);
        $this->dm->flush();

        /* The sub-class is annotated, but the method is declared in the super-
         * class, which is not annotated. No callback is invoked.
         */
        $this->assertCount(0, $document->invoked);
    }

    public function testHasLifecycleCallbacksSubAnnotatedExtendsSuperAnnotated()
    {
        $document = new HasLifecycleCallbacksSubAnnotatedExtendsSuperAnnotated();
        $this->dm->persist($document);
        $this->dm->flush();

        /* The sub-class is annotated, but it doesn't override the method, so
         * the callback in the annotated super-class is invoked.
         */
        $this->assertCount(1, $document->invoked);
        $this->assertEquals('super', $document->invoked[0]);
    }

    public function testHasLifecycleCallbacksSubOverrideExtendsSuper()
    {
        $document = new HasLifecycleCallbacksSubOverrideExtendsSuper();
        $this->dm->persist($document);
        $this->dm->flush();

        // Neither class is annotated. No callback is invoked.
        $this->assertCount(0, $document->invoked);
    }

    public function testHasLifecycleCallbacksSubOverrideExtendsSuperAnnotated()
    {
        $document = new HasLifecycleCallbacksSubOverrideExtendsSuperAnnotated();
        $this->dm->persist($document);
        $this->dm->flush();

        /* The sub-class is invoked because it overrides the method in the
         * annotated super-class.
         */
        $this->assertCount(1, $document->invoked);
        $this->assertEquals('sub', $document->invoked[0]);
    }

    public function testHasLifecycleCallbacksSubOverrideAnnotatedExtendsSuper()
    {
        $document = new HasLifecycleCallbacksSubOverrideAnnotatedExtendsSuper();
        $this->dm->persist($document);
        $this->dm->flush();

        /* The sub-class is invoked because it overrides the method and is
         * annotated.
         */
        $this->assertCount(1, $document->invoked);
        $this->assertEquals('sub', $document->invoked[0]);
    }

    public function testHasLifecycleCallbacksSubOverrideAnnotatedExtendsSuperAnnotated()
    {
        $document = new HasLifecycleCallbacksSubOverrideAnnotatedExtendsSuperAnnotated();
        $this->dm->persist($document);
        $this->dm->flush();

        /* Since both classes are annotated and declare the method, the callback
         * is registered twice but the sub-class should be invoked only once.
         */
        $this->assertCount(1, $document->invoked);
        $this->assertEquals('sub', $document->invoked[0]);
    }
}

/** @ODM\MappedSuperclass */
abstract class HasLifecycleCallbacksSuper
{
    /** @ODM\Id */
    public $id;

    public $invoked = array();

    /** @ODM\PrePersist */
    public function prePersist()
    {
        $this->invoked[] = 'super';
    }
}

/** @ODM\MappedSuperclass @ODM\HasLifecycleCallbacks */
abstract class HasLifecycleCallbacksSuperAnnotated
{
    /** @ODM\Id */
    public $id;

    public $invoked = array();

    /** @ODM\PrePersist */
    public function prePersist()
    {
        $this->invoked[] = 'super';
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
        $this->invoked[] = 'sub';
    }
}

/** @ODM\Document */
class HasLifecycleCallbacksSubOverrideExtendsSuperAnnotated extends HasLifecycleCallbacksSuperAnnotated
{
    /** @ODM\PrePersist */
    public function prePersist()
    {
        $this->invoked[] = 'sub';
    }
}

/** @ODM\Document @ODM\HasLifecycleCallbacks */
class HasLifecycleCallbacksSubOverrideAnnotatedExtendsSuper extends HasLifecycleCallbacksSuper
{
    /** @ODM\PrePersist */
    public function prePersist()
    {
        $this->invoked[] = 'sub';
    }
}

/** @ODM\Document @ODM\HasLifecycleCallbacks */
class HasLifecycleCallbacksSubOverrideAnnotatedExtendsSuperAnnotated extends HasLifecycleCallbacksSuperAnnotated
{
    /** @ODM\PrePersist */
    public function prePersist()
    {
        $this->invoked[] = 'sub';
    }
}
