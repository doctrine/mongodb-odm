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
        $persister->delete($user->phonenumbers, array('safe' => true));

        $user = $this->dm->getDocumentCollection(__NAMESPACE__ . '\CollectionPersisterUser')->findOne(array('username' => 'jwage'));
        $this->assertFalse(isset($user['phonenumbers']), 'Test that the phonenumbers field was deleted');
    }

    public function testDeleteEmbedMany()
    {
        $persister = $this->getCollectionPersister();
        $user = $this->getTestUser('jwage');
        $persister->delete($user->categories, array('safe' => true));

        $user = $this->dm->getDocumentCollection(__NAMESPACE__ . '\CollectionPersisterUser')->findOne(array('username' => 'jwage'));
        $this->assertFalse(isset($user['categories']), 'Test that the categories field was deleted');
    }

    public function testDeleteNestedEmbedMany()
    {
        $persister = $this->getCollectionPersister();
        $user = $this->getTestUser('jwage');

        $persister->delete($user->categories[0]->children[0]->children, array('safe' => true));
        $persister->delete($user->categories[0]->children[1]->children, array('safe' => true));

        $check = $this->dm->getDocumentCollection(__NAMESPACE__ . '\CollectionPersisterUser')->findOne(array('username' => 'jwage'));

        $this->assertFalse(isset($check['categories']['0']['children'][0]['children']));
        $this->assertFalse(isset($check['categories']['0']['children'][1]['children']));

        $persister->delete($user->categories[0]->children, array('safe' => true));
        $persister->delete($user->categories[1]->children, array('safe' => true));

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
        $this->dm->flush(null, array('safe' => true));
        return $user;
    }

    private function getCollectionPersister()
    {
        $uow = $this->dm->getUnitOfWork();
        $pb = new PersistenceBuilder($this->dm, $uow, '$');
        return new CollectionPersister($this->dm, $pb, $uow, '$');
    }

    public function testNestedEmbedManySetStrategy()
    {
        $post = new CollectionPersisterPost("Doest it work?");
        $comment = new CollectionPersisterComment("no way...", "skeptic");
        $comment2 = new CollectionPersisterComment("Hell yeah!", "asafdav");
        $comment3 = new CollectionPersisterComment("Awesome", "all");

        $post->comments->set('first', $comment);
        $comment->comments->set('first', $comment2);
        $comment->comments->set('second', $comment3);

        $this->dm->persist($post);
        $this->dm->flush(null, array('safe' => true));

        /** @var CollectionPersisterPost $check  */
        $check = $this->dm->getDocumentCollection(__NAMESPACE__ . '\CollectionPersisterPost')->findOne(array('post' => 'Doest it work?'));
        $this->assertEquals(1, count($check['comments']), 'First level persisted correctly');
        $this->assertTrue(isset($check['comments']['first']));
        $this->assertEquals(2, count($check['comments']['first']['comments']), 'Second level persisted correctly');
        $this->assertTrue(isset($check['comments']['first']['comments']['first']));
        $this->assertTrue(isset($check['comments']['first']['comments']['second']));

        // Test add comments
        $comment4 = new CollectionPersisterComment("Does add comment work?", "Someone");
        $comment5 = new CollectionPersisterComment("Sure!", "asafdav");

        $post->comments->set('second', $comment4);
        $comment4->comments->set('just-a-key', $comment5);

        $this->dm->persist($post);
        $this->dm->flush(null, array('safe' => true));
        $check = $this->dm->getDocumentCollection(__NAMESPACE__ . '\CollectionPersisterPost')->findOne(array('post' => 'Doest it work?'));

        $this->assertEquals(2, count($check['comments']), 'First level persisted correctly');
        $this->assertTrue(isset($check['comments']['first']));
        $this->assertEquals(2, count($check['comments']['first']['comments']), 'Second level persisted correctly');
        $this->assertTrue(isset($check['comments']['first']['comments']['first']));
        $this->assertTrue(isset($check['comments']['first']['comments']['second']));
        $this->assertTrue(isset($check['comments']['second']));
        $this->assertEquals($comment4->comment, $check['comments']['second']['comment']);
        $this->assertEquals(1, count($check['comments']['second']['comments']), 'New comment persisted correctly');
        $this->assertTrue(isset($check['comments']['second']['comments']['just-a-key']));
        $this->assertEquals($comment5->comment, $check['comments']['second']['comments']['just-a-key']['comment']);

        // Update two comments
        $comment4->comment = "Sorry, I could tell";
        $comment3->comment = "Hallelujah";

        $this->dm->persist($post);
        $this->dm->flush(null, array('safe' => true));
        $check = $this->dm->getDocumentCollection(__NAMESPACE__ . '\CollectionPersisterPost')->findOne(array('post' => 'Doest it work?'));

        $this->assertEquals(2, count($check['comments']), 'First level persisted correctly');
        $this->assertTrue(isset($check['comments']['first']));
        $this->assertEquals(2, count($check['comments']['first']['comments']), 'Second level persisted correctly');
        $this->assertTrue(isset($check['comments']['first']['comments']['first']));
        $this->assertTrue(isset($check['comments']['first']['comments']['second']));
        $this->assertEquals($comment3->comment, $check['comments']['first']['comments']['second']['comment']);
        $this->assertTrue(isset($check['comments']['second']));
        $this->assertEquals($comment4->comment, $check['comments']['second']['comment']);
        $this->assertEquals(1, count($check['comments']['second']['comments']), 'New comment persisted correctly');
        $this->assertTrue(isset($check['comments']['second']['comments']['just-a-key']));
        $this->assertEquals($comment5->comment, $check['comments']['second']['comments']['just-a-key']['comment']);

        // Delete  comment
        unset($post->comments['second']);
        $this->dm->persist($post);
        $this->dm->flush(null, array('safe' => true));
        $check = $this->dm->getDocumentCollection(__NAMESPACE__ . '\CollectionPersisterPost')->findOne(array('post' => 'Doest it work?'));

        $this->assertEquals(1, count($check['comments']), 'First level persisted correctly');
        $this->assertTrue(isset($check['comments']['first']));
        $this->assertEquals(2, count($check['comments']['first']['comments']), 'Second level persisted correctly');
        $this->assertTrue(isset($check['comments']['first']['comments']['first']));
        $this->assertTrue(isset($check['comments']['first']['comments']['second']));
        $this->assertEquals($comment3->comment, $check['comments']['first']['comments']['second']['comment']);
        $this->assertFalse(isset($check['comments']['second']));
    }
}

/** @ODM\Document(collection="user_collection_persister_test") */
class CollectionPersisterUser
{
    /** @ODM\Id */
    public $id;

    /** @ODM\String */
    public $username;

    /** @ODM\EmbedMany(targetDocument="CollectionPersisterCategory") */
    public $categories = array();

    /** @ODM\ReferenceMany(targetDocument="CollectionPersisterPhonenumber", cascade={"persist"}) */
    public $phonenumbers = array();
}

/** @ODM\EmbeddedDocument */
class CollectionPersisterCategory
{
    /** @ODM\String */
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

    /** @ODM\String */
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

  /** @ODM\String */
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

  /** @ODM\String */
  public $comment;

  /** @ODM\String */
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



