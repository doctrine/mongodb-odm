<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Doctrine\ODM\MongoDB\Persisters\CollectionPersister;
use Doctrine\ODM\MongoDB\Persisters\PersistenceBuilder;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class CollectionPersisterTest extends BaseTest
{
    public function testDeleteReferenceMany()
    {
        $persister = $this->getCollectionPersister();
        $user = $this->getTestUser('jwage');
        $persister->delete($user->phonenumbers, array());

        $user = $this->dm->getDocumentCollection(__NAMESPACE__ . '\CollectionPersisterUser')->findOne(array('username' => 'jwage'));
        $this->assertFalse(isset($user['phonenumbers']), 'Test that the phonenumbers field was deleted');
    }

    public function testDeleteEmbedMany()
    {
        $persister = $this->getCollectionPersister();
        $user = $this->getTestUser('jwage');
        $persister->delete($user->categories, array());

        $user = $this->dm->getDocumentCollection(__NAMESPACE__ . '\CollectionPersisterUser')->findOne(array('username' => 'jwage'));
        $this->assertFalse(isset($user['categories']), 'Test that the categories field was deleted');
    }

    public function testDeleteNestedEmbedMany()
    {
        $persister = $this->getCollectionPersister();
        $user = $this->getTestUser('jwage');

        $persister->delete($user->categories[0]->children[0]->children, array());
        $persister->delete($user->categories[0]->children[1]->children, array());

        $check = $this->dm->getDocumentCollection(__NAMESPACE__ . '\CollectionPersisterUser')->findOne(array('username' => 'jwage'));

        $this->assertFalse(isset($check['categories']['0']['children'][0]['children']));
        $this->assertFalse(isset($check['categories']['0']['children'][1]['children']));

        $persister->delete($user->categories[0]->children, array());
        $persister->delete($user->categories[1]->children, array());

        $check = $this->dm->getDocumentCollection(__NAMESPACE__ . '\CollectionPersisterUser')->findOne(array('username' => 'jwage'));

        $this->assertFalse(isset($check['categories'][0]['children']), 'Test that the nested children categories field was deleted');
        $this->assertTrue(isset($check['categories'][0]), 'Test that the category with the children still exists');

        $this->assertFalse(isset($check['categories'][1]['children']), 'Test that the nested children categories field was deleted');
        $this->assertTrue(isset($check['categories'][1]), 'Test that the category with the children still exists');
    }

    public function testDeleteRows()
    {
        $persister = $this->getCollectionPersister();
        $user = $this->getTestUser('jwage');

        unset($user->phonenumbers[0]);
        unset($user->phonenumbers[1]);

        unset($user->categories[0]->children[0]->children[0]);
        unset($user->categories[0]->children[0]->children[1]);

        unset($user->categories[0]->children[1]->children[0]);
        unset($user->categories[0]->children[1]->children[1]);

        $this->dm->flush();

        $check = $this->dm->getDocumentCollection(__NAMESPACE__ . '\CollectionPersisterUser')->findOne(array('username' => 'jwage'));

        $this->assertFalse(isset($check['phonenumbers'][0]));
        $this->assertFalse(isset($check['phonenumbers'][1]));

        $this->assertFalse(isset($check['categories'][0]['children'][0]['children'][0]));
        $this->assertFalse(isset($check['categories'][0]['children'][0]['children'][1]));

        $this->assertFalse(isset($check['categories'][0]['children'][1]['children'][0]));
        $this->assertFalse(isset($check['categories'][0]['children'][1]['children'][1]));

        unset($user->categories[0]);
        unset($user->categories[1]);
        $this->dm->flush();

        $check = $this->dm->getDocumentCollection(__NAMESPACE__ . '\CollectionPersisterUser')->findOne(array('username' => 'jwage'));
        $this->assertFalse(isset($check['categories'][0]));
        $this->assertFalse(isset($check['categories'][1]));
    }

    public function testInsertRows()
    {
        $user = $this->getTestUser('jwage');
        $user->phonenumbers[] = new CollectionPersisterPhonenumber('6155139185');
        $user->phonenumbers[] = new CollectionPersisterPhonenumber('6155139185');
        $this->dm->flush();

        $check = $this->dm->getDocumentCollection(__NAMESPACE__ . '\CollectionPersisterUser')->findOne(array('username' => 'jwage'));
        $this->assertEquals(4, count($check['phonenumbers']));
        $this->assertEquals((string) $check['phonenumbers'][2]['$id'], $user->phonenumbers[2]->id);
        $this->assertEquals((string) $check['phonenumbers'][3]['$id'], $user->phonenumbers[3]->id);

        $user->categories[] = new CollectionPersisterCategory('Test');
        $user->categories[] = new CollectionPersisterCategory('Test');
        $this->dm->flush();

        $check = $this->dm->getDocumentCollection(__NAMESPACE__ . '\CollectionPersisterUser')->findOne(array('username' => 'jwage'));
        $this->assertEquals(4, count($check['categories']));

        $user->categories[3]->children[0] = new CollectionPersisterCategory('Test');
        $user->categories[3]->children[1] = new CollectionPersisterCategory('Test');
        $user->categories[3]->children[1]->children[0] = new CollectionPersisterCategory('Test');
        $user->categories[3]->children[1]->children[1] = new CollectionPersisterCategory('Test');
        $this->dm->flush();

        $check = $this->dm->getDocumentCollection(__NAMESPACE__ . '\CollectionPersisterUser')->findOne(array('username' => 'jwage'));
        $this->assertEquals(2, count($check['categories'][3]['children']));
        $this->assertEquals(2, count($check['categories'][3]['children']['1']['children']));
    }

    private function getTestUser($username)
    {
        $user = new CollectionPersisterUser();
        $user->username = $username;
        $user->phonenumbers[0] = new CollectionPersisterPhonenumber('6155139185');
        $user->phonenumbers[1] = new CollectionPersisterPhonenumber('6155139185');

        $user->categories[0] = new CollectionPersisterCategory('Category0');
        $user->categories[1] = new CollectionPersisterCategory('Category1');

        $user->categories[0]->children[0] = new CollectionPersisterCategory('Child of Category0 1');
        $user->categories[0]->children[1] = new CollectionPersisterCategory('Child of Category0 2');

        $user->categories[1]->children[0] = new CollectionPersisterCategory('Child of Category1 1');
        $user->categories[1]->children[1] = new CollectionPersisterCategory('Child of Category1 2');

        $user->categories[0]->children[0]->children[0] = new CollectionPersisterCategory('Child of Category1_0 1');
        $user->categories[0]->children[0]->children[1] = new CollectionPersisterCategory('Child of Category1_0 2');

        $user->categories[0]->children[1]->children[0] = new CollectionPersisterCategory('Child of Category1_1 1');
        $user->categories[0]->children[1]->children[1] = new CollectionPersisterCategory('Child of Category1_1 2');

        $this->dm->persist($user);
        $this->dm->flush(null, array());
        return $user;
    }

    private function getCollectionPersister()
    {
        $uow = $this->dm->getUnitOfWork();
        $pb = new PersistenceBuilder($this->dm, $uow);
        return new CollectionPersister($this->dm, $pb, $uow);
    }

    public function testNestedEmbedManySetStrategy()
    {
        $post = new CollectionPersisterPost('postA');
        $commentA = new CollectionPersisterComment('commentA', 'userA');
        $commentAA = new CollectionPersisterComment('commentAA', 'userB');
        $commentAB = new CollectionPersisterComment('commentAB', 'userC');

        $post->comments->set('a', $commentA);
        $commentA->comments->set('a', $commentAA);
        $commentA->comments->set('b', $commentAB);

        $this->dm->persist($post);
        $this->dm->flush();

        $doc = $this->dm->getDocumentCollection(__NAMESPACE__ . '\CollectionPersisterPost')->findOne(array('post' => 'postA'));

        $this->assertCount(1, $doc['comments']);
        $this->assertEquals($commentA->comment, $doc['comments']['a']['comment']);
        $this->assertEquals($commentA->by, $doc['comments']['a']['by']);
        $this->assertCount(2, $doc['comments']['a']['comments']);
        $this->assertEquals($commentAA->comment, $doc['comments']['a']['comments']['a']['comment']);
        $this->assertEquals($commentAA->by, $doc['comments']['a']['comments']['a']['by']);
        $this->assertEquals($commentAB->comment, $doc['comments']['a']['comments']['b']['comment']);
        $this->assertEquals($commentAB->by, $doc['comments']['a']['comments']['b']['by']);

        // Add a new top-level comment with a nested comment
        $commentB = new CollectionPersisterComment('commentB', 'userD');
        $commentBA = new CollectionPersisterComment('commentBA', 'userE');

        $post->comments->set('b', $commentB);
        $commentB->comments->set('a', $commentBA);

        $this->dm->persist($post);
        $this->dm->flush();

        $doc = $this->dm->getDocumentCollection(__NAMESPACE__ . '\CollectionPersisterPost')->findOne(array('post' => 'postA'));

        $this->assertCount(2, $doc['comments']);
        $this->assertEquals($commentA->comment, $doc['comments']['a']['comment']);
        $this->assertEquals($commentB->comment, $doc['comments']['b']['comment']);
        $this->assertEquals($commentB->by, $doc['comments']['b']['by']);
        $this->assertCount(2, $doc['comments']['a']['comments']);
        $this->assertEquals($commentAA->comment, $doc['comments']['a']['comments']['a']['comment']);
        $this->assertEquals($commentAB->comment, $doc['comments']['a']['comments']['b']['comment']);
        $this->assertCount(1, $doc['comments']['b']['comments']);
        $this->assertEquals($commentBA->comment, $doc['comments']['b']['comments']['a']['comment']);
        $this->assertEquals($commentBA->by, $doc['comments']['b']['comments']['a']['by']);

        // Update two comments
        $commentB->comment = 'commentB-modified';
        $commentAB->comment = 'commentAB-modified';

        $this->dm->persist($post);
        $this->dm->flush();

        $doc = $this->dm->getDocumentCollection(__NAMESPACE__ . '\CollectionPersisterPost')->findOne(array('post' => 'postA'));

        $this->assertCount(2, $doc['comments']);
        $this->assertEquals($commentA->comment, $doc['comments']['a']['comment']);
        $this->assertEquals($commentB->comment, $doc['comments']['b']['comment']);
        $this->assertCount(2, $doc['comments']['a']['comments']);
        $this->assertEquals($commentAA->comment, $doc['comments']['a']['comments']['a']['comment']);
        $this->assertEquals($commentAB->comment, $doc['comments']['a']['comments']['b']['comment']);
        $this->assertCount(1, $doc['comments']['b']['comments']);
        $this->assertEquals($commentBA->comment, $doc['comments']['b']['comments']['a']['comment']);

        // Delete a comment
        unset($post->comments['b']);

        $this->dm->persist($post);
        $this->dm->flush();

        $doc = $this->dm->getDocumentCollection(__NAMESPACE__ . '\CollectionPersisterPost')->findOne(array('post' => 'postA'));

        $this->assertCount(1, $doc['comments']);
        $this->assertEquals($commentA->comment, $doc['comments']['a']['comment']);
        $this->assertCount(2, $doc['comments']['a']['comments']);
        $this->assertEquals($commentAA->comment, $doc['comments']['a']['comments']['a']['comment']);
        $this->assertEquals($commentAB->comment, $doc['comments']['a']['comments']['b']['comment']);
        $this->assertFalse(isset($doc['comments']['b']));
    }

    public function testFindBySetStrategyKey()
    {
        $post = new CollectionPersisterPost('postA');
        $commentA = new CollectionPersisterComment('commentA', 'userA');
        $commentAB = new CollectionPersisterComment('commentAA', 'userB');

        $post->comments['a'] = $commentA;
        $commentA->comments['b'] = $commentAB;

        $this->dm->persist($post);
        $this->dm->flush();

        $this->assertSame($post, $this->dm->getRepository(get_class($post))->findOneBy(array('comments.a.by' => 'userA')));
        $this->assertSame($post, $this->dm->getRepository(get_class($post))->findOneBy(array('comments.a.comments.b.by' => 'userB')));
    }
}

