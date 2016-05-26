<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Query\Query;
use Documents\Account;
use Documents\Agent;
use Documents\Functional\EmbeddedWhichReferences;
use Documents\Functional\EmbedNamed;
use Documents\Functional\FavoritesUser;
use Documents\Functional\Reference;
use Documents\Group;
use Documents\GuestServer;
use Documents\Phonenumber;
use Documents\Project;
use Documents\ReferenceUser;
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

    /**
     * @group current
     */
    public function testPrimeReferencesNestedInNamedEmbeddedReference()
    {
        $root = new EmbedNamed();

        $root->embeddedDoc    = ($embedded1 = new EmbeddedWhichReferences());
        $root->embeddedDocs[] = ($embedded2 = new EmbeddedWhichReferences());
        $root->embeddedDocs[] = ($embedded3 = new EmbeddedWhichReferences());

        $embedded1->referencedDoc    = ($referenced11 = new Reference());
        $embedded1->referencedDocs[] = ($referenced12 = new Reference());
        $embedded1->referencedDocs[] = ($referenced13 = new Reference());

        $embedded2->referencedDoc    = ($referenced21 = new Reference());
        $embedded2->referencedDocs[] = ($referenced22 = new Reference());
        $embedded2->referencedDocs[] = ($referenced23 = new Reference());

        $embedded3->referencedDoc    = ($referenced31 = new Reference());
        $embedded3->referencedDocs[] = ($referenced32 = new Reference());
        $embedded3->referencedDocs[] = ($referenced33 = new Reference());

        $this->dm->persist($referenced33);
        $this->dm->persist($referenced32);
        $this->dm->persist($referenced31);
        $this->dm->persist($referenced23);
        $this->dm->persist($referenced22);
        $this->dm->persist($referenced21);
        $this->dm->persist($referenced13);
        $this->dm->persist($referenced12);
        $this->dm->persist($referenced11);
        $this->dm->persist($embedded3);
        $this->dm->persist($embedded2);
        $this->dm->persist($embedded1);
        $this->dm->persist($root);
        $this->dm->flush();
        $this->dm->clear();

        $qb = $this->dm->createQueryBuilder('Documents\Functional\EmbedNamed')
            ->field('embeddedDoc.referencedDoc')->prime(true)
            ->field('embeddedDoc.referencedDocs')->prime(true)
            ->field('embeddedDocs.referencedDoc')->prime(true)
            ->field('embeddedDocs.referencedDocs')->prime(true)
        ;

        foreach ($qb->getQuery() as $root) {
            $this->assertNotInstanceOf('Doctrine\ODM\MongoDB\Proxy\Proxy', $root->embeddedDoc);
            $this->assertInstanceOf('Documents\Functional\EmbeddedWhichReferences', $root->embeddedDoc);

            $this->assertCount(2, $root->embeddedDocs);
            foreach ($root->embeddedDocs as $embeddedDoc) {
                $this->assertNotInstanceOf('Doctrine\ODM\MongoDB\Proxy\Proxy', $embeddedDoc);
                $this->assertInstanceOf('Documents\Functional\EmbeddedWhichReferences', $embeddedDoc);

                $this->assertInstanceOf('Doctrine\ODM\MongoDB\Proxy\Proxy', $embeddedDoc->referencedDoc);
                $this->assertTrue($embeddedDoc->referencedDoc->__isInitialized());

                $this->assertCount(2, $embeddedDoc->referencedDocs);
                foreach ($embeddedDoc->referencedDocs as $referencedDoc) {
                    $this->assertNotInstanceOf('Doctrine\ODM\MongoDB\Proxy\Proxy', $referencedDoc);
                    $this->assertInstanceOf('Documents\Functional\Reference', $referencedDoc);
                }
            }
        }
    }

    public function testPrimeReferencesWithDifferentStoreAsReferences()
    {
        $referenceUser = new ReferenceUser();
        $this->dm->persist($referenceUser);

        $user1 = new User();
        $this->dm->persist($user1);
        $referenceUser->setUser($user1);

        $user2 = new User();
        $this->dm->persist($user2);
        $referenceUser->addUser($user2);

        $parentUser1 = new User();
        $this->dm->persist($parentUser1);
        $referenceUser->setParentUser($parentUser1);

        $parentUser2 = new User();
        $this->dm->persist($parentUser2);
        $referenceUser->addParentUser($parentUser2);

        $otherUser1 = new User();
        $this->dm->persist($otherUser1);
        $referenceUser->setOtherUser($otherUser1);

        $otherUser2 = new User();
        $this->dm->persist($otherUser2);
        $referenceUser->addOtherUser($otherUser2);

        $this->dm->flush();
        $this->dm->clear();

        $qb = $this->dm->createQueryBuilder('Documents\ReferenceUser')
            ->field('user')->prime(true)
            ->field('users')->prime(true)
            ->field('parentUser')->prime(true)
            ->field('parentUsers')->prime(true)
            ->field('otherUser')->prime(true)
            ->field('otherUsers')->prime(true);

        /** @var ReferenceUser $referenceUser */
        foreach ($qb->getQuery() as $referenceUser) {
            // storeAs=id reference
            $this->assertInstanceOf('Doctrine\ODM\MongoDB\Proxy\Proxy', $referenceUser->getUser());
            $this->assertTrue($referenceUser->getUser()->__isInitialized());

            $this->assertCount(1, $referenceUser->getUsers());

            foreach ($referenceUser->getUsers() as $user) {
                $this->assertNotInstanceOf('Doctrine\ODM\MongoDB\Proxy\Proxy', $user);
                $this->assertInstanceOf('Documents\User', $user);
            }

            // storeAs=dbRef reference
            $this->assertInstanceOf('Doctrine\ODM\MongoDB\Proxy\Proxy', $referenceUser->getParentUser());
            $this->assertTrue($referenceUser->getParentUser()->__isInitialized());

            $this->assertCount(1, $referenceUser->getParentUsers());

            foreach ($referenceUser->getParentUsers() as $user) {
                $this->assertNotInstanceOf('Doctrine\ODM\MongoDB\Proxy\Proxy', $user);
                $this->assertInstanceOf('Documents\User', $user);
            }

            // storeAs=dbRefWithDb reference
            $this->assertInstanceOf('Doctrine\ODM\MongoDB\Proxy\Proxy', $referenceUser->getOtherUser());
            $this->assertTrue($referenceUser->getOtherUser()->__isInitialized());

            $this->assertCount(1, $referenceUser->getOtherUsers());

            foreach ($referenceUser->getOtherUsers() as $user) {
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

    /**
     * @group replication_lag
     */
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
