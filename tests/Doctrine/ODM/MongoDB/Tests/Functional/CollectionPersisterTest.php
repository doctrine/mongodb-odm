<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\APM\CommandLogger;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use Doctrine\ODM\MongoDB\Persisters\CollectionPersister;
use Doctrine\ODM\MongoDB\Persisters\PersistenceBuilder;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

use function get_class;

class CollectionPersisterTest extends BaseTest
{
    private CommandLogger $logger;

    public function setUp(): void
    {
        parent::setUp();

        $this->logger = new CommandLogger();
        $this->logger->register();
    }

    public function tearDown(): void
    {
        $this->logger->unregister();

        parent::tearDown();
    }

    public function testDeleteReferenceMany(): void
    {
        $persister = $this->getCollectionPersister();
        $user      = $this->getTestUser('jwage');
        self::assertInstanceOf(PersistentCollectionInterface::class, $user->categories);
        $persister->delete($user, [$user->categories], []);

        $user = $this->dm->getDocumentCollection(CollectionPersisterUser::class)->findOne(['username' => 'jwage']);
        self::assertArrayNotHasKey('categories', $user, 'Test that the categories field was deleted');
    }

    public function testDeleteEmbedMany(): void
    {
        $persister = $this->getCollectionPersister();
        $user      = $this->getTestUser('jwage');
        self::assertInstanceOf(PersistentCollectionInterface::class, $user->phonenumbers);
        $persister->delete($user, [$user->phonenumbers], []);

        $user = $this->dm->getDocumentCollection(CollectionPersisterUser::class)->findOne(['username' => 'jwage']);
        self::assertArrayNotHasKey('phonenumbers', $user, 'Test that the phonenumbers field was deleted');
    }

    public function testDeleteNestedEmbedMany(): void
    {
        $persister = $this->getCollectionPersister();
        $user      = $this->getTestUser('jwage');

        $this->logger->clear();
        $persister->delete(
            $user,
            [$user->categories[0]->children[0]->children, $user->categories[0]->children[1]->children],
            [],
        );
        self::assertCount(1, $this->logger, 'Deletion of several embedded-many collections of one document requires one query');

        $check = $this->dm->getDocumentCollection(CollectionPersisterUser::class)->findOne(['username' => 'jwage']);

        self::assertFalse(isset($check['categories']['0']['children'][0]['children']));
        self::assertFalse(isset($check['categories']['0']['children'][1]['children']));

        $this->logger->clear();
        $persister->delete(
            $user,
            [$user->categories[0]->children, $user->categories[1]->children],
            [],
        );
        self::assertCount(1, $this->logger, 'Deletion of several embedded-many collections of one document requires one query');

        $check = $this->dm->getDocumentCollection(CollectionPersisterUser::class)->findOne(['username' => 'jwage']);

        self::assertFalse(isset($check['categories'][0]['children']), 'Test that the nested children categories field was deleted');
        self::assertTrue(isset($check['categories'][0]), 'Test that the category with the children still exists');

        self::assertFalse(isset($check['categories'][1]['children']), 'Test that the nested children categories field was deleted');
        self::assertTrue(isset($check['categories'][1]), 'Test that the category with the children still exists');
    }

    public function testDeleteNestedEmbedManyAndNestedParent(): void
    {
        $persister = $this->getCollectionPersister();
        $user      = $this->getTestUser('jwage');

        $this->logger->clear();
        $persister->delete(
            $user,
            [$user->categories[0]->children[0]->children, $user->categories[0]->children[1]->children],
            [],
        );
        self::assertCount(1, $this->logger, 'Deletion of several embedded-many collections of one document requires one query');

        $check = $this->dm->getDocumentCollection(CollectionPersisterUser::class)->findOne(['username' => 'jwage']);

        self::assertFalse(isset($check['categories']['0']['children'][0]['children']));
        self::assertFalse(isset($check['categories']['0']['children'][1]['children']));

        $this->logger->clear();
        $firstCategoryChildren = $user->categories[0]->children;
        self::assertInstanceOf(PersistentCollectionInterface::class, $firstCategoryChildren);
        self::assertInstanceOf(PersistentCollectionInterface::class, $firstCategoryChildren[1]->children);
        self::assertInstanceOf(PersistentCollectionInterface::class, $user->categories);
        $persister->delete(
            $user,
            [$firstCategoryChildren, $firstCategoryChildren[1]->children, $user->categories],
            [],
        );
        self::assertCount(1, $this->logger, 'Deletion of several embedded-many collections of one document requires one query');

        $check = $this->dm->getDocumentCollection(CollectionPersisterUser::class)->findOne(['username' => 'jwage']);

        self::assertFalse(isset($check['categories']), 'Test that the nested categories field was deleted');
    }

