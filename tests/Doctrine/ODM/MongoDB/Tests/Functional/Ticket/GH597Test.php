<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @author Jordan Stout <j@jrdn.org>
 */
class GH597Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testEmbedManyGetsUnset()
    {
        $post = new GH597Post();
        $this->dm->persist($post);
        $this->dm->flush($post);
        $this->dm->clear();

        // default behavior on inserts already leaves out embedded documents
        $expectedDocument = array('_id' => new \MongoId($post->getId()));
        $this->assertPostDocument($expectedDocument, $post);

        // fill documents with comments
        $post = $this->dm->find(__NAMESPACE__ . '\GH597Post', $post->getId());
        $post->comments = new ArrayCollection(array(
            new GH597Comment('Comment 1'),
            new GH597Comment('Comment 2'),
            new GH597Comment('Comment 3')
        ));
        $this->dm->persist($post);
        $this->dm->flush($post);
        $this->dm->clear();

        $expectedDocument = array(
            '_id' => new \MongoId($post->getId()),
            'comments' => array(
                array('comment' => 'Comment 1'),
                array('comment' => 'Comment 2'),
                array('comment' => 'Comment 3')
            )
        );
        $this->assertPostDocument($expectedDocument, $post);

        // trigger update
        $post = $this->dm->find(__NAMESPACE__ . '\GH597Post', $post->getId());
        $this->assertCount(3, $post->getComments());
        $post->comments = null;
        $this->dm->flush($post);
        $this->dm->clear();

        $post = $this->dm->find(__NAMESPACE__ . '\GH597Post', $post->getId());
        $this->assertCount(0, $post->getComments());

        // make sure embedded documents got unset
        $expectedDocument = array('_id' => new \MongoId($post->getId()));
        $this->assertPostDocument($expectedDocument, $post);
    }

    /**
     * Asserts that raw document matches expected document.
     *
     * @param array $expected
     * @param GH597Post $post
     */
    private function assertPostDocument(array $expected, GH597Post $post)
    {
        $document = $this->getPostCollection()->findOne(array('_id' => new \MongoId($post->getId())));
        $this->assertEquals($expected, $document);
    }

    /**
     * @return \MongoCollection
     */
    private function getPostCollection()
    {
        return $this->dm
            ->getConnection()
            ->getMongo()
            ->selectCollection('doctrine_odm_tests', 'GH597Post');
    }
}

/** @ODM\Document() */
class GH597Post
{
    /** @ODM\Id */
    public $id;

    /** @ODM\EmbedMany(targetDocument="GH597Comment") */
    public $comments;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getComments()
    {
        return $this->comments;
    }
}

/** @ODM\EmbeddedDocument */
class GH597Comment
{
    /** @ODM\String() */
    public $comment;

    public function __construct($comment)
    {
        $this->comment = $comment;
    }
}
