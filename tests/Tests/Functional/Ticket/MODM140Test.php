<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\Functional\EmbeddedTestLevel0;
use Documents\Functional\EmbeddedTestLevel1;
use Documents\Functional\EmbeddedTestLevel2;

class MODM140Test extends BaseTestCase
{
    public function testInsertingNestedEmbeddedCollections(): void
    {
        $category       = new Category();
        $category->name = 'My Category';

        $post1 = new Post();
        $post1->versions->add(new PostVersion('P1V1'));
        $post1->versions->add(new PostVersion('P1V2'));

        $category->posts->add($post1);

        $this->dm->persist($category);
        $this->dm->flush();
        $this->dm->clear();

        $category = $this->dm->getRepository(Category::class)->findOneBy(['name' => 'My Category']);
        $post2    = new Post();
        $post2->versions->add(new PostVersion('P2V1'));
        $post2->versions->add(new PostVersion('P2V2'));
        $category->posts->add($post2);

        $this->dm->flush();
        $this->dm->clear();

        $category = $this->dm->getRepository(Category::class)->findOneBy(['name' => 'My Category']);
        // Should be: 1 Category, 2 Post, 2 PostVersion in each Post
        self::assertEquals(2, $category->posts->count());
        self::assertEquals(2, $category->posts->get(0)->versions->count());
        self::assertEquals(2, $category->posts->get(1)->versions->count());
    }

    public function testInsertingEmbeddedCollectionWithRefMany(): void
    {
        $comment = new Comment();

        $post             = new Post();
        $post->comments[] = $comment;

        $category       = new Category();
        $category->name = 'My Category';
        $category->posts->add($post);

        $this->dm->persist($comment);
        $this->dm->persist($post);
        $this->dm->persist($category);
        $this->dm->flush();
        $this->dm->clear();

        $category = $this->dm->getRepository(Category::class)->findOneBy(['name' => 'My Category']);
        self::assertEquals(1, $category->posts->count());
        self::assertEquals(1, $category->posts->get(0)->comments->count());
    }

    public function testAddingAnotherEmbeddedDocument(): void
    {
        $test       = new EmbeddedTestLevel0();
        $test->name = 'test';

        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->getRepository(EmbeddedTestLevel0::class)->findOneBy(['name' => 'test']);
        self::assertInstanceOf(EmbeddedTestLevel0::class, $test);

        $level1       = new EmbeddedTestLevel1();
        $level1->name = 'test level 1 #1';

        $level2           = new EmbeddedTestLevel2();
        $level2->name     = 'test level 2 #1 in level 1 #1';
        $level1->level2[] = $level2;

        $level2           = new EmbeddedTestLevel2();
        $level2->name     = 'test level 2 #2 in level 1 #1';
        $level1->level2[] = $level2;

        $test->level1[] = $level1;

        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->getRepository(EmbeddedTestLevel0::class)->findOneBy(['name' => 'test']);
        self::assertCount(1, $test->level1);
        self::assertCount(2, $test->level1[0]->level2);

        $level1       = new EmbeddedTestLevel1();
        $level1->name = 'test level 1 #2';

        $level2           = new EmbeddedTestLevel2();
        $level2->name     = 'test level 2 #1 in level 1 #2';
        $level1->level2[] = $level2;

        $level2           = new EmbeddedTestLevel2();
        $level2->name     = 'test level 2 #2 in level 1 #2';
        $level1->level2[] = $level2;

        $test->level1[] = $level1;

        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->getRepository(EmbeddedTestLevel0::class)->findOneBy(['name' => 'test']);
        self::assertCount(2, $test->level1);
        self::assertCount(2, $test->level1[0]->level2);
        self::assertCount(2, $test->level1[1]->level2);
    }
}

#[ODM\Document]
class Category
{
    /** @var string|null */
    #[ODM\Id]
    protected $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $name;

    /** @var Collection<int, Post> */
    #[ODM\EmbedMany(targetDocument: Post::class)]
    public $posts;

    public function __construct()
    {
        $this->posts = new ArrayCollection();
    }
}

#[ODM\EmbeddedDocument]
class Post
{
    /** @var Collection<int, PostVersion> */
    #[ODM\EmbedMany(targetDocument: PostVersion::class)]
    public $versions;

    /** @var Collection<int, Comment> */
    #[ODM\ReferenceMany(targetDocument: Comment::class)]
    public $comments;

    public function __construct()
    {
        $this->versions = new ArrayCollection();
        $this->comments = new ArrayCollection();
    }
}

#[ODM\EmbeddedDocument]
class PostVersion
{
    /** @var string */
    #[ODM\Field(type: 'string')]
    public $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}

#[ODM\Document]
class Comment
{
    /** @var string|null */
    #[ODM\Id]
    protected $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $content;
}