    public function testDeleteRows(): void
    {
        $user = $this->getTestUser('jwage');

        unset($user->phonenumbers[0]);
        unset($user->phonenumbers[1]);

        unset($user->categories[0]->children[0]->children[0]);
        unset($user->categories[0]->children[0]->children[1]);

        unset($user->categories[0]->children[1]->children[0]);
        unset($user->categories[0]->children[1]->children[1]);

        $this->logger->clear();
        $this->dm->flush();
        self::assertCount(2, $this->logger, 'Modification of several embedded-many collections of one document requires two queries');

        $check = $this->dm->getDocumentCollection(CollectionPersisterUser::class)->findOne(['username' => 'jwage']);

        self::assertFalse(isset($check['phonenumbers'][0]));
        self::assertFalse(isset($check['phonenumbers'][1]));

        self::assertFalse(isset($check['categories'][0]['children'][0]['children'][0]));
        self::assertFalse(isset($check['categories'][0]['children'][0]['children'][1]));

        self::assertFalse(isset($check['categories'][0]['children'][1]['children'][0]));
        self::assertFalse(isset($check['categories'][0]['children'][1]['children'][1]));

        unset($user->categories[0]);
        unset($user->categories[1]);
        $this->logger->clear();
        $this->dm->flush();
        self::assertCount(2, $this->logger, 'Modification of embedded-many collection of one document requires two queries');

        $check = $this->dm->getDocumentCollection(CollectionPersisterUser::class)->findOne(['username' => 'jwage']);
        self::assertFalse(isset($check['categories'][0]));
        self::assertFalse(isset($check['categories'][1]));
    }

    public function testInsertRows(): void
    {
        $user                 = $this->getTestUser('jwage');
        $user->phonenumbers[] = new CollectionPersisterPhonenumber('6155139185');
        $user->phonenumbers[] = new CollectionPersisterPhonenumber('6155139185');
        $this->logger->clear();
        $this->dm->flush();
        self::assertCount(2, $this->logger, 'Modification of embedded-many collection of one document requires two queries');

        $check = $this->dm->getDocumentCollection(CollectionPersisterUser::class)->findOne(['username' => 'jwage']);
        self::assertCount(4, $check['phonenumbers']);
        self::assertEquals((string) $check['phonenumbers'][2]['$id'], $user->phonenumbers[2]->id);
        self::assertEquals((string) $check['phonenumbers'][3]['$id'], $user->phonenumbers[3]->id);

        $user->categories[] = new CollectionPersisterCategory('Test');
        $user->categories[] = new CollectionPersisterCategory('Test');
        $this->logger->clear();
        $this->dm->flush();
        self::assertCount(
            1,
            $this->logger,
            'Modification of embedded-many collection of one document requires one query since no existing collection elements was removed',
        );

        $check = $this->dm->getDocumentCollection(CollectionPersisterUser::class)->findOne(['username' => 'jwage']);
        self::assertCount(4, $check['categories']);

        $user->categories[3]->children[0]              = new CollectionPersisterCategory('Test');
        $user->categories[3]->children[1]              = new CollectionPersisterCategory('Test');
        $user->categories[3]->children[1]->children[0] = new CollectionPersisterCategory('Test');
        $user->categories[3]->children[1]->children[1] = new CollectionPersisterCategory('Test');
        $this->logger->clear();
        $this->dm->flush();
        self::assertCount(
            1,
            $this->logger,
            'Modification of embedded-many collection of one document requires one query since no existing collection elements was removed',
        );

        $check = $this->dm->getDocumentCollection(CollectionPersisterUser::class)->findOne(['username' => 'jwage']);
        self::assertCount(2, $check['categories'][3]['children']);
        self::assertCount(2, $check['categories'][3]['children']['1']['children']);
    }

