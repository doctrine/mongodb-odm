<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class GH921Test extends BaseTest
{
    public function testPersistentCollectionCountAndIterationShouldBeConsistent(): void
    {
        $user = new GH921User();
        $user->setName('smith');

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->getRepository(GH921User::class)->findOneBy(['name' => 'smith']);

        $postA = new GH921Post();
        $user->addPost($postA);

        $this->dm->persist($postA);
        $this->dm->flush();

        $this->assertInstanceOf(PersistentCollectionInterface::class, $user->getPosts());
        $this->assertFalse($user->getPosts()->isDirty(), 'A flushed collection should not be dirty');
        $this->assertTrue($user->getPosts()->isInitialized(), 'A flushed collection should be initialized');
        $this->assertCount(1, $user->getPosts());
        $this->assertCount(1, $user->getPosts()->toArray());

        $this->dm->refresh($user);

        $this->assertInstanceOf(PersistentCollectionInterface::class, $user->getPosts());
        $this->assertFalse($user->getPosts()->isDirty(), 'A refreshed collection should not be dirty');
        $this->assertFalse($user->getPosts()->isInitialized(), 'A refreshed collection should not be initialized');
        $this->assertCount(1, $user->getPosts());
        $this->assertCount(1, $user->getPosts()->toArray());

        $this->dm->refresh($user);

        $postB = new GH921Post();
        $user->addPost($postB);
        $this->dm->persist($postB);

        $this->assertInstanceOf(PersistentCollectionInterface::class, $user->getPosts());
        $this->assertTrue($user->getPosts()->isDirty(), 'A refreshed collection then modified should be dirty');
        $this->assertFalse($user->getPosts()->isInitialized(), 'A refreshed collection then modified should not be initialized');
        $this->assertCount(2, $user->getPosts());
        $this->assertCount(2, $user->getPosts()->toArray());

        $user->getPosts()->initialize();

        $this->assertInstanceOf(PersistentCollectionInterface::class, $user->getPosts());
        $this->assertTrue($user->getPosts()->isDirty(), 'A dirty collection then initialized should remain dirty');
        $this->assertCount(2, $user->getPosts());
        $this->assertCount(2, $user->getPosts()->toArray());
    }
}

/** @ODM\Document */
class GH921User
{
    /** @ODM\Id */
    private $id;

    /** @ODM\Field(type="string") */
    private $name;

    /** @ODM\ReferenceMany(targetDocument=GH921Post::class) */
    private $posts;

    public function __construct()
    {
        $this->posts = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name): void
    {
        $this->name = $name;
    }

    public function addPost(GH921Post $post): void
    {
        $this->posts[] = $post;
    }

    public function getPosts(): Collection
    {
        return $this->posts;
    }
}

/** @ODM\Document */
class GH921Post
{
    /** @ODM\Id */
    private $id;

    /** @ODM\Field(type="string") */
    private $name;

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name): void
    {
        $this->name = $name;
    }
}
