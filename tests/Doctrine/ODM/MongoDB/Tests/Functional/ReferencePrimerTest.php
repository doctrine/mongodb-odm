<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Query\Query;
use Documents\Account;
use Documents\Agent;
use Documents\Functional\FavoritesUser;
use Documents\Group;
use Documents\GuestServer;
use Documents\Phonenumber;
use Documents\Project;
use Documents\SimpleReferenceUser;
use Documents\User;
use Documents\Ecommerce\ConfigurableProduct;
use Documents\Ecommerce\Currency;
use Documents\Ecommerce\Money;
use Documents\Ecommerce\Option;
use Documents\Ecommerce\StockItem;

class ReferencePrimerTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    /**
     * @expectedException InvalidArgumentException
     */
    public function testPrimeReferencesShouldRequireReferenceMapping()
    {
        $user = new User();

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $this->dm->createQueryBuilder('Documents\User')
            ->field('username')->prime(true)
            ->getQuery()
            ->toArray();
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testPrimeReferencesShouldRequireOwningSideReferenceMapping()
    {
        $user = new User();

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $this->dm->createQueryBuilder('Documents\User')
            ->field('simpleReferenceOneInverse')->prime(true)
            ->getQuery()
            ->toArray();
    }

    public function testPrimeImpliesEagerCursor()
    {
        $query = $this->dm->createQueryBuilder('Documents\User')
            ->field('account')
            ->prime(true)
            ->getQuery()
            ->getQuery();

        $this->assertArrayHasKey('eagerCursor', $query);
        $this->assertTrue($query['eagerCursor']);
    }

    /**
     * @expectedException BadMethodCallException
     */
    public function testPrimeForbidsLazyCursor()
    {
        $this->dm->createQueryBuilder('Documents\User')
            ->field('account')
            ->prime(true)
            ->eagerCursor(false);
    }

    public function testFieldPrimingCanBeToggled()
    {
        $this->dm->createQueryBuilder('Documents\User')
            ->field('account')
            ->prime(true)
            ->prime(false)
            ->eagerCursor(false);
    }

    /**
     * @expectedException BadMethodCallException
     */
    public function testLazyCursorForbidsPrime()
    {
        $this->dm->createQueryBuilder('Documents\User')
            ->eagerCursor(false)
            ->field('account')
            ->prime(true);
    }

    public function testPrimeReferencesWithDBRefObjects()
    {
        $user = new User();
        $user->addGroup(new Group());
        $user->addGroup(new Group());
        $user->setAccount(new Account());

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->field('account')->prime(true)
            ->field('groups')->prime(true);

        foreach ($qb->getQuery() as $user) {
            $this->assertInstanceOf('Doctrine\ODM\MongoDB\Proxy\Proxy', $user->getAccount());
            $this->assertTrue($user->getAccount()->__isInitialized());

            $this->assertCount(2, $user->getGroups());

            /* Since the Groups are primed before the PersistentCollection is
             * initialized, they will not be hydrated as proxy objects.
             */
            foreach ($user->getGroups() as $group) {
                $this->assertNotInstanceOf('Doctrine\ODM\MongoDB\Proxy\Proxy', $group);
                $this->assertInstanceOf('Documents\Group', $group);
            }
        }
    }

    public function testPrimeReferencesWithSimpleReferences()
    {
        $user1 = new User();
        $user2 = new User();
        $user3 = new User();

        $simpleUser = new SimpleReferenceUser();
        $simpleUser->setUser($user1);
        $simpleUser->addUser($user2);
        $simpleUser->addUser($user3);

        $this->dm->persist($user1);
        $this->dm->persist($user2);
        $this->dm->persist($user3);
        $this->dm->persist($simpleUser);
        $this->dm->flush();
        $this->dm->clear();

        $qb = $this->dm->createQueryBuilder('Documents\SimpleReferenceUser')
            ->field('user')->prime(true)
            ->field('users')->prime(true);

        foreach ($qb->getQuery() as $simpleUser) {
            $this->assertInstanceOf('Doctrine\ODM\MongoDB\Proxy\Proxy', $simpleUser->getUser());
            $this->assertTrue($simpleUser->getUser()->__isInitialized());

            $this->assertCount(2, $simpleUser->getUsers());

            foreach ($simpleUser->getUsers() as $user) {
                $this->assertNotInstanceOf('Doctrine\ODM\MongoDB\Proxy\Proxy', $user);
                $this->assertInstanceOf('Documents\User', $user);
            }
        }
    }

    public function testPrimeReferencesWithDiscriminatedReferenceMany()
    {
        $group = new Group();
        $project = new Project('foo');

        $user = new FavoritesUser();
        $user->addFavorite($group);
        $user->addFavorite($project);

        $this->dm->persist($group);
        $this->dm->persist($project);
        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $qb = $this->dm->createQueryBuilder('Documents\Functional\FavoritesUser')
            ->field('favorites')->prime(true);

        foreach ($qb->getQuery() as $user) {
            $favorites = $user->getFavorites()->toArray();

            $this->assertNotInstanceOf('Doctrine\ODM\MongoDB\Proxy\Proxy', $favorites[0]);
            $this->assertInstanceOf('Documents\Group', $favorites[0]);

            $this->assertNotInstanceOf('Doctrine\ODM\MongoDB\Proxy\Proxy', $favorites[1]);
            $this->assertInstanceOf('Documents\Project', $favorites[1]);
        }
    }

    public function testPrimeReferencesWithDiscriminatedReferenceOne()
    {
        $agent = new Agent();
        $agent->server = new GuestServer();

        $this->dm->persist($agent->server);
        $this->dm->persist($agent);
        $this->dm->flush();
        $this->dm->clear();

        $qb = $this->dm->createQueryBuilder('Documents\Agent')
            ->field('server')->prime(true);

        foreach ($qb->getQuery() as $agent) {
            $this->assertInstanceOf('Doctrine\ODM\MongoDB\Proxy\Proxy', $agent->server);
            $this->assertTrue($agent->server->__isInitialized());
        }
    }

    public function testPrimeReferencesIgnoresInitializedProxyObjects()
    {
        $user = new User();
        $user->addGroup(new Group());
        $user->addGroup(new Group());

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $this->dm->createQueryBuilder('Documents\Group')->getQuery()->toArray();

        $invoked = 0;
        $primer = function(DocumentManager $dm, ClassMetadata $class, array $ids, array $hints) use (&$invoked) {
            $invoked++;
        };

        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->field('groups')->prime($primer);

        foreach ($qb->getQuery() as $user) {
            $this->assertCount(2, $user->getGroups());

            foreach ($user->getGroups() as $group) {
                $this->assertNotInstanceOf('Doctrine\ODM\MongoDB\Proxy\Proxy', $group);
                $this->assertInstanceOf('Documents\Group', $group);
            }
        }

        $this->assertEquals(0, $invoked, 'Primer was not invoked when all references were already managed.');
    }

    public function testPrimeReferencesInvokesPrimer()
    {
        $group1 = new Group();
        $group2 = new Group();
        $account = new Account();

        $user = new User();
        $user->addGroup($group1);
        $user->addGroup($group2);
        $user->setAccount($account);

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $invokedArgs = array();
        $primer = function(DocumentManager $dm, ClassMetadata $class, array $ids, array $hints) use (&$invokedArgs) {
            $invokedArgs[] = func_get_args();
        };

        $this->dm->createQueryBuilder('Documents\User')
            ->field('account')->prime($primer)
            ->field('groups')->prime($primer)
            ->slaveOkay(true)
            ->getQuery()
            ->toArray();

        $this->assertCount(2, $invokedArgs, 'Primer was invoked once for each referenced class.');
        $this->assertArrayHasKey(Query::HINT_SLAVE_OKAY, $invokedArgs[0][3], 'Primer was invoked with UnitOfWork hints from original query.');
        $this->assertTrue($invokedArgs[0][3][Query::HINT_SLAVE_OKAY], 'Primer was invoked with UnitOfWork hints from original query.');

        $accountIds = array($account->getId());
        $groupIds = array($group1->getId(), $group2->getId());

        $this->assertEquals($accountIds, $invokedArgs[0][2]);
        $this->assertEquals($groupIds, $invokedArgs[1][2]);
    }

    public function testPrimeReferencesInFindAndModifyResult()
    {
        $group = new Group();
        $user = new User();

        $this->dm->persist($user);
        $this->dm->persist($group);
        $this->dm->flush();

        $groupDBRef = $this->dm->createDBRef($group);

        $this->dm->clear();

        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->findAndUpdate()
            ->returnNew(true)
            ->field('groups')->push($groupDBRef)->prime(true);

        $user = $qb->getQuery()->execute();

        $this->assertCount(1, $user->getGroups());

        foreach ($user->getGroups() as $group) {
            $this->assertNotInstanceOf('Doctrine\ODM\MongoDB\Proxy\Proxy', $group);
            $this->assertInstanceOf('Documents\Group', $group);
        }
    }

    public function testPrimeEmbeddedReferenceOneLevelDeep()
    {
        $user1 = new User();
        $user2 = new User();
        $phone = new Phonenumber('555-GET-THIS',$user2);

        $user1->addPhonenumber($phone);
        $user1->setUsername('SomeName');

        $this->dm->persist($user1);
        $this->dm->persist($user2);
        $this->dm->flush();

        $this->dm->clear();

        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->field('username')->equals('SomeName')
            ->field('phonenumbers.lastCalledBy')->prime(true);

        $user = $qb->getQuery()->getSingleResult();
        $phonenumbers = $user->getPhonenumbers();

        $this->assertCount(1, $phonenumbers);

        $phonenumber = $phonenumbers->current();

        $this->assertNotInstanceOf('Doctrine\ODM\MongoDB\Proxy\Proxy', $phonenumber);
        $this->assertInstanceOf('Documents\Phonenumber', $phonenumber);
    }

    public function testPrimeEmbeddedReferenceTwoLevelsDeep()
    {
        $product = new ConfigurableProduct('Bundle');

        $product->addOption(
            new Option('Lens1', new Money(75.00, new Currency('USD',1)),
            new StockItem('Filter1', new Money(50.00, new Currency('USD',1)), 1))
        );
        $product->addOption(
            new Option('Lens2', new Money(120.00, new Currency('USD',1)),
            new StockItem('Filter2', new Money(100.00, new Currency('USD',1)), 1))
        );
        $product->addOption(
            new Option('Lens3', new Money(180.00, new Currency('USD',1)),
            new StockItem('Filter3', new Money(0.01, new Currency('USD',1)), 1))
        );

        $this->dm->persist($product);
        $this->dm->flush();
        $this->dm->clear();

        $qb = $this->dm->createQueryBuilder('Documents\Ecommerce\ConfigurableProduct')
            ->field('name')->equals('Bundle')
            ->field('options.money.currency')->prime(true);

        /** @var Query $query */
        $query = $qb->getQuery();

        /** @var ConfigurableProduct $product */
        $product = $query->getSingleResult();

        /** @var Option $option */
        $option = $product->getOption('Lens2');

        /** @var Money $money */
        $money = $option->getPrice(true);

        /** @var Currency $currency */
        $currency = $money->getCurrency();

        $this->assertInstanceOf('Doctrine\ODM\MongoDB\Proxy\Proxy', $currency);
        $this->assertTrue($currency->__isInitialized());
    }
}
