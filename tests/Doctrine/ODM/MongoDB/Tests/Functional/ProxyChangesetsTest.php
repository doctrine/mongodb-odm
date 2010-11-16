<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

require_once __DIR__ . '/../../../../../TestInit.php';

use Documents\User,
    Documents\Account,
    Doctrine\ODM\MongoDB\Mapping\Types\Type;


/**
 * Description of ProxyChangesetsTest
 *
 * @author Bulat Shakirzyanov <bulat@theopenskyproject.com>
 */
class ProxyChangesetsTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testProxyChangesets()
    {
        $this->markTestSkipped('These tests may not apply anymore');

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

        $metadata = $this->dm->getClassMetadata('Documents\User');

        $this->assertEquals('Jon Test Account', $user->getAccount()->getName());

        $uow = $this->dm->getUnitOfWork();
        $uow->computeChangeSets();

        $userPersister = $uow->getDocumentPersister('Documents\User');
        $this->assertEquals(array(), $userPersister->prepareUpdateData($user));

        $accountPersister = $uow->getDocumentPersister('Documents\Account');
        $this->assertEquals(array(), $accountPersister->prepareUpdateData($user->getAccount()));
    }

    public function testDocumentChangesets()
    {
        $this->markTestSkipped('These tests may not apply anymore');

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

        $metadata = $this->dm->getClassMetadata('Documents\User');

        $account = $this->dm->findOne('Documents\Account');

        $uow = $this->dm->getUnitOfWork();
        $uow->computeChangeSets();

        $accountPersister = $uow->getDocumentPersister('Documents\Account');
        $this->assertEquals(array(), $accountPersister->prepareUpdateData($account));
    }
}