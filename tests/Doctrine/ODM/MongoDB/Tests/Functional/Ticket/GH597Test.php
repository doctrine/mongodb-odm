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

    public function testReferenceManyGetsUnset()
    {
        $post = new GH597Post();
        $this->dm->persist($post);
        $this->dm->flush();
        $this->dm->clear();

        // default behavior on inserts already leaves out referenced documents
        $expectedDocument = array('_id' => new \MongoId($post->getId()));
        $this->assertPostDocument($expectedDocument, $post);

        // associate post with many GH597ReferenceMany documents
        $post = $this->dm->find(__NAMESPACE__ . '\GH597Post', $post->getId());

        $referenceMany1 = new GH597ReferenceMany('one');
        $this->dm->persist($referenceMany1);
        $referenceMany2 = new GH597ReferenceMany('two');
        $this->dm->persist($referenceMany2);

        $post->referenceMany = new ArrayCollection(array($referenceMany1, $referenceMany2));
        $this->dm->persist($post);
        $this->dm->flush($post);
        $this->dm->clear();

        $expectedDocument = array(
            '_id' => new \MongoId($post->getId()),
            'referenceMany' => array(
                new \MongoId($referenceMany1->getId()),
                new \MongoId($referenceMany2->getId())
            )
        );
        $this->assertPostDocument($expectedDocument, $post);

        // trigger update
        $post = $this->dm->find(__NAMESPACE__ . '\GH597Post', $post->getId());
        $this->assertCount(2, $post->getReferenceMany());
        $post->referenceMany = null;
        $this->dm->flush($post);
        $this->dm->clear();

        $post = $this->dm->find(__NAMESPACE__ . '\GH597Post', $post->getId());
        $this->assertCount(0, $post->getReferenceMany());

        // make sure reference-many documents got unset
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

    /** @ODM\ReferenceMany(targetDocument="GH597ReferenceMany", simple="true") */
    public $referenceMany;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->referenceMany = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getComments()
    {
        return $this->comments;
    }

    public function getReferenceMany()
    {
        return $this->referenceMany;
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


/** @ODM\Document */
class GH597ReferenceMany
{
    /** @ODM\Id */
    public $id;

    /** @ODM\String() */
    public $field;

    public function __construct($field)
    {
        $this->field = $field;
    }

    public function getId()
    {
        return $this->id;
    }
}