    private function getTestUser(string $username): CollectionPersisterUser
    {
        $user                  = new CollectionPersisterUser();
        $user->username        = $username;
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
        $this->dm->flush();

        return $user;
    }

    private function getCollectionPersister(): CollectionPersister
    {
        $uow = $this->dm->getUnitOfWork();
        $pb  = new PersistenceBuilder($this->dm, $uow);

        return new CollectionPersister($this->dm, $pb, $uow);
    }

    public function testNestedEmbedManySetStrategy(): void
    {
        $post      = new CollectionPersisterPost('postA');
        $commentA  = new CollectionPersisterComment('commentA', 'userA');
        $commentAA = new CollectionPersisterComment('commentAA', 'userB');
        $commentAB = new CollectionPersisterComment('commentAB', 'userC');

        $post->comments->set('a', $commentA);
        $commentA->comments->set('a', $commentAA);
        $commentA->comments->set('b', $commentAB);

        $this->dm->persist($post);
        $this->logger->clear();
        $this->dm->flush();
        self::assertCount(
            1,
            $this->logger,
            'Insertion of embedded-many collection of one document requires no additional queries',
        );

        $doc = $this->dm->getDocumentCollection(CollectionPersisterPost::class)->findOne(['post' => 'postA']);

        self::assertCount(1, $doc['comments']);
        self::assertEquals($commentA->comment, $doc['comments']['a']['comment']);
        self::assertEquals($commentA->by, $doc['comments']['a']['by']);
        self::assertCount(2, $doc['comments']['a']['comments']);
        self::assertEquals($commentAA->comment, $doc['comments']['a']['comments']['a']['comment']);
        self::assertEquals($commentAA->by, $doc['comments']['a']['comments']['a']['by']);
        self::assertEquals($commentAB->comment, $doc['comments']['a']['comments']['b']['comment']);
        self::assertEquals($commentAB->by, $doc['comments']['a']['comments']['b']['by']);

        // Add a new top-level comment with a nested comment
        $commentB  = new CollectionPersisterComment('commentB', 'userD');
        $commentBA = new CollectionPersisterComment('commentBA', 'userE');

        $post->comments->set('b', $commentB);
        $commentB->comments->set('a', $commentBA);

        $this->dm->persist($post);
        $this->logger->clear();
        $this->dm->flush();
        self::assertCount(
            1,
            $this->logger,
            'Modification of embedded-many collection of one document requires one query since no existing collection elements was removed',
        );

        $doc = $this->dm->getDocumentCollection(CollectionPersisterPost::class)->findOne(['post' => 'postA']);

        self::assertCount(2, $doc['comments']);
        self::assertEquals($commentA->comment, $doc['comments']['a']['comment']);
        self::assertEquals($commentB->comment, $doc['comments']['b']['comment']);
        self::assertEquals($commentB->by, $doc['comments']['b']['by']);
        self::assertCount(2, $doc['comments']['a']['comments']);
        self::assertEquals($commentAA->comment, $doc['comments']['a']['comments']['a']['comment']);
        self::assertEquals($commentAB->comment, $doc['comments']['a']['comments']['b']['comment']);
        self::assertCount(1, $doc['comments']['b']['comments']);
        self::assertEquals($commentBA->comment, $doc['comments']['b']['comments']['a']['comment']);
        self::assertEquals($commentBA->by, $doc['comments']['b']['comments']['a']['by']);

        // Update two comments
        $commentB->comment  = 'commentB-modified';
        $commentAB->comment = 'commentAB-modified';

        $this->dm->persist($post);
        $this->logger->clear();
        $this->dm->flush();
        self::assertCount(
            1,
            $this->logger,
            'Modification of embedded-many collection of one document requires one query since no existing collection elements was removed',
        );

        $doc = $this->dm->getDocumentCollection(CollectionPersisterPost::class)->findOne(['post' => 'postA']);

        self::assertCount(2, $doc['comments']);
        self::assertEquals($commentA->comment, $doc['comments']['a']['comment']);
        self::assertEquals($commentB->comment, $doc['comments']['b']['comment']);
        self::assertCount(2, $doc['comments']['a']['comments']);
        self::assertEquals($commentAA->comment, $doc['comments']['a']['comments']['a']['comment']);
        self::assertEquals($commentAB->comment, $doc['comments']['a']['comments']['b']['comment']);
        self::assertCount(1, $doc['comments']['b']['comments']);
        self::assertEquals($commentBA->comment, $doc['comments']['b']['comments']['a']['comment']);

        // Delete a comment
        unset($post->comments['b']);

        $this->dm->persist($post);
        $this->logger->clear();
        $this->dm->flush();
        self::assertCount(
            1,
            $this->logger,
            'Modification of embedded-many collection of one document requires one query since collection, from which element was removed, have "set" store strategy.',
        );

        $doc = $this->dm->getDocumentCollection(CollectionPersisterPost::class)->findOne(['post' => 'postA']);

        self::assertCount(1, $doc['comments']);
        self::assertEquals($commentA->comment, $doc['comments']['a']['comment']);
        self::assertCount(2, $doc['comments']['a']['comments']);
        self::assertEquals($commentAA->comment, $doc['comments']['a']['comments']['a']['comment']);
        self::assertEquals($commentAB->comment, $doc['comments']['a']['comments']['b']['comment']);
        self::assertFalse(isset($doc['comments']['b']));
    }

