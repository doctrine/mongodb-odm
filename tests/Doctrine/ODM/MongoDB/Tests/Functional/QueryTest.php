<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Iterator\Iterator;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\Article;
use Documents\CmsComment;
use Documents\Group;
use Documents\IndirectlyReferencedUser;
use Documents\Phonenumber;
use Documents\ReferenceUser;
use Documents\User;
use InvalidArgumentException;
use IteratorAggregate;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Regex;
use MongoDB\BSON\UTCDateTime;

use function array_values;
use function assert;
use function get_class;
use function iterator_to_array;
use function strtotime;

class QueryTest extends BaseTest
{
    private User $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = new User();
        $this->user->setUsername('boo');

        $this->dm->persist($this->user);
        $this->dm->flush();
    }

    public function testAddElemMatch(): void
    {
        $user = new User();
        $user->setUsername('boo');
        $phonenumber = new Phonenumber('6155139185');
        $user->addPhonenumber($phonenumber);

        $this->dm->persist($user);
        $this->dm->flush();

        $qb         = $this->dm->createQueryBuilder(User::class);
        $embeddedQb = $this->dm->createQueryBuilder(Phonenumber::class);

        $qb->field('phonenumbers')->elemMatch($embeddedQb->expr()->field('phonenumber')->equals('6155139185'));
        $query = $qb->getQuery();
        $user  = $query->getSingleResult();
        self::assertNotNull($user);
    }

    public function testAddElemMatchWithDeepFields(): void
    {
        $user1 = new User();
        $user1->setUsername('ben');

        $user2 = new User();
        $user2->setUsername('boo');
        $phonenumber = new Phonenumber('2125550123', $user1);
        $user2->addPhonenumber($phonenumber);

        $this->dm->persist($user1);
        $this->dm->persist($user2);
        $this->dm->flush();

        $qb         = $this->dm->createQueryBuilder(User::class);
        $embeddedQb = $this->dm->createQueryBuilder(Phonenumber::class);

        $qb->field('phonenumbers')->elemMatch($embeddedQb->expr()->field('lastCalledBy.$id')->equals(new ObjectId($user1->getId())));
        $query = $qb->getQuery();
        $user  = $query->getSingleResult();
        self::assertNotNull($user);
    }

    public function testAddNot(): void
    {
        $user = new User();
        $user->setUsername('boo');

        $this->dm->persist($user);
        $this->dm->flush();

        $qb = $this->dm->createQueryBuilder(User::class);
        $qb->field('username')->not($qb->expr()->in(['boo']));
        $query = $qb->getQuery();
        $user  = $query->getSingleResult();
        self::assertNull($user);

        $qb->field('username')->not($qb->expr()->in(['1boo']));
        $query = $qb->getQuery();
        $user  = $query->getSingleResult();
        self::assertNotNull($user);
    }

    public function testNotAllowsRegex(): void
    {
        $user = new User();
        $user->setUsername('boo');

        $this->dm->persist($user);
        $this->dm->flush();

        $qb = $this->dm->createQueryBuilder(User::class);
        $qb->field('username')->not(new Regex('Boo', 'i'));
        $query = $qb->getQuery();
        $user  = $query->getSingleResult();
        self::assertNull($user);

        $qb = $this->dm->createQueryBuilder(User::class);
        $qb->field('username')->not(new Regex('Boo'));
        $query = $qb->getQuery();
        $user  = $query->getSingleResult();
        self::assertNotNull($user);
    }

    public function testDistinct(): void
    {
        $user = new User();
        $user->setUsername('distinct_test');
        $user->setCount(1);
        $this->dm->persist($user);

        $user = new User();
        $user->setUsername('distinct_test');
        $user->setCount(1);
        $this->dm->persist($user);

        $user = new User();
        $user->setUsername('distinct_test');
        $user->setCount(2);
        $this->dm->persist($user);

        $user = new User();
        $user->setUsername('distinct_test');
        $user->setCount(3);
        $this->dm->persist($user);
        $this->dm->flush();

        $qb      = $this->dm->createQueryBuilder(User::class)
            ->distinct('count')
            ->field('username')->equals('distinct_test');
        $q       = $qb->getQuery();
        $results = $q->execute();
        self::assertEquals([1, 2, 3], $results);

        $results = $this->dm->createQueryBuilder(User::class)
            ->distinct('count')
            ->field('username')->equals('distinct_test')
            ->getQuery()
            ->execute();
        self::assertEquals([1, 2, 3], $results);
    }

    public function testDistinctWithDifferentDbName(): void
    {
        $c1           = new CmsComment();
        $c1->authorIp = '127.0.0.1';
        $c2           = new CmsComment();
        $c3           = new CmsComment();
        $c2->authorIp = $c3->authorIp = '192.168.0.1';
        $this->dm->persist($c1);
        $this->dm->persist($c2);
        $this->dm->persist($c3);
        $this->dm->flush();
        $this->dm->clear();

        $results = $this->dm->createQueryBuilder(get_class($c1))
            ->distinct('authorIp')
            ->getQuery()
            ->execute();
        self::assertEquals(['127.0.0.1', '192.168.0.1'], $results);
    }

    public function testFindQuery(): void
    {
        $qb    = $this->dm->createQueryBuilder(User::class)
            ->where("function() { return this.username == 'boo' }");
        $query = $qb->getQuery();
        $user  = $query->getSingleResult();

        self::assertInstanceOf(User::class, $user);
        self::assertEquals('boo', $user->getUsername());
    }

    public function testUpdateQuery(): void
    {
        $qb     = $this->dm->createQueryBuilder(User::class)
            ->updateOne()
            ->field('username')
            ->set('crap')
            ->equals('boo');
        $query  = $qb->getQuery();
        $result = $query->execute();

        $this->dm->refresh($this->user);
        self::assertEquals('crap', $this->user->getUsername());
    }

    public function testUpsertUpdateQuery(): void
    {
        $qb     = $this->dm->createQueryBuilder(User::class)
            ->updateOne()
            ->upsert(true)
            ->field('username')
            ->set('crap')
            ->equals('foo');
        $query  = $qb->getQuery();
        $result = $query->execute();

        $qb    = $this->dm->createQueryBuilder(User::class)
            ->find()
            ->field('username')->equals('crap');
        $query = $qb->getQuery();
        $user  = $query->getSingleResult();
        self::assertNotNull($user);
    }

    public function testMultipleUpdateQuery(): void
    {
        $user = new User();
        $user->setUsername('multiple_test');
        $user->setCount(1);
        $this->dm->persist($user);

        $user = new User();
        $user->setUsername('multiple_test');
        $user->setCount(1);
        $this->dm->persist($user);

        $user = new User();
        $user->setUsername('multiple_test');
        $user->setCount(2);
        $this->dm->persist($user);

        $user = new User();
        $user->setUsername('multiple_test');
        $user->setCount(3);
        $this->dm->persist($user);
        $this->dm->flush();

        $qb      = $this->dm->createQueryBuilder(User::class)
            ->updateMany()
            ->field('username')->equals('multiple_test')
            ->field('username')->set('foo');
        $q       = $qb->getQuery();
        $results = $q->execute();

        $qb     = $this->dm->createQueryBuilder(User::class)
            ->find()
            ->field('username')->equals('foo');
        $q      = $qb->getQuery();
        $result = $q->execute();
        self::assertInstanceOf(Iterator::class, $result);
        $users = array_values($result->toArray());

        self::assertCount(4, $users);
    }

    public function testRemoveQuery(): void
    {
        $this->dm->remove($this->user);

        $this->expectException(InvalidArgumentException::class);
        // should invoke exception because $this->user doesn't exist anymore
        $this->dm->refresh($this->user);
    }

    public function testIncUpdateQuery(): void
    {
        $qb    = $this->dm->createQueryBuilder(User::class)
            ->updateOne()
            ->field('hits')->inc(5)
            ->field('username')->equals('boo');
        $query = $qb->getQuery();
        $query->execute();
        $query->execute();

        $qb->find(User::class)
            ->hydrate(false);
        $query = $qb->getQuery();
        $user  = $query->getSingleResult();
        self::assertEquals(10, $user['hits']);
    }

    public function testUnsetFieldUpdateQuery(): void
    {
        $qb     = $this->dm->createQueryBuilder(User::class)
            ->updateOne()
            ->field('hits')->unsetField()
            ->field('username')->equals('boo');
        $query  = $qb->getQuery();
        $result = $query->execute();

        $qb->find(User::class)
            ->hydrate(false);
        $query = $qb->getQuery();
        $user  = $query->getSingleResult();
        self::assertArrayNotHasKey('hits', $user);
    }

    public function testUnsetField(): void
    {
        $qb    = $this->dm->createQueryBuilder()
            ->updateOne(User::class)
            ->field('nullTest')
            ->type('null')
            ->unsetField();
        $query = $qb->getQuery();
        $query->execute();

        $qb    = $this->dm->createQueryBuilder(User::class)
            ->field('nullTest')->type('null');
        $query = $qb->getQuery();
        $user  = $query->getSingleResult();
        self::assertNull($user);
    }

    public function testDateRange(): void
    {
        $article1 = new Article();
        $article1->setTitle('test');
        $article1->setBody('test');
        $article1->setCreatedAt('1985-09-01 00:00:00');

        $article2 = new Article();
        $article2->setTitle('test');
        $article2->setBody('test');
        $article2->setCreatedAt('1985-09-02 00:00:00');

        $article3 = new Article();
        $article3->setTitle('test');
        $article3->setBody('test');
        $article3->setCreatedAt('1985-09-03 00:00:00');

        $article4 = new Article();
        $article4->setTitle('test');
        $article4->setBody('test');
        $article4->setCreatedAt('1985-09-04 00:00:00');

        $this->dm->persist($article1);
        $this->dm->persist($article2);
        $this->dm->persist($article3);
        $this->dm->persist($article4);

        $this->dm->flush();
        $this->dm->clear();

        $qb = $this->dm->createQueryBuilder(Article::class);
        $qb->field('createdAt')->range(
            new UTCDateTime(strtotime('1985-09-01 01:00:00') * 1000),
            new UTCDateTime(strtotime('1985-09-04') * 1000),
        );
        $query  = $qb->getQuery();
        $result = $query->execute();
        self::assertInstanceOf(Iterator::class, $result);
        $articles = array_values($result->toArray());
        self::assertCount(2, $articles);
        self::assertEquals('1985-09-02', $articles[0]->getCreatedAt()->format('Y-m-d'));
        self::assertEquals('1985-09-03', $articles[1]->getCreatedAt()->format('Y-m-d'));
    }

    public function testQueryIsIterable(): void
    {
        $article = new Article();
        $article->setTitle('test');
        $this->dm->persist($article);
        $this->dm->flush();

        $qb    = $this->dm->createQueryBuilder(Article::class);
        $query = $qb->getQuery();
        self::assertInstanceOf(IteratorAggregate::class, $query);
        foreach ($query as $article) {
            self::assertEquals(Article::class, get_class($article));
        }
    }

    public function testQueryReferences(): void
    {
        $group = new Group('Test Group');

        $user = new User();
        $user->setUsername('cool');
        $user->addGroup($group);

        $this->dm->persist($user);
        $this->dm->flush();

        $qb    = $this->dm->createQueryBuilder(User::class)
            ->field('groups')->references($group);
        $query = $qb->getQuery();
        $user2 = $query->getSingleResult();
        self::assertSame($user, $user2);
    }

    public function testNestedQueryReference(): void
    {
        $referencedUser = new User();
        $referencedUser->setUsername('boo');
        $phonenumber = new Phonenumber('6155139185');
        $referencedUser->addPhonenumber($phonenumber);

        $indirectlyReferencedUser       = new IndirectlyReferencedUser();
        $indirectlyReferencedUser->user = $referencedUser;

        $user                              = new ReferenceUser();
        $user->indirectlyReferencedUsers[] = $indirectlyReferencedUser;

        $this->dm->persist($referencedUser);
        $this->dm->persist($user);
        $this->dm->flush();

        $qb = $this->dm->createQueryBuilder(ReferenceUser::class);

        $referencedUsersQuery = $qb
            ->field('indirectlyReferencedUsers.user.id')->equals(new ObjectId($referencedUser->getId()))
            ->getQuery();

        $referencedUsersIterator = $referencedUsersQuery->execute();

        assert($referencedUsersIterator instanceof Iterator);

        $referencedUsers = iterator_to_array($referencedUsersIterator, false);

        self::assertCount(1, $referencedUsers);
        self::assertSame($user, $referencedUsers[0]);
    }

    public function testQueryWhereIn(): void
    {
        $qb      = $this->dm->createQueryBuilder(User::class);
        $choices = ['a', 'b'];
        $qb->field('username')->in($choices);
        $expected = [
            'username' => ['$in' => $choices],
        ];
        self::assertSame($expected, $qb->getQueryArray());
    }

    public function testQueryWhereInReferenceId(): void
    {
        $qb      = $this->dm->createQueryBuilder(User::class);
        $choices = [new ObjectId(), new ObjectId()];
        $qb->field('account.$id')->in($choices);
        $expected = [
            'account.$id' => ['$in' => $choices],
        ];
        self::assertSame($expected, $qb->getQueryArray());
        self::assertSame($expected, $qb->getQuery()->debug('query'));
    }

    public function testQueryWhereOneValueOfCollection(): void
    {
        $qb = $this->dm->createQueryBuilder(Article::class);
        $qb->field('tags')->equals('pet');
        $expected = ['tags' => 'pet'];
        self::assertSame($expected, $qb->getQueryArray());
        self::assertSame($expected, $qb->getQuery()->debug('query'));
    }

    /** search for articles where tags exactly equal [pet, blue] */
    public function testQueryWhereAllValuesOfCollection(): void
    {
        $qb = $this->dm->createQueryBuilder(Article::class);
        $qb->field('tags')->equals(['pet', 'blue']);
        $expected = [
            'tags' => ['pet', 'blue'],
        ];
        self::assertSame($expected, $qb->getQueryArray());
        self::assertSame($expected, $qb->getQuery()->debug('query'));
    }

    public function testPopFirst(): void
    {
        $article = new Article();
        $article->setTitle('test');
        $article->setBody('test');
        $article->setCreatedAt('1985-09-01 00:00:00');
        $article->addTag(1);
        $article->addTag(2);
        $article->addTag(3);

        $this->dm->persist($article);
        $this->dm->flush();

        $this->dm->createQueryBuilder(Article::class)
            ->updateOne()
            ->field('id')
            ->equals($article->getId())
            ->field('tags')
            ->popFirst()
            ->getQuery()
            ->execute();

        $this->dm->refresh($article);
        self::assertSame([2, 3], $article->getTags());
    }

    public function testPopLast(): void
    {
        $article = new Article();
        $article->setTitle('test');
        $article->setBody('test');
        $article->setCreatedAt('1985-09-01 00:00:00');
        $article->addTag(1);
        $article->addTag(2);
        $article->addTag(3);

        $this->dm->persist($article);
        $this->dm->flush();

        $this->dm->createQueryBuilder(Article::class)
            ->updateOne()
            ->field('id')
            ->equals($article->getId())
            ->field('tags')
            ->popLast()
            ->getQuery()
            ->execute();

        $this->dm->refresh($article);
        self::assertSame([1, 2], $article->getTags());
    }

    /**
     * Checks that the query class runs a ReplaceOne operation internally
     *
     * @doesNotPerformAssertions
     */
    public function testReplaceDocumentRunsReplaceOperation(): void
    {
        $this->dm->createQueryBuilder(Article::class)
            ->updateOne()
            ->field('id')
            ->equals('foo')
            ->setNewObj(['value' => 'bar'])
            ->getQuery()
            ->execute();
    }

    /**
     * Checks that the query class runs a ReplaceOne operation internally
     */
    public function testReplaceMultipleCausesException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Combining the "multiple" option without using an update operator as first operation in a query is not supported.');

        $this->dm->createQueryBuilder(Article::class)
            ->updateMany()
            ->field('id')
            ->equals('foo')
            ->setNewObj(['value' => 'bar'])
            ->getQuery()
            ->execute();
    }

    /** @doesNotPerformAssertions */
    public function testFindAndReplaceDocumentRunsFindAndReplaceOperation(): void
    {
        $this->dm->createQueryBuilder(Article::class)
            ->findAndUpdate()
            ->field('id')
            ->equals('foo')
            ->setNewObj(['value' => 'bar'])
            ->getQuery()
            ->execute();
    }
}
