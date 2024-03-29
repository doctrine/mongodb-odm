<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DefaultRepositoryFactory;
use Doctrine\ODM\MongoDB\Repository\RepositoryFactory;
use Documents\User;

class RepositoryFactoryTest extends BaseTestCase
{
    public function testRepositoryFactoryCanBeReplaced(): void
    {
        $factory = $this->createMock(RepositoryFactory::class);
        $factory->expects($this->once())->method('getRepository');

        $conf = static::getConfiguration();
        $conf->setRepositoryFactory($factory);
        $dm = DocumentManager::create(null, $conf);

        $dm->getRepository(User::class);
    }

    public function testRepositoriesAreSameForSameClasses(): void
    {
        $proxy = $this->dm->getPartialReference(User::class, 'abc');
        self::assertSame(
            $this->dm->getRepository(User::class),
            $this->dm->getRepository($proxy::class),
        );
    }

    public function testRepositoriesAreDifferentForDifferentDms(): void
    {
        $conf = static::getConfiguration();
        $conf->setRepositoryFactory(new DefaultRepositoryFactory());

        $dm1 = DocumentManager::create(null, $conf);
        $dm2 = DocumentManager::create(null, $conf);

        self::assertNotSame(
            $dm1->getRepository(User::class),
            $dm2->getRepository(User::class),
        );
    }
}