    public function testFindBySetStrategyKey(): void
    {
        $post      = new CollectionPersisterPost('postA');
        $commentA  = new CollectionPersisterComment('commentA', 'userA');
        $commentAB = new CollectionPersisterComment('commentAA', 'userB');

        $post->comments['a']     = $commentA;
        $commentA->comments['b'] = $commentAB;

        $this->dm->persist($post);
        $this->dm->flush();

        self::assertSame($post, $this->dm->getRepository(get_class($post))->findOneBy(['comments.a.by' => 'userA']));
        self::assertSame($post, $this->dm->getRepository(get_class($post))->findOneBy(['comments.a.comments.b.by' => 'userB']));
    }

    public function testPersistSeveralNestedEmbedManySetStrategy(): void
    {
        $structure = new CollectionPersisterStructure();
        $structure->set->add(new CollectionPersisterNestedStructure('nested1'));
        $structure->set->add(new CollectionPersisterNestedStructure('nested2'));
        $structure->set2->add(new CollectionPersisterNestedStructure('nested3'));
        $structure->set2->add(new CollectionPersisterNestedStructure('nested4'));

        $this->dm->persist($structure);
        $this->dm->flush();
        self::assertCount(
            1,
            $this->logger,
            'Insertion of embedded-many collections of one document by "set" strategy requires no additional queries',
        );

        $structure->set->clear();
        $structure->set->add(new CollectionPersisterNestedStructure('nested5'));
        $structure->set2->add(new CollectionPersisterNestedStructure('nested6'));

        $this->logger->clear();
        $this->dm->flush();
        self::assertCount(
            1,
            $this->logger,
            'Modification of embedded-many collections of one document by "set" strategy requires one query',
        );

        self::assertSame($structure, $this->dm->getRepository(get_class($structure))->findOneBy(['id' => $structure->id]));
    }

