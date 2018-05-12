<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class GH1232Test extends BaseTest
{
    /**
     * @doesNotPerformAssertions
     */
    public function testRemoveDoesNotCauseErrors()
    {
        $post = new GH1232Post();
        $this->dm->persist($post);
        $this->dm->flush();

        $comment = new GH1232Comment();
        $comment->post = $post;
        $this->dm->persist($comment);
        $this->dm->flush();

        $this->dm->refresh($post);

        $this->dm->remove($post);
        $this->dm->flush();
    }
}

/** @ODM\Document */
class GH1232Post
{
    public const CLASSNAME = __CLASS__;

    /** @ODM\Id */
    public $id;

    /** @ODM\ReferenceMany(targetDocument=GH1232Comment::class, mappedBy="post", cascade={"remove"}) */
    protected $comments;

    /**
     * @ODM\ReferenceMany(
     *     targetDocument=GH1232Comment::class,
     *     mappedBy="post",
     *     repositoryMethod="getLongComments",
     * )
     */
    protected $longComments;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
    }
}

/** @ODM\Document(repositoryClass="GH1232CommentRepository") */
class GH1232Comment
{
    /** @ODM\Id */
    public $id;

    /** @ODM\ReferenceOne(targetDocument=GH1232Post::class) */
    public $post;
}

class GH1232CommentRepository extends DocumentRepository
{
    public function getLongComments(GH1232Post $post)
    {
        return $this
            ->createQueryBuilder()
            ->field('post')
            ->references($post)
            ->sort('_id', 'asc')
            ->getQuery()
            ->execute();
    }
}
