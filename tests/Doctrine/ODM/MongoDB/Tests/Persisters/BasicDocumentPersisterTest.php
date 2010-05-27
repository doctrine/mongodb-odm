<?php

namespace Doctrine\ODM\MongoDB\Tests\Persisters;

require_once __DIR__ . '/../../../../../TestInit.php';

use Doctrine\ODM\MongoDB\Persisters\BasicDocumentPersister,
    Documents\Account,
    Documents\Address,
    Documents\Group,
    Documents\Phonenumber,
    Documents\Profile,
    Documents\File,
    Documents\User;

/**
 * @author Bulat Shakirzyanov <bulat@theopenskyproject.com>
 */
class BasicDocumentPersisterTest extends \BaseTest
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
        unset ($user, $account);

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

        $user->addGroup(new Group('administrator'));
        $user->addGroup(new Group('member'));
        $user->addGroup(new Group('moderator'));

        $this->dm->persist($user);
        $this->persister->expects($this->once())
            ->method('executeInserts');

        $this->dm->getUnitOfWork()->computeChangeSets();
        $update = $this->persister->prepareUpdateData($user);

        $this->assertTrue(array_key_exists('$pushAll', $update));
        $this->assertTrue(array_key_exists('groups', $update['$pushAll']));
        $this->assertEquals(3, count($update['$pushAll']['groups']));
        $this->assertFalse(array_key_exists('$pullAll', $update));

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

        unset ($user, $account);

        $user = $this->dm->findOne('Documents\User');

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

        unset ($user, $account);

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

        $this->assertTrue(array_key_exists('$pullAll', $update));
        $this->assertTrue(array_key_exists('groups', $update['$pullAll']));
        $this->assertEquals(2, count($update['$pullAll']['groups']));
        $this->assertTrue(array_key_exists('$pushAll', $update));
        $this->assertTrue(array_key_exists('groups', $update['$pushAll']));
        $this->assertEquals(2, count($update['$pushAll']['groups']));

        $this->dm->flush();
    }
}
