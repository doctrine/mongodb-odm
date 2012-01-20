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