    public function testPersistSeveralNestedEmbedManySetArrayStrategy(): void
    {
        $structure = new CollectionPersisterStructure();
        $structure->setArray->add(new CollectionPersisterNestedStructure('nested1'));
        $structure->setArray->add(new CollectionPersisterNestedStructure('nested2'));
        $structure->setArray2->add(new CollectionPersisterNestedStructure('nested3'));
        $structure->setArray2->add(new CollectionPersisterNestedStructure('nested4'));

        $this->dm->persist($structure);
        $this->dm->flush();
        self::assertCount(
            1,
            $this->logger,
            'Insertion of embedded-many collections of one document by "setArray" strategy requires no additional queries',
        );

        $structure->setArray->clear();
        $structure->setArray->add(new CollectionPersisterNestedStructure('nested5'));
        $structure->setArray2->add(new CollectionPersisterNestedStructure('nested6'));

        $this->logger->clear();
        $this->dm->flush();
        self::assertCount(
            1,
            $this->logger,
            'Modification of embedded-many collections of one document by "setArray" strategy requires one query',
        );

        self::assertSame($structure, $this->dm->getRepository(get_class($structure))->findOneBy(['id' => $structure->id]));
    }

    public function testPersistSeveralNestedEmbedManyAddToSetStrategy(): void
    {
        $structure = new CollectionPersisterStructure();
        $structure->addToSet->add(new CollectionPersisterNestedStructure('nested1'));
        $structure->addToSet->add(new CollectionPersisterNestedStructure('nested2'));
        $structure->addToSet2->add(new CollectionPersisterNestedStructure('nested3'));
        $structure->addToSet2->add(new CollectionPersisterNestedStructure('nested4'));

        $this->dm->persist($structure);
        $this->dm->flush();
        self::assertCount(
            2,
            $this->logger,
            'Insertion of embedded-many collections of one document by "addToSet" strategy requires one additional query',
        );

        $structure->addToSet->clear();
        $structure->addToSet->add(new CollectionPersisterNestedStructure('nested5'));
        $structure->addToSet2->add(new CollectionPersisterNestedStructure('nested6'));

        $this->logger->clear();
        $this->dm->flush();
        self::assertCount(
            2,
            $this->logger,
            'Modification of embedded-many collections of one document by "addToSet" strategy requires two queries',
        );

        self::assertSame($structure, $this->dm->getRepository(get_class($structure))->findOneBy(['id' => $structure->id]));
    }

    public function testPersistSeveralNestedEmbedManyPushAllStrategy(): void
    {
        $structure = new CollectionPersisterStructure();
        $structure->pushAll->add(new CollectionPersisterNestedStructure('nested1'));
        $structure->pushAll->add(new CollectionPersisterNestedStructure('nested2'));
        $structure->pushAll2->add(new CollectionPersisterNestedStructure('nested3'));
        $structure->pushAll2->add(new CollectionPersisterNestedStructure('nested4'));

        $this->dm->persist($structure);
        $this->dm->flush();
        self::assertCount(
            1,
            $this->logger,
            'Insertion of embedded-many collections of one document by "pushAll" strategy requires no additional queries',
        );

        $structure->pushAll->add(new CollectionPersisterNestedStructure('nested5'));
        $structure->pushAll2->add(new CollectionPersisterNestedStructure('nested6'));

        $this->logger->clear();
        $this->dm->persist($structure);
        $this->dm->flush();
        self::assertCount(
            2,
            $this->logger,
            'Modification of embedded-many collections of one document by "pushAll" strategy requires two queries',
        );

        self::assertSame($structure, $this->dm->getRepository(get_class($structure))->findOneBy(['id' => $structure->id]));
    }

    public function testPersistSeveralNestedEmbedManyDifferentStrategies(): void
    {
        $structure = new CollectionPersisterStructure();
        $structure->set->add(new CollectionPersisterNestedStructure('nested1'));
        $structure->setArray->add(new CollectionPersisterNestedStructure('nested2'));
        $structure->pushAll->add(new CollectionPersisterNestedStructure('nested3'));

        $this->dm->persist($structure);
        $this->dm->flush();
        self::assertCount(
            1,
            $this->logger,
            'Insertion of embedded-many collections of one document by "set", "setArray" and "pushAll" strategies requires no additional queries',
        );

        $structure->set->remove(0);
        $structure->set->add(new CollectionPersisterNestedStructure('nested5'));
        $structure->pushAll->remove(0);
        $structure->setArray->add(new CollectionPersisterNestedStructure('nested6'));
        $structure->setArray->remove(0);
        $structure->pushAll->add(new CollectionPersisterNestedStructure('nested7'));

        $this->logger->clear();
        $this->dm->persist($structure);
        $this->dm->flush();
        self::assertCount(
            4,
            $this->logger,
            'Modification of embedded-many collections of one document by "set", "setArray" and "pushAll" strategies requires two queries',
        );

        self::assertSame($structure, $this->dm->getRepository(get_class($structure))->findOneBy(['id' => $structure->id]));
    }

