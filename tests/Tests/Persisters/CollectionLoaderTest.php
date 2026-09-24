<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Persisters;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\PersistentCollection;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionException;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use Doctrine\ODM\MongoDB\Persisters\CollectionLoader;
use Doctrine\ODM\MongoDB\Query\CriteriaMerger;
use Doctrine\ODM\MongoDB\Query\CriteriaPreparer;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\BlogPost;
use Documents\Comment;
use Documents\Group;
use Documents\Message;
use Documents\Phonenumber;
use Documents\Strategy;
use Documents\User;
use ReflectionProperty;

use function array_key_first;
use function array_map;
use function assert;
use function sort;

/**
 * Unit-style coverage for CollectionLoader, constructed directly rather than
 * through DocumentPersister/UnitOfWork::loadCollection(). This complements
 * (rather than replaces) the existing black-box coverage in
 * Functional\DocumentPersisterTest and Functional\ReferencesTest, which
 * exercise collection loading indirectly via the public lazy-loading API.
 */
class CollectionLoaderTest extends BaseTestCase
{
    /** @phpstan-param class-string $ownerClass */
    private function createLoader(string $ownerClass): CollectionLoader
    {
        $class = $this->dm->getClassMetadata($ownerClass);
        $cm    = new CriteriaMerger();

        return new CollectionLoader(
            $this->dm,
            $this->uow,
            $this->dm->getHydratorFactory(),
            new CriteriaPreparer($this->dm, $this->uow->getPersistenceBuilder(), $class, $cm),
            $cm,
        );
    }

    /**
     * Builds a PersistentCollection with a real field mapping but no owner,
     * to exercise the "owner required to load collection" guards without
     * needing a document that was never actually persisted incompletely.
     *
     * @param array<mixed> $mongoData
     * @phpstan-param class-string $ownerClass
     *
     * @phpstan-return PersistentCollectionInterface<array-key, object>
     */
    private function collectionWithoutOwner(string $ownerClass, string $fieldName, array $mongoData = []): PersistentCollectionInterface
    {
        $mapping = $this->dm->getClassMetadata($ownerClass)->fieldMappings[$fieldName];

        $collection = new PersistentCollection(new ArrayCollection(), $this->dm, $this->uow);
        $collection->setMongoData($mongoData);

        $property = new ReflectionProperty($collection, 'mapping');
        $property->setValue($collection, $mapping);

        return $collection;
    }

    public function testLoadEmbedManyCollectionWithListStrategy(): void
    {
        $user = new User();
        $user->setUsername('alice');
        $user->addPhonenumber(new Phonenumber('555-0100'));
        $user->addPhonenumber(new Phonenumber('555-0101'));

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $reloaded   = $this->dm->find(User::class, $user->getId());
        $collection = $reloaded->getPhonenumbers();
        assert($collection instanceof PersistentCollectionInterface);
        self::assertFalse($collection->isInitialized());

        $this->createLoader(User::class)->loadCollection($collection);

        $numbers = array_map(static fn (Phonenumber $p) => $p->getPhonenumber(), $collection->unwrap()->toArray());
        self::assertSame(['555-0100', '555-0101'], $numbers);

        foreach ($collection->unwrap() as $phonenumber) {
            self::assertFalse($this->uow->isScheduledForInsert($phonenumber));
        }
    }

    public function testLoadEmbedManyCollectionWithHashStrategy(): void
    {
        $strategy             = new Strategy();
        $strategy->messages[] = new Message('first');
        $strategy->messages[] = new Message('second');

        $this->dm->persist($strategy);
        $this->dm->flush();
        $this->dm->clear();

        $reloaded   = $this->dm->find(Strategy::class, $strategy->id);
        $collection = $reloaded->messages;
        assert($collection instanceof PersistentCollectionInterface);
        self::assertFalse($collection->isInitialized());

        $this->createLoader(Strategy::class)->loadCollection($collection);

        $names = array_map(static fn (Message $m) => $m->getName(), $collection->unwrap()->toArray());
        sort($names);
        self::assertSame(['first', 'second'], $names);
    }

    public function testLoadEmbedManyCollectionIsNoopForEmptyMongoData(): void
    {
        $collection = $this->collectionWithoutOwner(User::class, 'phonenumbers', []);

        // Must not throw despite having no owner: the empty check short-circuits first.
        $this->createLoader(User::class)->loadCollection($collection);

        self::assertCount(0, $collection->unwrap());
    }

    public function testLoadEmbedManyCollectionThrowsWithoutOwner(): void
    {
        $collection = $this->collectionWithoutOwner(User::class, 'phonenumbers', [
            ['phonenumber' => '555-0100'],
        ]);

        $this->expectException(PersistentCollectionException::class);
        $this->createLoader(User::class)->loadCollection($collection);
    }

    public function testLoadReferenceManyCollectionOwningSideUnsorted(): void
    {
        $user = new User();
        $user->setUsername('bob');
        $user->addGroup(new Group('Group A'));
        $user->addGroup(new Group('Group B'));

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $reloaded   = $this->dm->find(User::class, $user->getId());
        $collection = $reloaded->getGroups();
        assert($collection instanceof PersistentCollectionInterface);
        self::assertFalse($collection->isInitialized());

        $this->createLoader(User::class)->loadCollection($collection);

        $names = array_map(static fn (Group $g) => $g->getName(), $collection->unwrap()->toArray());
        self::assertSame(['Group A', 'Group B'], $names);
    }

