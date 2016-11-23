<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class RedundantFieldsTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testSimpleRedundantField()
    {
        $blog = new Blog('my-blog');
        $this->dm->persist($blog);

        $post = new Post($blog, 'My first post');
        $this->dm->persist($post);
        $this->dm->flush();

        $rawPost = $this->dm->createQueryBuilder(Post::class)
            ->find()
            ->field('id')
            ->equals($post->getId())
            ->hydrate(false)
            ->getQuery()
            ->getSingleResult();

        $this->assertSame('my-blog', $rawPost['blog']['title']);
        $this->assertInstanceOf(\MongoDate::class, $rawPost['blog']['creationDate']);
    }

    public function testDatabaseValueIsPreparedInQuery()
    {
        $blog = new Blog('my-blog');
        $this->dm->persist($blog);

        $post = new Post($blog, 'My first post');
        $this->dm->persist($post);
        $this->dm->flush();

        $result = $this->dm->createQueryBuilder(Post::class)
            ->find()
            ->field('blog.creationDate')
            ->equals($blog->getCreationDate())
            ->getQuery()
            ->execute();

        $this->assertCount(1, $result);
    }
}

/**
* @ODM\Document()
*/
class Blog
{
    /** @ODM\Id */
    protected $id;

    /** @ODM\Field(type="string") */
    protected $title;

    /** @ODM\Field(type="date") */
    protected $creationDate;

    public function __construct($title)
    {
        $this->title = $title;
        $this->creationDate = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getCreationDate()
    {
        return $this->creationDate;
    }
}

/** @ODM\Document */
class Post
{
    /** @ODM\Id */
    protected $id;

    /** @ODM\ReferenceOne(targetDocument="Blog", redundantFields={"title","creationDate"}) */
    protected $blog;

    /** @ODM\Field(type="string") */
    protected $title;

    public function __construct(Blog $blog, $title)
    {
        $this->blog = $blog;
        $this->title = $title;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getBlog()
    {
        return $this->blog;
    }

    public function getTitle()
    {
        return $this->title;
    }
}
