<?php

namespace Doctrine\ODM\MongoDB\Tests\Persisters;

require_once __DIR__ . '/../../../../../TestInit.php';

use Doctrine\ODM\MongoDB\Persisters\BasicDocumentPersister,
    Documents\Account,
    Documents\Article,
    Documents\Address,
    Documents\Group,
    Documents\Phonenumber,
    Documents\Profile,
    Documents\File,
    Documents\User;

/**
 * @author Bulat Shakirzyanov <bulat@theopenskyproject.com>
 */
class BasicDocumentPersisterTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    protected $persister;
    protected $classMetadata;

    public function setUp()
    {
        parent::setUp();
        $this->classMetadata = $this->dm->getClassMetadata('Documents\User');
        $this->persister = $this->getMock(
            'Doctrine\ODM\MongoDB\Persisters\BasicDocumentPersister',
            array('update', 'delete', 'executeInserts'),
            array($this->dm, $this->classMetadata)
        );
        $this->dm->getUnitOfWork()->setDocumentPersister(
            'Documents\User', $this->persister
        );
    }

    public function tearDown()
    {
        $this->persister = null;
        parent::tearDown();
    }

    public function testNewDocumentInsert()
    {
        $account = new Account();
        $account->setName('Jon Test Account');

        $user = new User();
        $user->setUsername('jon');
        $user->setPassword('changeme');
        $user->setAccount($account);

        $this->persister->expects($this->once())
            ->method('executeInserts');

        $this->dm->persist($user);
        $this->dm->getUnitOfWork()->computeChangeSets();
        $update = $this->persister->prepareUpdateData($user);
        $this->dm->flush();

        $this->assertTrue(array_key_exists('$set', $update));
        $this->assertFalse(array_key_exists('$unset', $update));
        $this->assertTrue(array_key_exists('username', $update['$set']));
        $this->assertTrue(array_key_exists('password', $update['$set']));
        $this->assertTrue(array_key_exists('account', $update['$set']));
        $this->assertTrue(array_key_exists('$ref', $update['$set']['account']));
        $this->assertTrue(array_key_exists('$db', $update['$set']['account']));
        $this->assertTrue(array_key_exists('$id', $update['$set']['account']));
    }

    public function testDocumentUpdate()
    {
        $this->dm->getUnitOfWork()->setDocumentPersister(
            'Documents\User', new BasicDocumentPersister($this->dm, $this->classMetadata)
        );

        $account = new Account();
        $account->setName('Jon Test Account');

        $user = new User();
        $user->setUsername('jon');
        $user->setPassword('changeme');
        $user->setAccount($account);

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();
        unset($user, $account);

        $user = $this->dm->findOne('Documents\User');
        $this->assertEquals('jon', $user->getUsername());
        $this->assertEquals('changeme', $user->getPassword());
        $this->assertTrue($user->getAccount() instanceof Account);

        $user->setUsername(null);
        $this->dm->getUnitOfWork()->setDocumentPersister(
            'Documents\User', $this->persister
        );

        $this->persister->expects($this->once())
            ->method('update')
            ->with($user);

        $this->dm->getUnitOfWork()->computeChangeSets();
        $update = $this->persister->prepareUpdateData($user);

        $this->assertTrue(array_key_exists('$unset', $update));
        $this->assertTrue(array_key_exists('username', $update['$unset']));
        $this->assertFalse(array_key_exists('$set', $update));

        $this->dm->flush();
    }

    public function testAddGroups()
    {
        $account = new Account();
        $account->setName('Jon Test Account');

        $user = new User();
        $user->setUsername('jon');
        $user->setPassword('changeme');
        $user->setAccount($account);
        $user->setCount(5);

        $user->addGroup(new Group('administrator'));
        $user->addGroup(new Group('member'));
        $user->addGroup(new Group('moderator'));


        $this->dm->persist($user);
        $this->persister->expects($this->once())
            ->method('executeInserts');

        $this->dm->getUnitOfWork()->computeChangeSets();
        $update = $this->persister->prepareUpdateData($user);

        $this->assertTrue(array_key_exists('$set', $update));
        $this->assertFalse(array_key_exists('$unset', $update));
        $this->assertTrue(array_key_exists('$pushAll', $update));
        $this->assertTrue(array_key_exists('groups', $update['$pushAll']));
        $this->assertEquals(3, count($update['$pushAll']['groups']));
        $this->assertFalse(array_key_exists('$pullAll', $update));
        $this->assertTrue(array_key_exists('$inc', $update));
        $this->assertEquals(5, $update['$inc']['count']);

        $user->setCount(20);
        $this->dm->getUnitOfWork()->computeChangeSets();
        $update = $this->persister->prepareUpdateData($user);
        $this->assertTrue(array_key_exists('$inc', $update));
        $this->assertEquals(15, $update['$inc']['count']);

        $user->setCount(5);
        $this->dm->getUnitOfWork()->computeChangeSets();
        $update = $this->persister->prepareUpdateData($user);
        $this->assertTrue(array_key_exists('$inc', $update));
        $this->assertEquals(-15, $update['$inc']['count']);

        $this->dm->flush();
    }

    public function testRemoveGroups()
    {
        $this->dm->getUnitOfWork()->setDocumentPersister(
            'Documents\User', new BasicDocumentPersister($this->dm, $this->classMetadata)
        );

        $account = new Account();
        $account->setName('Jon Test Account');

        $user = new User();
        $user->setUsername('jon');
        $user->setPassword('changeme');
        $user->setAccount($account);

        $user->addGroup(new Group('administrator'));
        $user->addGroup(new Group('member'));
        $user->addGroup(new Group('moderator'));

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        unset($user, $account);

        $user = $this->dm->findOne('Documents\User');
        $this->assertEquals(3, count($user->getGroups()));
  
        $user->removeGroup('moderator');
        $user->removeGroup('member');

        $this->assertEquals(1, count($user->getGroups()));

        $this->dm->getUnitOfWork()->setDocumentPersister(
            'Documents\User', $this->persister
        );

        $this->persister->expects($this->once())
            ->method('update')
            ->with($user);

        $this->dm->getUnitOfWork()->computeChangeSets();
        $update = $this->persister->prepareUpdateData($user);

        $this->assertFalse(array_key_exists('$set', $update));
        $this->assertFalse(array_key_exists('$unset', $update));
        $this->assertTrue(array_key_exists('$pullAll', $update));
        $this->assertTrue(array_key_exists('groups', $update['$pullAll']));
        $this->assertEquals(2, count($update['$pullAll']['groups']));
        $this->assertFalse(array_key_exists('$pushAll', $update));

        $this->dm->flush();
    }

    public function testReplaceGroups()
    {
        $this->dm->getUnitOfWork()->setDocumentPersister(
            'Documents\User', new BasicDocumentPersister($this->dm, $this->classMetadata)
        );

        $account = new Account();
        $account->setName('Jon Test Account');

        $user = new User();
        $user->setUsername('jon');
        $user->setPassword('changeme');
        $user->setAccount($account);

        $user->addGroup(new Group('administrator'));
        $user->addGroup(new Group('member'));
        $user->addGroup(new Group('moderator'));

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        unset($user, $account);

        $user = $this->dm->findOne('Documents\User');

        $user->removeGroup('moderator');
        $user->removeGroup('member');

        $this->assertEquals(1, count($user->getGroups()));

        $user->addGroup(new Group('seller'));
        $user->addGroup(new Group('supplier'));

        $this->assertEquals(3, count($user->getGroups()));

        $this->dm->getUnitOfWork()->setDocumentPersister(
            'Documents\User', $this->persister
        );

        $this->persister->expects($this->once())
            ->method('update')
            ->with($user);

        $this->dm->getUnitOfWork()->computeChangeSets();
        $update = $this->persister->prepareUpdateData($user);

        $this->assertFalse(array_key_exists('$set', $update));
        $this->assertFalse(array_key_exists('$unset', $update));
        $this->assertTrue(array_key_exists('$pullAll', $update));
        $this->assertTrue(array_key_exists('groups', $update['$pullAll']));
        $this->assertEquals(2, count($update['$pullAll']['groups']));
        $this->assertTrue(array_key_exists('$pushAll', $update));
        $this->assertTrue(array_key_exists('groups', $update['$pushAll']));
        $this->assertEquals(2, count($update['$pushAll']['groups']));

        $this->dm->flush();
        $this->dm->clear();

        unset($user);

        $user = $this->dm->findOne('Documents\User');
        $this->assertEquals(3, count($user->getGroups()));
    }

    public function testCollectionField()
    {
        $classMetadata = $this->dm->getClassMetadata('Documents\Article');
        $persister = new BasicDocumentPersister($this->dm, $classMetadata);
        $this->dm->getUnitOfWork()->setDocumentPersister(
            'Documents\Article', $persister
        );

        $article = new Article();
        $article->setTitle('test');
        $article->setBody('test');
        $article->setCreatedAt('1985-09-04 00:00:00');

        $article->addTag('tag 1');
        $article->addTag('tag 2');
        $article->addTag('tag 3');
        $article->addTag('tag 4');

        $this->dm->persist($article);

        $this->dm->getUnitOfWork()->computeChangeSets();
        $update = $persister->prepareUpdateData($article);

        $this->assertTrue(array_key_exists('$pushAll', $update));
        $this->assertTrue(array_key_exists('tags', $update['$pushAll']));
        $this->assertEquals(4, count($update['$pushAll']['tags']));
        $this->assertFalse(array_key_exists('$pullAll', $update));

        $this->dm->flush();
        $this->dm->clear();
        unset($article);

        $article = $this->dm->findOne('Documents\Article');

        $this->assertEquals(array(
            'tag 1', 'tag 2', 'tag 3', 'tag 4',
        ), $article->getTags());

        $article->removeTag('tag 1');
        $article->removeTag('tag 3');
        $article->addTag('tag 5');

        $this->dm->getUnitOfWork()->computeChangeSets();
        $update = $persister->prepareUpdateData($article);

        $this->assertTrue(array_key_exists('$pushAll', $update));
        $this->assertTrue(array_key_exists('tags', $update['$pushAll']));
        $this->assertEquals(1, count($update['$pushAll']['tags']));
        $this->assertTrue(array_key_exists('$pullAll', $update));
        $this->assertTrue(array_key_exists('tags', $update['$pullAll']));
        $this->assertEquals(2, count($update['$pullAll']['tags']));

        $this->dm->flush();
        $this->dm->clear();
        unset($article);

        $article = $this->dm->findOne('Documents\Article');

        $this->assertEquals(array(
            'tag 2', 'tag 4', 'tag 5'
        ), $article->getTags());
    }
}