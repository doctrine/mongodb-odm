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

        $posts = $user->getPosts();
        self::assertInstanceOf(PersistentCollectionInterface::class, $posts);
        self::assertFalse($posts->isDirty(), 'A flushed collection should not be dirty');
        self::assertTrue($posts->isInitialized(), 'A flushed collection should be initialized');
        self::assertCount(1, $posts);
        self::assertCount(1, $posts->toArray());

        $this->dm->refresh($user);

        $posts = $user->getPosts();
        self::assertInstanceOf(PersistentCollectionInterface::class, $posts);
        self::assertFalse($posts->isDirty(), 'A refreshed collection should not be dirty');
        self::assertFalse($posts->isInitialized(), 'A refreshed collection should not be initialized');
        self::assertCount(1, $posts);
        self::assertCount(1, $posts->toArray());

        $this->dm->refresh($user);

        $postB = new GH921Post();
        $user->addPost($postB);
        $this->dm->persist($postB);

        $posts = $user->getPosts();
        self::assertInstanceOf(PersistentCollectionInterface::class, $posts);
        self::assertTrue($posts->isDirty(), 'A refreshed collection then modified should be dirty');
        self::assertFalse($posts->isInitialized(), 'A refreshed collection then modified should not be initialized');
        self::assertCount(2, $posts);
        self::assertCount(2, $posts->toArray());

        $posts->initialize();

        self::assertTrue($posts->isDirty(), 'A dirty collection then initialized should remain dirty');
        self::assertCount(2, $posts);
        self::assertCount(2, $posts->toArray());
    }
}

/** @ODM\Document */
class GH921User
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    private $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    private $name;

    /**
     * @ODM\ReferenceMany(targetDocument=GH921Post::class)
     *
     * @var Collection<int, GH921Post>
     */
    private $posts;

    public function __construct()
    {
        $this->posts = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function addPost(GH921Post $post): void
    {
        $this->posts[] = $post;
    }

    /** @return Collection<int, GH921Post> */
    public function getPosts(): Collection
    {
        return $this->posts;
    }
}

/** @ODM\Document */
class GH921Post
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    private $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    private $name;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
