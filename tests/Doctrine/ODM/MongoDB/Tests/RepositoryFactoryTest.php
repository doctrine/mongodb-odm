<?php

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DefaultRepositoryFactory;
use Doctrine\ODM\MongoDB\Repository\RepositoryFactory;
use Documents\User;

class RepositoryFactoryTest extends BaseTest
{
    public function testRepositoryFactoryCanBeReplaced()
    {
        $factory = $this->createMock(RepositoryFactory::class);
        $factory->expects($this->once())->method('getRepository');

        $conf = $this->getConfiguration();
        $conf->setRepositoryFactory($factory);
        $dm = DocumentManager::create(null, $conf);

        $dm->getRepository(User::class);
    }

    public function testRepositoriesAreSameForSameClasses()
    {
        $this->assertSame(
            $this->dm->getRepository(User::class),
            $this->dm->getRepository(\Proxies\__CG__\Documents\User::class)
        );
    }

    public function testRepositoriesAreDifferentForDifferentDms()
    {
        $conf = $this->getConfiguration();
        $conf->setRepositoryFactory(new DefaultRepositoryFactory());

        $dm1 = DocumentManager::create(null, $conf);
        $dm2 = DocumentManager::create(null, $conf);

        $this->assertNotSame(
            $dm1->getRepository(User::class),
            $dm2->getRepository(User::class)
        );
    }
}
