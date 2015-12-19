<?php

namespace Doctrine\ODM\MongoDB\Tests\Persisters;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class DereferenceTest extends BaseTest
{
    private $pb;

    public function setUp()
    {
        parent::setUp();
        $this->pb = $this->dm->getUnitOfWork()->getPersistenceBuilder();
    }

    public function tearDown()
    {
        unset($this->pb);
        parent::tearDown();
    }

    public function testDereferenceManyWithSetStrategyDoesNotUnsetFirst()
    {
        $post = new Post();
        $post->title = 'test';
        $this->dm->persist($post);
        $this->dm->flush();
        $this->dm->clear();

        $post = $this->dm->find(get_class($post), $post->id);

        $comment = new Comment();
        $comment->subject = 'test';

        $post->comments = array();
        $post->comments[] = $comment;

        $this->dm->flush();

    }
}

/** @ODM\Document */
class Post
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $title;

    /** @ODM\EmbedMany(targetDocument="Comment", strategy="set") */
    public $comments = array();
}

/** @ODM\EmbeddedDocument */
class Comment
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $subject;
}
