<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class HasLifecycleCallbacksTest extends BaseTest
{
    public function testHasLifecycleCallbacksSubExtendsSuper(): void
    {
        $document = new HasLifecycleCallbacksSubExtendsSuper();
        $this->dm->persist($document);
        $this->dm->flush();

        // Neither class is annotated. No callback is invoked.
        self::assertEmpty($document->invoked);
    }

    public function testHasLifecycleCallbacksSubExtendsSuperAnnotated(): void
    {
        $document = new HasLifecycleCallbacksSubExtendsSuperAnnotated();
        $this->dm->persist($document);
        $this->dm->flush();

        /* The sub-class is not annotated, so the callback in the annotated
         * super-class is invokved.
         */
        self::assertCount(1, $document->invoked);
        self::assertEquals('super', $document->invoked[0]);
    }

    public function testHasLifecycleCallbacksSubAnnotatedExtendsSuper(): void
    {
        $document = new HasLifecycleCallbacksSubAnnotatedExtendsSuper();
        $this->dm->persist($document);
        $this->dm->flush();

        /* The sub-class is annotated, but the method is declared in the super-
         * class, which is not annotated. No callback is invoked.
         */
        self::assertEmpty($document->invoked);
    }

    public function testHasLifecycleCallbacksSubAnnotatedExtendsSuperAnnotated(): void
    {
        $document = new HasLifecycleCallbacksSubAnnotatedExtendsSuperAnnotated();
        $this->dm->persist($document);
        $this->dm->flush();

        /* The sub-class is annotated, but it doesn't override the method, so
         * the callback in the annotated super-class is invoked.
         */
        self::assertCount(1, $document->invoked);
        self::assertEquals('super', $document->invoked[0]);
    }

    public function testHasLifecycleCallbacksSubOverrideExtendsSuper(): void
    {
        $document = new HasLifecycleCallbacksSubOverrideExtendsSuper();
        $this->dm->persist($document);
        $this->dm->flush();

        // Neither class is annotated. No callback is invoked.
        self::assertEmpty($document->invoked);
    }

    public function testHasLifecycleCallbacksSubOverrideExtendsSuperAnnotated(): void
    {
        $document = new HasLifecycleCallbacksSubOverrideExtendsSuperAnnotated();
        $this->dm->persist($document);
        $this->dm->flush();

        /* The sub-class is invoked because it overrides the method in the
         * annotated super-class.
         */
        self::assertCount(1, $document->invoked);
        self::assertEquals('sub', $document->invoked[0]);
    }

    public function testHasLifecycleCallbacksSubOverrideAnnotatedExtendsSuper(): void
    {
        $document = new HasLifecycleCallbacksSubOverrideAnnotatedExtendsSuper();
        $this->dm->persist($document);
        $this->dm->flush();

        /* The sub-class is invoked because it overrides the method and is
         * annotated.
         */
        self::assertCount(1, $document->invoked);
        self::assertEquals('sub', $document->invoked[0]);
    }

    public function testHasLifecycleCallbacksSubOverrideAnnotatedExtendsSuperAnnotated(): void
    {
        $document = new HasLifecycleCallbacksSubOverrideAnnotatedExtendsSuperAnnotated();
        $this->dm->persist($document);
        $this->dm->flush();

        /* Since both classes are annotated and declare the method, the callback
         * is registered twice but the sub-class should be invoked only once.
         */
        self::assertCount(1, $document->invoked);
        self::assertEquals('sub', $document->invoked[0]);
    }
}

/** @ODM\MappedSuperclass */
abstract class HasLifecycleCallbacksSuper
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /** @var string[] */
    public $invoked = [];

    /** @ODM\PrePersist */
    public function prePersist(): void
    {
        $this->invoked[] = 'super';
    }
}

/** @ODM\MappedSuperclass @ODM\HasLifecycleCallbacks */
abstract class HasLifecycleCallbacksSuperAnnotated
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /** @var string[] */
    public $invoked = [];

    /** @ODM\PrePersist */
    public function prePersist(): void
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
    public function prePersist(): void
    {
        $this->invoked[] = 'sub';
    }
}

/** @ODM\Document */
class HasLifecycleCallbacksSubOverrideExtendsSuperAnnotated extends HasLifecycleCallbacksSuperAnnotated
{
    /** @ODM\PrePersist */
    public function prePersist(): void
    {
        $this->invoked[] = 'sub';
    }
}

/** @ODM\Document @ODM\HasLifecycleCallbacks */
class HasLifecycleCallbacksSubOverrideAnnotatedExtendsSuper extends HasLifecycleCallbacksSuper
{
    /** @ODM\PrePersist */
    public function prePersist(): void
    {
        $this->invoked[] = 'sub';
    }
}

/** @ODM\Document @ODM\HasLifecycleCallbacks */
class HasLifecycleCallbacksSubOverrideAnnotatedExtendsSuperAnnotated extends HasLifecycleCallbacksSuperAnnotated
{
    /** @ODM\PrePersist */
    public function prePersist(): void
    {
        $this->invoked[] = 'sub';
    }
}