    public function testPersistSeveralNestedEmbedManyDifferentStrategiesDeepNesting(): void
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
        self::assertCount(
            1,
            $this->logger,
            'Insertion of embedded-many collections of one document by "set", "setArray" and "pushAll" strategies requires no additional queries',
        );

        $structure->set->remove(0);
        $structure->set->add(new CollectionPersisterNestedStructure('nested5'));
        $structure->setArray->get(0)->set->add(new CollectionPersisterNestedStructure('set_nested1'));
        $structure->pushAll->get(0)->set->clear();
        $structure->pushAll->add(new CollectionPersisterNestedStructure('nested5'));
        $structure->pushAll->remove(0);

        $this->logger->clear();
        $this->dm->persist($structure);
        $this->dm->flush();
        self::assertCount(
            5,
            $this->logger,
            'Modification of embedded-many collections of one document by "set", "setArray" and "pushAll" strategies requires two queries',
        );

        self::assertSame($structure, $this->dm->getRepository(get_class($structure))->findOneBy(['id' => $structure->id]));
    }
}

/** @ODM\Document(collection="user_collection_persister_test") */
class CollectionPersisterUser
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $username;

    /**
     * @ODM\EmbedMany(targetDocument=CollectionPersisterCategory::class)
     *
     * @var Collection<int, CollectionPersisterCategory>|array<CollectionPersisterCategory>
     */
    public $categories = [];

    /**
     * @ODM\ReferenceMany(targetDocument=CollectionPersisterPhonenumber::class, cascade={"persist"})
     *
     * @var Collection<int, CollectionPersisterPhonenumber>|array<CollectionPersisterPhonenumber>
     */
    public $phonenumbers = [];
}

/** @ODM\EmbeddedDocument */
class CollectionPersisterCategory
{
    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    public $name;

    /**
     * @ODM\EmbedMany(targetDocument=CollectionPersisterCategory::class)
     *
     * @var Collection<int, CollectionPersisterCategory>|array<CollectionPersisterCategory>
     */
    public $children = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}

/** @ODM\Document(collection="phonenumber_collection_persister_test") */
class CollectionPersisterPhonenumber
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    public $phonenumber;

    public function __construct(string $phonenumber)
    {
        $this->phonenumber = $phonenumber;
    }
}

/** @ODM\Document(collection="post_collection_persister_test") */
class CollectionPersisterPost
{
  /**
   * @ODM\Id
   *
   * @var string|null
   */
    public $id;

  /**
   * @ODM\Field(type="string")
   *
   * @var string
   */
    public $post;

  /**
   * @ODM\EmbedMany(targetDocument=CollectionPersisterComment::class, strategy="set")
   *
   * @var Collection<array-key, CollectionPersisterComment>|array<CollectionPersisterComment>
   */
    public $comments = [];

    public function __construct(string $post)
    {
        $this->comments = new ArrayCollection();
        $this->post     = $post;
    }
}

/** @ODM\EmbeddedDocument */
class CollectionPersisterComment
{
  /**
   * @ODM\Id
   *
   * @var string|null
   */
    public $id;

  /**
   * @ODM\Field(type="string")
   *
   * @var string
   */
    public $comment;

  /**
   * @ODM\Field(type="string")
   *
   * @var string
   */
    public $by;

  /**
   * @ODM\EmbedMany(targetDocument=CollectionPersisterComment::class, strategy="set")
   *
   * @var Collection<array-key, CollectionPersisterComment>|array<CollectionPersisterComment>
   */
    public $comments = [];