    public function testLoadReferenceManyCollectionOwningSideSorted(): void
    {
        $user = new User();
        $user->setUsername('carol');
        $user->addGroup(new Group('Group B'));
        $user->addGroup(new Group('Group A'));

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $reloaded   = $this->dm->find(User::class, $user->getId());
        $collection = $reloaded->getSortedAscGroups();
        assert($collection instanceof PersistentCollectionInterface);
        self::assertFalse($collection->isInitialized());

        $this->createLoader(User::class)->loadCollection($collection);

        $names = array_map(static fn (Group $g) => $g->getName(), $collection->unwrap()->toArray());
        self::assertSame(['Group A', 'Group B'], $names);
    }

    public function testLoadReferenceManyCollectionOwningSideThrowsWithoutOwner(): void
    {
        $collection = $this->collectionWithoutOwner(User::class, 'groups');

        $this->expectException(PersistentCollectionException::class);
        $this->createLoader(User::class)->loadCollection($collection);
    }

    public function testLoadReferenceManyCollectionInverseSide(): void
    {
        $blogPost = new BlogPost('Announcing 2.0');
        $blogPost->addComment(new Comment('First!', new DateTime('2024-01-01')));
        $blogPost->addComment(new Comment('Second', new DateTime('2024-01-02')));

        $this->dm->persist($blogPost);
        $this->dm->flush();
        $this->dm->clear();

        $reloaded   = $this->dm->find(BlogPost::class, $blogPost->id);
        $collection = $reloaded->comments;
        assert($collection instanceof PersistentCollectionInterface);
        self::assertFalse($collection->isInitialized());

        $this->createLoader(BlogPost::class)->loadCollection($collection);

        $texts = array_map(static fn (Comment $c) => $c->getText(), $collection->unwrap()->toArray());
        sort($texts);
        self::assertSame(['First!', 'Second'], $texts);
    }

    public function testCreateReferenceManyInverseSideQueryAppliesSortAndCriteria(): void
    {
        $blogPost = new BlogPost('Announcing 2.0');
        $blogPost->addComment(new Comment('Regular comment', new DateTime('2024-01-01'), false));
        $blogPost->addComment(new Comment('Admin comment', new DateTime('2024-01-02'), true));

        $this->dm->persist($blogPost);
        $this->dm->flush();
        $this->dm->clear();

        $reloaded   = $this->dm->find(BlogPost::class, $blogPost->id);
        $collection = $reloaded->adminComments;
        assert($collection instanceof PersistentCollectionInterface);

        $query = $this->createLoader(BlogPost::class)->createReferenceManyInverseSideQuery($collection);
        $debug = $query->debug();

        self::assertSame(['isByAdmin' => true], $debug['query']['$and'][1]);
        self::assertSame(['date' => -1], $debug['sort']);
    }

    public function testCreateReferenceManyInverseSideQueryAppliesLimit(): void
    {
        $blogPost = new BlogPost('Announcing 2.0');
        for ($i = 0; $i < 3; $i++) {
            $blogPost->addComment(new Comment('Comment ' . $i, new DateTime('2024-01-01')));
        }

        $this->dm->persist($blogPost);
        $this->dm->flush();
        $this->dm->clear();

        $reloaded   = $this->dm->find(BlogPost::class, $blogPost->id);
        $collection = $reloaded->last5Comments;
        assert($collection instanceof PersistentCollectionInterface);

        $query = $this->createLoader(BlogPost::class)->createReferenceManyInverseSideQuery($collection);
        $debug = $query->debug();

        self::assertSame(5, $debug['limit']);
        self::assertSame(['date' => -1], $debug['sort']);
    }

    public function testCreateReferenceManyInverseSideQueryThrowsWithoutOwner(): void
    {
        $collection = $this->collectionWithoutOwner(BlogPost::class, 'comments');

        $this->expectException(PersistentCollectionException::class);
        $this->createLoader(BlogPost::class)->createReferenceManyInverseSideQuery($collection);
    }

    public function testLoadReferenceManyWithRepositoryMethod(): void
    {
        $blogPost = new BlogPost('Announcing 2.0');
        $blogPost->addComment(new Comment('First!', new DateTime('2024-01-01')));
        $blogPost->addComment(new Comment('Second', new DateTime('2024-01-02')));

        $this->dm->persist($blogPost);
        $this->dm->flush();
        $this->dm->clear();

        $reloaded   = $this->dm->find(BlogPost::class, $blogPost->id);
        $collection = $reloaded->repoComments;
        assert($collection instanceof PersistentCollectionInterface);
        self::assertFalse($collection->isInitialized());

        $this->createLoader(BlogPost::class)->loadCollection($collection);

        self::assertCount(2, $collection->unwrap());
    }

    public function testCreateReferenceManyWithRepositoryMethodCursorPrimesConfiguredFields(): void
    {
        $author = new User();
        $author->setUsername('dave');
        $blogPost        = new BlogPost('Announcing 2.0');
        $comment         = new Comment('First!', new DateTime('2024-01-01'));
        $comment->author = $author;
        $blogPost->addComment($comment);

        $this->dm->persist($blogPost);
        $this->dm->flush();
        $this->dm->clear();

        $reloaded   = $this->dm->find(BlogPost::class, $blogPost->id);
        $collection = $reloaded->repoCommentsWithPrimer;
        assert($collection instanceof PersistentCollectionInterface);

        $cursor    = $this->createLoader(BlogPost::class)->createReferenceManyWithRepositoryMethodCursor($collection);
        $documents = $cursor->toArray();

        self::assertCount(1, $documents);
        $loadedComment = $documents[array_key_first($documents)];
        self::assertInstanceOf(Comment::class, $loadedComment);
        self::assertFalse($this->uow->isUninitializedObject($loadedComment->author));
    }
}
