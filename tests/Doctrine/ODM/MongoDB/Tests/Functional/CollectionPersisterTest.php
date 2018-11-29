<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Doctrine\ODM\MongoDB\Persisters\CollectionPersister;
use Doctrine\ODM\MongoDB\Persisters\PersistenceBuilder;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\QueryLogger;

class CollectionPersisterTest extends BaseTest
{
    /**
     * @var QueryLogger
     */
    private $ql;

    protected function getConfiguration()
    {
        if ( ! isset($this->ql)) {
            $this->ql = new QueryLogger();
        }

        $config = parent::getConfiguration();
        $config->setLoggerCallable($this->ql);

        return $config;
    }

    public function testDeleteReferenceMany()
    {
        $persister = $this->getCollectionPersister();
        $user = $this->getTestUser('jwage');
        $persister->delete($user->phonenumbers, array());

        $user = $this->dm->getDocumentCollection(__NAMESPACE__ . '\CollectionPersisterUser')->findOne(array('username' => 'jwage'));
        $this->assertArrayNotHasKey('phonenumbers', $user, 'Test that the phonenumbers field was deleted');
    }

    public function testDeleteEmbedMany()
    {
        $persister = $this->getCollectionPersister();
        $user = $this->getTestUser('jwage');
        $persister->delete($user->categories, array());

        $user = $this->dm->getDocumentCollection(__NAMESPACE__ . '\CollectionPersisterUser')->findOne(array('username' => 'jwage'));
        $this->assertArrayNotHasKey('categories', $user, 'Test that the categories field was deleted');
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

    public function testDeleteAllEmbedMany()
    {
        $persister = $this->getCollectionPersister();
        $user      = $this->getTestUser('jwage');
        $persister->deleteAll($user, [$user->categories], []);
        $user = $this->dm->getDocumentCollection(CollectionPersisterUser::class)->findOne(['username' => 'jwage']);
        $this->assertArrayNotHasKey('categories', $user, 'Test that the categories field was deleted');
    }

    public function testDeleteAllReferenceMany()
    {
        $persister = $this->getCollectionPersister();
        $user      = $this->getTestUser('jwage');
        $persister->deleteAll($user, [$user->phonenumbers], []);
        $user = $this->dm->getDocumentCollection(CollectionPersisterUser::class)->findOne(['username' => 'jwage']);
        $this->assertArrayNotHasKey('phonenumbers', $user, 'Test that the phonenumbers field was deleted');
    }

    public function testDeleteAllNestedEmbedMany()
    {
        $persister = $this->getCollectionPersister();
        $user      = $this->getTestUser('jwage');
        $this->ql->clear();
        $persister->deleteAll(
            $user,
            [$user->categories[0]->children[0]->children, $user->categories[0]->children[1]->children],
            []
        );
        $this->assertCount(1, $this->ql, 'Deletion of several embedded-many collections of one document requires one query');
        $check = $this->dm->getDocumentCollection(CollectionPersisterUser::class)->findOne(['username' => 'jwage']);
        $this->assertFalse(isset($check['categories']['0']['children'][0]['children']));
        $this->assertFalse(isset($check['categories']['0']['children'][1]['children']));
        $this->ql->clear();
        $persister->deleteAll(
            $user,
            [$user->categories[0]->children, $user->categories[1]->children],
            []
        );
        $this->assertCount(1, $this->ql, 'Deletion of several embedded-many collections of one document requires one query');
        $check = $this->dm->getDocumentCollection(CollectionPersisterUser::class)->findOne(['username' => 'jwage']);
        $this->assertFalse(isset($check['categories'][0]['children']), 'Test that the nested children categories field was deleted');
        $this->assertTrue(isset($check['categories'][0]), 'Test that the category with the children still exists');
        $this->assertFalse(isset($check['categories'][1]['children']), 'Test that the nested children categories field was deleted');
        $this->assertTrue(isset($check['categories'][1]), 'Test that the category with the children still exists');
    }

    public function testDeleteAllNestedEmbedManyAndNestedParent()
    {
        $persister = $this->getCollectionPersister();
        $user      = $this->getTestUser('jwage');
        $this->ql->clear();
        $persister->deleteAll(
            $user,
            [$user->categories[0]->children[0]->children, $user->categories[0]->children[1]->children],
            []
        );
        $this->assertCount(1, $this->ql, 'Deletion of several embedded-many collections of one document requires one query');
        $check = $this->dm->getDocumentCollection(CollectionPersisterUser::class)->findOne(['username' => 'jwage']);
        $this->assertFalse(isset($check['categories']['0']['children'][0]['children']));
        $this->assertFalse(isset($check['categories']['0']['children'][1]['children']));
        $this->ql->clear();
        $persister->deleteAll(
            $user,
            [$user->categories[0]->children, $user->categories[0]->children[1]->children, $user->categories],
            []
        );
        $this->assertCount(1, $this->ql, 'Deletion of several embedded-many collections of one document requires one query');
        $check = $this->dm->getDocumentCollection(CollectionPersisterUser::class)->findOne(['username' => 'jwage']);
        $this->assertFalse(isset($check['categories']), 'Test that the nested categories field was deleted');
    }


    public function testDeleteRows()
    {
        $user = $this->getTestUser('jwage');

        unset($user->phonenumbers[0]);
        unset($user->phonenumbers[1]);

        unset($user->categories[0]->children[0]->children[0]);
        unset($user->categories[0]->children[0]->children[1]);

        unset($user->categories[0]->children[1]->children[0]);
        unset($user->categories[0]->children[1]->children[1]);

        $this->ql->clear();
        $this->dm->flush();
        $this->assertCount(2, $this->ql, 'Modification of several embedded-many collections of one document requires two queries');

        $check = $this->dm->getDocumentCollection(__NAMESPACE__ . '\CollectionPersisterUser')->findOne(array('username' => 'jwage'));

        $this->assertFalse(isset($check['phonenumbers'][0]));
        $this->assertFalse(isset($check['phonenumbers'][1]));

        $this->assertFalse(isset($check['categories'][0]['children'][0]['children'][0]));
        $this->assertFalse(isset($check['categories'][0]['children'][0]['children'][1]));

        $this->assertFalse(isset($check['categories'][0]['children'][1]['children'][0]));
        $this->assertFalse(isset($check['categories'][0]['children'][1]['children'][1]));

        unset($user->categories[0]);
        unset($user->categories[1]);
        $this->ql->clear();
        $this->dm->flush();
        $this->assertCount(2, $this->ql, 'Modification of embedded-many collection of one document requires two queries');

        $check = $this->dm->getDocumentCollection(__NAMESPACE__ . '\CollectionPersisterUser')->findOne(array('username' => 'jwage'));
        $this->assertFalse(isset($check['categories'][0]));
        $this->assertFalse(isset($check['categories'][1]));
    }

    public function testInsertRows()
    {
        $user = $this->getTestUser('jwage');
        $user->phonenumbers[] = new CollectionPersisterPhonenumber('6155139185');
        $user->phonenumbers[] = new CollectionPersisterPhonenumber('6155139185');
        $this->ql->clear();
        $this->dm->flush();
        $this->assertCount(2, $this->ql, 'Modification of embedded-many collection of one document requires two queries');

        $check = $this->dm->getDocumentCollection(__NAMESPACE__ . '\CollectionPersisterUser')->findOne(array('username' => 'jwage'));
        $this->assertCount(4, $check['phonenumbers']);
        $this->assertEquals((string) $check['phonenumbers'][2]['$id'], $user->phonenumbers[2]->id);
        $this->assertEquals((string) $check['phonenumbers'][3]['$id'], $user->phonenumbers[3]->id);

        $user->categories[] = new CollectionPersisterCategory('Test');
        $user->categories[] = new CollectionPersisterCategory('Test');
        $this->ql->clear();
        $this->dm->flush();
        $this->assertCount(
            1,
            $this->ql,
            'Modification of embedded-many collection of one document requires one query since no existing collection elements was removed'
        );

        $check = $this->dm->getDocumentCollection(__NAMESPACE__ . '\CollectionPersisterUser')->findOne(array('username' => 'jwage'));
        $this->assertCount(4, $check['categories']);

        $user->categories[3]->children[0] = new CollectionPersisterCategory('Test');
        $user->categories[3]->children[1] = new CollectionPersisterCategory('Test');
        $user->categories[3]->children[1]->children[0] = new CollectionPersisterCategory('Test');
        $user->categories[3]->children[1]->children[1] = new CollectionPersisterCategory('Test');
        $this->ql->clear();
        $this->dm->flush();
        $this->assertCount(
            1,
            $this->ql,
            'Modification of embedded-many collection of one document requires one query since no existing collection elements was removed'
        );

        $check = $this->dm->getDocumentCollection(__NAMESPACE__ . '\CollectionPersisterUser')->findOne(array('username' => 'jwage'));
        $this->assertCount(2, $check['categories'][3]['children']);
        $this->assertCount(2, $check['categories'][3]['children']['1']['children']);
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
        $this->ql->clear();
        $this->dm->flush();
        $this->assertCount(
            1,
            $this->ql,
            'Insertion of embedded-many collection of one document requires no additional queries'
        );

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
        $this->ql->clear();
        $this->dm->flush();
        $this->assertCount(
            1,
            $this->ql,
            'Modification of embedded-many collection of one document requires one query since no existing collection elements was removed'
        );

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
        $this->ql->clear();
        $this->dm->flush();
        $this->assertCount(
            1,
            $this->ql,
            'Modification of embedded-many collection of one document requires one query since no existing collection elements was removed'
        );

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
        $this->ql->clear();
        $this->dm->flush();
        $this->assertCount(
            1,
            $this->ql,
            'Modification of embedded-many collection of one document requires one query since collection, from which element was removed, have "set" store strategy.'
        );

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

    public function testPersistSeveralNestedEmbedManySetStrategy()
    {
        $structure = new CollectionPersisterStructure();
        $structure->set->add(new CollectionPersisterNestedStructure('nested1'));
        $structure->set->add(new CollectionPersisterNestedStructure('nested2'));
        $structure->set2->add(new CollectionPersisterNestedStructure('nested3'));
        $structure->set2->add(new CollectionPersisterNestedStructure('nested4'));

        $this->dm->persist($structure);
        $this->dm->flush();
        $this->assertCount(
            1,
            $this->ql,
            'Insertion of embedded-many collections of one document by "set" strategy requires no additional queries'
        );

        $structure->set->clear();
        $structure->set->add(new CollectionPersisterNestedStructure('nested5'));
        $structure->set2->add(new CollectionPersisterNestedStructure('nested6'));

        $this->ql->clear();
        $this->dm->flush();
        $this->assertCount(
            1,
            $this->ql,
            'Modification of embedded-many collections of one document by "set" strategy requires one query'
        );

        $this->assertSame($structure, $this->dm->getRepository(get_class($structure))->findOneBy(['id' => $structure->id]));
    }

    public function testPersistSeveralNestedEmbedManySetArrayStrategy()
    {
        $structure = new CollectionPersisterStructure();
        $structure->setArray->add(new CollectionPersisterNestedStructure('nested1'));
        $structure->setArray->add(new CollectionPersisterNestedStructure('nested2'));
        $structure->setArray2->add(new CollectionPersisterNestedStructure('nested3'));
        $structure->setArray2->add(new CollectionPersisterNestedStructure('nested4'));

        $this->dm->persist($structure);
        $this->dm->flush();
        $this->assertCount(
            1,
            $this->ql,
            'Insertion of embedded-many collections of one document by "setArray" strategy requires no additional queries'
        );

        $structure->setArray->clear();
        $structure->setArray->add(new CollectionPersisterNestedStructure('nested5'));
        $structure->setArray2->add(new CollectionPersisterNestedStructure('nested6'));

        $this->ql->clear();
        $this->dm->flush();
        $this->assertCount(
            1,
            $this->ql,
            'Modification of embedded-many collections of one document by "setArray" strategy requires one query'
        );

        $this->assertSame($structure, $this->dm->getRepository(get_class($structure))->findOneBy(['id' => $structure->id]));
    }

    public function testPersistSeveralNestedEmbedManyAddToSetStrategy()
    {
        $structure = new CollectionPersisterStructure();
        $structure->addToSet->add(new CollectionPersisterNestedStructure('nested1'));
        $structure->addToSet->add(new CollectionPersisterNestedStructure('nested2'));
        $structure->addToSet2->add(new CollectionPersisterNestedStructure('nested3'));
        $structure->addToSet2->add(new CollectionPersisterNestedStructure('nested4'));

        $this->dm->persist($structure);
        $this->dm->flush();
        $this->assertCount(
            2,
            $this->ql,
            'Insertion of embedded-many collections of one document by "addToSet" strategy requires one additional query'
        );

        $structure->addToSet->clear();
        $structure->addToSet->add(new CollectionPersisterNestedStructure('nested5'));
        $structure->addToSet2->add(new CollectionPersisterNestedStructure('nested6'));

        $this->ql->clear();
        $this->dm->flush();
        $this->assertCount(
            2,
            $this->ql,
            'Modification of embedded-many collections of one document by "addToSet" strategy requires two queries'
        );

        $this->assertSame($structure, $this->dm->getRepository(get_class($structure))->findOneBy(['id' => $structure->id]));
    }

    public function testPersistSeveralNestedEmbedManyPushAllStrategy()
    {
        $structure = new CollectionPersisterStructure();
        $structure->pushAll->add(new CollectionPersisterNestedStructure('nested1'));
        $structure->pushAll->add(new CollectionPersisterNestedStructure('nested2'));
        $structure->pushAll2->add(new CollectionPersisterNestedStructure('nested3'));
        $structure->pushAll2->add(new CollectionPersisterNestedStructure('nested4'));

        $this->dm->persist($structure);
        $this->dm->flush();
        $this->assertCount(
            1,
            $this->ql,
            'Insertion of embedded-many collections of one document by "pushAll" strategy requires no additional queries'
        );

        $structure->pushAll->add(new CollectionPersisterNestedStructure('nested5'));
        $structure->pushAll2->add(new CollectionPersisterNestedStructure('nested6'));

        $this->ql->clear();
        $this->dm->persist($structure);
        $this->dm->flush();
        $this->assertCount(
            2,
            $this->ql,
            'Modification of embedded-many collections of one document by "pushAll" strategy requires two queries'
        );

        $this->assertSame($structure, $this->dm->getRepository(get_class($structure))->findOneBy(['id' => $structure->id]));
    }

    public function testPersistSeveralNestedEmbedManyDifferentStrategies()
    {
        $structure = new CollectionPersisterStructure();
        $structure->set->add(new CollectionPersisterNestedStructure('nested1'));
        $structure->setArray->add(new CollectionPersisterNestedStructure('nested2'));
        $structure->pushAll->add(new CollectionPersisterNestedStructure('nested3'));

        $this->dm->persist($structure);
        $this->dm->flush();
        $this->assertCount(
            1,
            $this->ql,
            'Insertion of embedded-many collections of one document by "set", "setArray" and "pushAll" strategies requires no additional queries'
        );

        $structure->set->remove(0);
        $structure->set->add(new CollectionPersisterNestedStructure('nested5'));
        $structure->pushAll->remove(0);
        $structure->setArray->add(new CollectionPersisterNestedStructure('nested6'));
        $structure->setArray->remove(0);
        $structure->pushAll->add(new CollectionPersisterNestedStructure('nested7'));

        $this->ql->clear();
        $this->dm->persist($structure);
        $this->dm->flush();
        $this->assertCount(
            4,
            $this->ql,
            'Modification of embedded-many collections of one document by "set", "setArray" and "pushAll" strategies requires two queries'
        );

        $this->assertSame($structure, $this->dm->getRepository(get_class($structure))->findOneBy(['id' => $structure->id]));
    }

    public function testPersistSeveralNestedEmbedManyDifferentStrategiesDeepNesting()
    {
        $structure = new CollectionPersisterStructure();
        $nested1   = new CollectionPersisterNestedStructure('nested1');
        $nested2   = new CollectionPersisterNestedStructure('nested2');
        $nested3   = new CollectionPersisterNestedStructure('nested3');
        $nested1->setArray->add(new CollectionPersisterNestedStructure('setArray_nested1'));
        $nested2->pushAll->add(new CollectionPersisterNestedStructure('pushAll_nested1'));
        $nested3->set->add(new CollectionPersisterNestedStructure('set_nested1'));
        $structure->set->add($nested1);
        $structure->setArray->add($nested2);
        $structure->pushAll->add($nested3);

        $this->dm->persist($structure);
        $this->dm->flush();
        $this->assertCount(
            1,
            $this->ql,
            'Insertion of embedded-many collections of one document by "set", "setArray" and "pushAll" strategies requires no additional queries'
        );

        $structure->set->remove(0);
        $structure->set->add(new CollectionPersisterNestedStructure('nested5'));
        $structure->setArray->get(0)->set->add(new CollectionPersisterNestedStructure('set_nested1'));
        $structure->pushAll->get(0)->set->clear();
        $structure->pushAll->add(new CollectionPersisterNestedStructure('nested5'));
        $structure->pushAll->remove(0);

        $this->ql->clear();
        $this->dm->persist($structure);
        $this->dm->flush();
        $this->assertCount(
            5,
            $this->ql,
            'Modification of embedded-many collections of one document by "set", "setArray" and "pushAll" strategies requires two queries'
        );

        $this->assertSame($structure, $this->dm->getRepository(get_class($structure))->findOneBy(['id' => $structure->id]));
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
    $this->comments = new ArrayCollection();
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
    $this->comments = new ArrayCollection();
    $this->comment = $comment;
    $this->by = $by;
  }
}

/** @ODM\Document(collection="structure_collection_persister_test") */
class CollectionPersisterStructure
{
    /** @ODM\Id */
    public $id;

    /** @ODM\EmbedMany(targetDocument=CollectionPersisterNestedStructure::class, strategy="addToSet") */
    public $addToSet;

    /** @ODM\EmbedMany(targetDocument=CollectionPersisterNestedStructure::class, strategy="addToSet") */
    public $addToSet2;

    /** @ODM\EmbedMany(targetDocument=CollectionPersisterNestedStructure::class, strategy="set") */
    public $set;

    /** @ODM\EmbedMany(targetDocument=CollectionPersisterNestedStructure::class, strategy="set") */
    public $set2;

    /** @ODM\EmbedMany(targetDocument=CollectionPersisterNestedStructure::class, strategy="setArray") */
    public $setArray;

    /** @ODM\EmbedMany(targetDocument=CollectionPersisterNestedStructure::class, strategy="setArray") */
    public $setArray2;

    /** @ODM\EmbedMany(targetDocument=CollectionPersisterNestedStructure::class, strategy="pushAll") */
    public $pushAll;

    /** @ODM\EmbedMany(targetDocument=CollectionPersisterNestedStructure::class, strategy="pushAll") */
    public $pushAll2;

    public function __construct()
    {
        $this->addToSet  = new ArrayCollection();
        $this->addToSet2 = new ArrayCollection();
        $this->set       = new ArrayCollection();
        $this->set2      = new ArrayCollection();
        $this->setArray  = new ArrayCollection();
        $this->setArray2 = new ArrayCollection();
        $this->pushAll   = new ArrayCollection();
        $this->pushAll2  = new ArrayCollection();
    }
}

/** @ODM\EmbeddedDocument */
class CollectionPersisterNestedStructure
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $field;

    /** @ODM\EmbedMany(targetDocument=CollectionPersisterNestedStructure::class, strategy="addToSet") */
    public $addToSet;

    /** @ODM\EmbedMany(targetDocument=CollectionPersisterNestedStructure::class, strategy="addToSet") */
    public $addToSet2;

    /** @ODM\EmbedMany(targetDocument=CollectionPersisterNestedStructure::class, strategy="set") */
    public $set;

    /** @ODM\EmbedMany(targetDocument=CollectionPersisterNestedStructure::class, strategy="set") */
    public $set2;

    /** @ODM\EmbedMany(targetDocument=CollectionPersisterNestedStructure::class, strategy="setArray") */
    public $setArray;

    /** @ODM\EmbedMany(targetDocument=CollectionPersisterNestedStructure::class, strategy="setArray") */
    public $setArray2;

    /** @ODM\EmbedMany(targetDocument=CollectionPersisterNestedStructure::class, strategy="pushAll") */
    public $pushAll;

    /** @ODM\EmbedMany(targetDocument=CollectionPersisterNestedStructure::class, strategy="pushAll") */
    public $pushAll2;

    public function __construct($field)
    {
        $this->field     = $field;
        $this->addToSet  = new ArrayCollection();
        $this->addToSet2 = new ArrayCollection();
        $this->set       = new ArrayCollection();
        $this->set2      = new ArrayCollection();
        $this->setArray  = new ArrayCollection();
        $this->setArray2 = new ArrayCollection();
        $this->pushAll   = new ArrayCollection();
        $this->pushAll2  = new ArrayCollection();
    }
}
