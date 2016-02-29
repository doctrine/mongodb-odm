<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Documents\Article;
use Documents\Account;
use Documents\Address;
use Documents\CmsComment;
use Documents\Phonenumber;
use Documents\User;

class QueryTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function setUp()
    {
        parent::setUp();

        $this->user = new User();
        $this->user->setUsername('boo');

        $this->dm->persist($this->user);
        $this->dm->flush();
    }

    public function testAddElemMatch()
    {
        $user = new User();
        $user->setUsername('boo');
        $phonenumber = new Phonenumber('6155139185');
        $user->addPhonenumber($phonenumber);

        $this->dm->persist($user);
        $this->dm->flush();

        $qb = $this->dm->createQueryBuilder('Documents\User');
        $embeddedQb = $this->dm->createQueryBuilder('Documents\Phonenumber');

        $qb->field('phonenumbers')->elemMatch($embeddedQb->expr()->field('phonenumber')->equals('6155139185'));
        $query = $qb->getQuery();
        $user = $query->getSingleResult();
        $this->assertNotNull($user);
    }

    public function testAddElemMatchWithDeepFields()
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

        $qb = $this->dm->createQueryBuilder('Documents\User');
        $embeddedQb = $this->dm->createQueryBuilder('Documents\Phonenumber');

        $qb->field('phonenumbers')->elemMatch($embeddedQb->expr()->field('lastCalledBy.$id')->equals(new \MongoId($user1->getId())));
        $query = $qb->getQuery();
        $user = $query->getSingleResult();
        $this->assertNotNull($user);
    }

    public function testAddNot()
    {
        $user = new User();
        $user->setUsername('boo');

        $this->dm->persist($user);
        $this->dm->flush();

        $qb = $this->dm->createQueryBuilder('Documents\User');
        $qb->field('username')->not($qb->expr()->in(array('boo')));
        $query = $qb->getQuery();
        $user = $query->getSingleResult();
        $this->assertNull($user);

        $qb->field('username')->not($qb->expr()->in(array('1boo')));
        $query = $qb->getQuery();
        $user = $query->getSingleResult();
        $this->assertNotNull($user);
    }

    public function testDistinct()
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

        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->distinct('count')
            ->field('username')->equals('distinct_test');
        $q = $qb->getQuery();
        $results = $q->execute();
        $this->assertEquals(array(1, 2, 3), $results->toArray());

        $results = $this->dm->createQueryBuilder('Documents\User')
            ->distinct('count')
            ->field('username')->equals('distinct_test')
            ->getQuery()
            ->execute();
        $this->assertEquals(array(1, 2, 3), $results->toArray());
    }

    public function testDistinctWithDifferentDbName()
    {
        $c1 = new CmsComment();
        $c1->authorIp = "127.0.0.1";
        $c2 = new CmsComment();
        $c3 = new CmsComment();
        $c2->authorIp = $c3->authorIp = "192.168.0.1";
        $this->dm->persist($c1);
        $this->dm->persist($c2);
        $this->dm->persist($c3);
        $this->dm->flush();
        $this->dm->clear();

        $results = $this->dm->createQueryBuilder(get_class($c1))
            ->distinct('authorIp')
            ->getQuery()
            ->execute();
        $this->assertEquals(array("127.0.0.1", "192.168.0.1"), $results->toArray());
    }

    public function testFindQuery()
    {
        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->where("function() { return this.username == 'boo' }");
        $query = $qb->getQuery();
        $user = $query->getSingleResult();
        $this->assertEquals('boo', $user->getUsername());
    }

    public function testUpdateQuery()
    {
        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->update()
            ->field('username')
            ->set('crap')
            ->equals('boo');
        $query = $qb->getQuery();
        $result = $query->execute();

        $this->dm->refresh($this->user);
        $this->assertEquals('crap', $this->user->getUsername());
    }

    public function testUpsertUpdateQuery()
    {
        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->update()
            ->upsert(true)
            ->field('username')
            ->set('crap')
            ->equals('foo');
        $query = $qb->getQuery();
        $result = $query->execute();

        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->find()
            ->field('username')->equals('crap');
        $query = $qb->getQuery();
        $user = $query->getSingleResult();
        $this->assertNotNull($user);
    }

    public function testMultipleUpdateQuery()
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

        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->update()
            ->multiple()
            ->field('username')->equals('multiple_test')
            ->field('username')->set('foo');
        $q = $qb->getQuery();
        $results = $q->execute();

        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->find()
            ->field('username')->equals('foo');
        $q = $qb->getQuery();
        $users = array_values($q->execute()->toArray());

        $this->assertEquals(4, count($users));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testRemoveQuery()
    {
        $this->dm->remove($this->user);

        // should invoke exception because $this->user doesn't exist anymore
        $this->dm->refresh($this->user);
    }

    public function testIncUpdateQuery()
    {
        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->update()
            ->field('hits')->inc(5)
            ->field('username')->equals('boo');
        $query = $qb->getQuery();
        $query->execute();
        $query->execute();

        $qb->find('Documents\User')
            ->hydrate(false);
        $query = $qb->getQuery();
        $user = $query->getSingleResult();
        $this->assertEquals(10, $user['hits']);
    }

    public function testUnsetFieldUpdateQuery()
    {
        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->update()
            ->field('hits')->unsetField()
            ->field('username')->equals('boo');
        $query = $qb->getQuery();
        $result = $query->execute();

        $qb->find('Documents\User')
            ->hydrate(false);
        $query = $qb->getQuery();
        $user = $query->getSingleResult();
        $this->assertFalse(isset($user['hits']));
    }

    public function testGroup()
    {
        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->group(array('username' => 1), array('count' => 0))
            ->reduce('function (obj, prev) { prev.count++; }');
        $query = $qb->getQuery();
        $result = $query->execute();
        $this->assertEquals(array(array('username' => 'boo', 'count' => 1)), $result->toArray());
    }

    public function testUnsetField()
    {
        $qb = $this->dm->createQueryBuilder()
            ->update('Documents\User')
            ->field('nullTest')
            ->type('null')
            ->unsetField('nullTest');
        $query = $qb->getQuery();
        $query->execute();

        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->field('nullTest')->type('null');
        $query = $qb->getQuery();
        $user = $query->getSingleResult();
        $this->assertNull($user);
    }

    public function testDateRange()
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

        $qb = $this->dm->createQueryBuilder('Documents\Article');
        $qb->field('createdAt')->range(
            new \MongoDate(strtotime('1985-09-01 01:00:00')),
            new \MongoDate(strtotime('1985-09-04'))
        );
        $query = $qb->getQuery();
        $articles = array_values($query->execute()->toArray());
        $this->assertEquals(2, count($articles));
        $this->assertEquals('1985-09-02', $articles[0]->getCreatedAt()->format('Y-m-d'));
        $this->assertEquals('1985-09-03', $articles[1]->getCreatedAt()->format('Y-m-d'));
    }

    public function testQueryIsIterable()
    {
        $article = new Article();
        $article->setTitle('test');
        $this->dm->persist($article);
        $this->dm->flush();

        $qb = $this->dm->createQueryBuilder('Documents\Article');
        $query = $qb->getQuery();
        $this->assertTrue($query instanceof \Doctrine\MongoDB\IteratorAggregate);
        foreach ($query as $article) {
            $this->assertEquals('Documents\Article', get_class($article));
        }
    }

    public function testQueryReferences()
    {
        $group = new \Documents\Group('Test Group');

        $user = new User();
        $user->setUsername('cool');
        $user->addGroup($group);

        $this->dm->persist($user);
        $this->dm->flush();

        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->field('groups')->references($group);
        $query = $qb->getQuery();
        $user2 = $query->getSingleResult();
        $this->assertSame($user, $user2);
    }

    public function testQueryWhereIn()
    {
        $qb = $this->dm->createQueryBuilder('Documents\User');
        $choices = array('a', 'b');
        $qb->field('username')->in($choices);
        $expected = array(
            'username' => array(
                '$in' => $choices
            )
        );
        $this->assertSame($expected, $qb->getQueryArray());
    }

    public function testQueryWhereInReferenceId()
    {
        $qb = $this->dm->createQueryBuilder('Documents\User');
        $choices = array(new \MongoId(), new \MongoId());
        $qb->field('account.$id')->in($choices);
        $expected = array(
            'account.$id' => array(
                '$in' => $choices
            )
        );
        $this->assertSame($expected, $qb->getQueryArray());
        $this->assertSame($expected, $qb->getQuery()->debug('query'));
    }

    // search for articles that have the "pet" tag in their tags collection
    public function testQueryWhereOneValueOfCollection()
    {
        $qb = $this->dm->createQueryBuilder('Documents\Article');
        $qb->field('tags')->equals('pet');
        $expected = array(
            'tags' => 'pet'
        );
        $this->assertSame($expected, $qb->getQueryArray());
        $this->assertSame($expected, $qb->getQuery()->debug('query'));
    }

    // search for articles where tags exactly equal [pet, blue]
    public function testQueryWhereAllValuesOfCollection()
    {
        $qb = $this->dm->createQueryBuilder('Documents\Article');
        $qb->field('tags')->equals(array('pet', 'blue'));
        $expected = array(
            'tags' => array('pet', 'blue')
        );
        $this->assertSame($expected, $qb->getQueryArray());
        $this->assertSame($expected, $qb->getQuery()->debug('query'));
    }
}