/** @ODM\Document(collection="user_collection_persister_test") */
class CollectionPersisterUser
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $username;

    /** @ODM\EmbedMany(targetDocument="CollectionPersisterCategory") */
    public $categories = array();

    /** @ODM\ReferenceMany(targetDocument="CollectionPersisterPhonenumber", cascade={"persist"}) */
    public $phonenumbers = array();
}

/** @ODM\EmbeddedDocument */
class CollectionPersisterCategory
{
    /** @ODM\Field(type="string") */
    public $name;

    /** @ODM\EmbedMany(targetDocument="CollectionPersisterCategory") */
    public $children = array();

    public function __construct($name)
    {
        $this->name = $name;
    }
}

/** @ODM\Document(collection="phonenumber_collection_persister_test") */
class CollectionPersisterPhonenumber
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $phonenumber;

    public function __construct($phonenumber)
    {
        $this->phonenumber = $phonenumber;
    }
}

/** @ODM\Document(collection="post_collection_persister_test") */
class CollectionPersisterPost
{
  /** @ODM\Id */
  public $id;

  /** @ODM\Field(type="string") */
  public $post;

  /** @ODM\EmbedMany(targetDocument="CollectionPersisterComment", strategy="set") */
  public $comments = array();

  function __construct($post)
  {
    $this->comments = new \Doctrine\Common\Collections\ArrayCollection();
    $this->post = $post;
  }


}

/** @ODM\EmbeddedDocument */
class CollectionPersisterComment
{
  /** @ODM\Id */
  public $id;

  /** @ODM\Field(type="string") */
  public $comment;

  /** @ODM\Field(type="string") */
  public $by;

  /** @ODM\EmbedMany(targetDocument="CollectionPersisterComment", strategy="set") */
  public $comments = array();

  function __construct($comment, $by)
  {
    $this->comments = new \Doctrine\Common\Collections\ArrayCollection();
    $this->comment = $comment;
    $this->by = $by;
  }
}