    public function __construct(string $comment, string $by)
    {
        $this->comments = new ArrayCollection();
        $this->comment  = $comment;
        $this->by       = $by;
    }
}

/** @ODM\Document(collection="structure_collection_persister_test") */
class CollectionPersisterStructure
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\EmbedMany(targetDocument=CollectionPersisterNestedStructure::class, strategy="addToSet")
     *
     * @var Collection<int, CollectionPersisterNestedStructure>
     */
    public $addToSet;

    /**
     * @ODM\EmbedMany(targetDocument=CollectionPersisterNestedStructure::class, strategy="addToSet")
     *
     * @var Collection<int, CollectionPersisterNestedStructure>
     */
    public $addToSet2;

    /**
     * @ODM\EmbedMany(targetDocument=CollectionPersisterNestedStructure::class, strategy="set")
     *
     * @var Collection<int, CollectionPersisterNestedStructure>
     */
    public $set;

    /**
     * @ODM\EmbedMany(targetDocument=CollectionPersisterNestedStructure::class, strategy="set")
     *
     * @var Collection<int, CollectionPersisterNestedStructure>
     */
    public $set2;

    /**
     * @ODM\EmbedMany(targetDocument=CollectionPersisterNestedStructure::class, strategy="setArray")
     *
     * @var Collection<int, CollectionPersisterNestedStructure>
     */
    public $setArray;

    /**
     * @ODM\EmbedMany(targetDocument=CollectionPersisterNestedStructure::class, strategy="setArray")
     *
     * @var Collection<int, CollectionPersisterNestedStructure>
     */
    public $setArray2;

    /**
     * @ODM\EmbedMany(targetDocument=CollectionPersisterNestedStructure::class, strategy="pushAll")
     *
     * @var Collection<int, CollectionPersisterNestedStructure>
     */
    public $pushAll;

    /**
     * @ODM\EmbedMany(targetDocument=CollectionPersisterNestedStructure::class, strategy="pushAll")
     *
     * @var Collection<int, CollectionPersisterNestedStructure>
     */
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
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    public $field;

    /**
     * @ODM\EmbedMany(targetDocument=CollectionPersisterNestedStructure::class, strategy="addToSet")
     *
     * @var Collection<int, CollectionPersisterNestedStructure>
     */
    public $addToSet;

    /**
     * @ODM\EmbedMany(targetDocument=CollectionPersisterNestedStructure::class, strategy="addToSet")
     *
     * @var Collection<int, CollectionPersisterNestedStructure>
     */
    public $addToSet2;

    /**
     * @ODM\EmbedMany(targetDocument=CollectionPersisterNestedStructure::class, strategy="set")
     *
     * @var Collection<int, CollectionPersisterNestedStructure>
     */
    public $set;

    /**
     * @ODM\EmbedMany(targetDocument=CollectionPersisterNestedStructure::class, strategy="set")
     *
     * @var Collection<int, CollectionPersisterNestedStructure>
     */
    public $set2;

    /**
     * @ODM\EmbedMany(targetDocument=CollectionPersisterNestedStructure::class, strategy="setArray")
     *
     * @var Collection<int, CollectionPersisterNestedStructure>
     */
    public $setArray;

    /**
     * @ODM\EmbedMany(targetDocument=CollectionPersisterNestedStructure::class, strategy="setArray")
     *
     * @var Collection<int, CollectionPersisterNestedStructure>
     */
    public $setArray2;

    /**
     * @ODM\EmbedMany(targetDocument=CollectionPersisterNestedStructure::class, strategy="pushAll")
     *
     * @var Collection<int, CollectionPersisterNestedStructure>
     */
    public $pushAll;

    /**
     * @ODM\EmbedMany(targetDocument=CollectionPersisterNestedStructure::class, strategy="pushAll")
     *
     * @var Collection<int, CollectionPersisterNestedStructure>
     */
    public $pushAll2;

    public function __construct(string $field)
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
