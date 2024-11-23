<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use DateTime;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Query\Query;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Doctrine\ODM\MongoDB\Tests\ClassMetadataTestUtil;
use Documents\Account;
use Documents\Agent;
use Documents\BlogPost;
use Documents\Comment;
use Documents\Ecommerce\ConfigurableProduct;
use Documents\Ecommerce\Currency;
use Documents\Ecommerce\Money;
use Documents\Ecommerce\Option;
use Documents\Ecommerce\StockItem;
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
use InvalidArgumentException;
use MongoDB\Driver\ReadPreference;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;

use function assert;
use function func_get_args;

class ReferencePrimerTest extends BaseTestCase
{
    public function testPrimeReferencesShouldRequireReferenceMapping(): void
    {
        $user = new User();

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $this->expectException(InvalidArgumentException::class);
        $this->dm->createQueryBuilder(User::class)
            ->field('username')->prime(true)
            ->getQuery()
            ->toArray();
    }

    public function testPrimeReferencesShouldRequireOwningSideReferenceMapping(): void
    {
        $user = new User();

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $this->expectException(InvalidArgumentException::class);
        $this->dm->createQueryBuilder(User::class)
            ->field('simpleReferenceOneInverse')->prime(true)
            ->getQuery()
            ->toArray();
    }

    #[DoesNotPerformAssertions]
    public function testFieldPrimingCanBeToggled(): void
    {
        $this->dm->createQueryBuilder(User::class)
            ->field('account')
            ->prime(true)
            ->prime(false);
    }

    public function testPrimeReferencesWithDBRefObjects(): void
    {
        $user = new User();
        $user->addGroup(new Group());
        $user->addGroup(new Group());
        $user->setAccount(new Account());

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $qb = $this->dm->createQueryBuilder(User::class)
            ->field('account')->prime(true)
            ->field('groups')->prime(true);

        foreach ($qb->getQuery() as $user) {
            self::assertTrue(self::isLazyObject($user->getAccount()));
            self::assertFalse($this->uow->isUninitializedObject($user->getAccount()));

            self::assertCount(2, $user->getGroups());

            /* Since the Groups are primed before the PersistentCollection is
             * initialized, they will not be hydrated as proxy objects.
             */
            foreach ($user->getGroups() as $group) {
                self::assertFalse(self::isLazyObject($group));
                self::assertInstanceOf(Group::class, $group);
            }
        }
    }

    public function testPrimeReferencesWithSimpleReferences(): void
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

        $qb = $this->dm->createQueryBuilder(SimpleReferenceUser::class)
            ->field('user')->prime(true)
            ->field('users')->prime(true);

        foreach ($qb->getQuery() as $simpleUser) {
            self::assertTrue(self::isLazyObject($simpleUser->getUser()));
            self::assertFalse($this->uow->isUninitializedObject($simpleUser->getUser()));

            self::assertCount(2, $simpleUser->getUsers());

            foreach ($simpleUser->getUsers() as $user) {
                self::assertFalse(self::isLazyObject($user));
                self::assertInstanceOf(User::class, $user);
            }
        }
    }

    public function testPrimeReferencesNestedInNamedEmbeddedReference(): void
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

        $qb = $this->dm->createQueryBuilder(EmbedNamed::class)
            ->field('embeddedDoc.referencedDoc')->prime(true)
            ->field('embeddedDoc.referencedDocs')->prime(true)
            ->field('embeddedDocs.referencedDoc')->prime(true)
            ->field('embeddedDocs.referencedDocs')->prime(true);

        foreach ($qb->getQuery() as $root) {
            self::assertFalse(self::isLazyObject($root->embeddedDoc));
            self::assertInstanceOf(EmbeddedWhichReferences::class, $root->embeddedDoc);

            self::assertCount(2, $root->embeddedDocs);
            foreach ($root->embeddedDocs as $embeddedDoc) {
                self::assertFalse(self::isLazyObject($embeddedDoc));
                self::assertInstanceOf(EmbeddedWhichReferences::class, $embeddedDoc);

                self::assertTrue(self::isLazyObject($embeddedDoc->referencedDoc));
                self::assertFalse($this->uow->isUninitializedObject($embeddedDoc->referencedDoc));

                self::assertCount(2, $embeddedDoc->referencedDocs);
                foreach ($embeddedDoc->referencedDocs as $referencedDoc) {
                    self::assertFalse(self::isLazyObject($referencedDoc));
                    self::assertInstanceOf(Reference::class, $referencedDoc);
                }
            }
        }
    }

    public function testPrimeReferencesWithDifferentStoreAsReferences(): void
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

        $qb = $this->dm->createQueryBuilder(ReferenceUser::class)
            ->field('user')->prime(true)
            ->field('users')->prime(true)
            ->field('parentUser')->prime(true)
            ->field('parentUsers')->prime(true)
            ->field('otherUser')->prime(true)
            ->field('otherUsers')->prime(true);

        foreach ($qb->getQuery() as $referenceUser) {
            assert($referenceUser instanceof ReferenceUser);
            $user = $referenceUser->getUser();
            self::assertInstanceOf(User::class, $user);
            self::assertTrue(self::isLazyObject($user));
            self::assertFalse($this->uow->isUninitializedObject($user));

            self::assertCount(1, $referenceUser->getUsers());

            foreach ($referenceUser->getUsers() as $user) {
                self::assertFalse(self::isLazyObject($user));
                self::assertInstanceOf(User::class, $user);
            }

            $parentUser = $referenceUser->getParentUser();
            self::assertTrue(self::isLazyObject($parentUser));
            self::assertInstanceOf(User::class, $parentUser);
            self::assertFalse($this->uow->isUninitializedObject($parentUser));

            self::assertCount(1, $referenceUser->getParentUsers());

            foreach ($referenceUser->getParentUsers() as $user) {
                self::assertFalse(self::isLazyObject($user));
                self::assertInstanceOf(User::class, $user);
            }

            $otherUser = $referenceUser->getOtherUser();
            self::assertInstanceOf(User::class, $otherUser);
            self::assertTrue(self::isLazyObject($otherUser));
            self::assertFalse($this->uow->isUninitializedObject($otherUser));

            self::assertCount(1, $referenceUser->getOtherUsers());

            foreach ($referenceUser->getOtherUsers() as $user) {
                self::assertFalse(self::isLazyObject($user));
                self::assertInstanceOf(User::class, $user);
            }
        }
    }

    public function testPrimeReferencesWithDiscriminatedReferenceMany(): void
    {
        $group   = new Group();
        $project = new Project('foo');

        $user = new FavoritesUser();
        $user->addFavorite($group);
        $user->addFavorite($project);

        $this->dm->persist($group);
        $this->dm->persist($project);
        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $qb = $this->dm->createQueryBuilder(FavoritesUser::class)
            ->field('favorites')->prime(true);

        foreach ($qb->getQuery() as $user) {
            $favorites = $user->getFavorites()->toArray();

            self::assertFalse(self::isLazyObject($favorites[0]));
            self::assertInstanceOf(Group::class, $favorites[0]);

            self::assertFalse(self::isLazyObject($favorites[1]));
            self::assertInstanceOf(Project::class, $favorites[1]);
        }
    }

    public function testPrimeReferencesWithDiscriminatedReferenceOne(): void
    {
        $agent         = new Agent();
        $agent->server = new GuestServer();

        $this->dm->persist($agent->server);
        $this->dm->persist($agent);
        $this->dm->flush();
        $this->dm->clear();

        $qb = $this->dm->createQueryBuilder(Agent::class)
            ->field('server')->prime(true);

        foreach ($qb->getQuery() as $agent) {
            self::assertTrue(self::isLazyObject($agent->server));
            self::assertFalse($this->uow->isUninitializedObject($agent->server));
        }
    }

    public function testPrimeReferencesIgnoresInitializedProxyObjects(): void
    {
        $user = new User();
        $user->addGroup(new Group());
        $user->addGroup(new Group());

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $this->dm->createQueryBuilder(Group::class)->getQuery()->toArray();

        $invoked = 0;
        $primer  = static function (DocumentManager $dm, ClassMetadata $class, array $ids, array $hints) use (&$invoked) {
            $invoked++;
        };

        $qb = $this->dm->createQueryBuilder(User::class)
            ->field('groups')->prime($primer);

        foreach ($qb->getQuery() as $user) {
            self::assertCount(2, $user->getGroups());

            foreach ($user->getGroups() as $group) {
                self::assertFalse(self::isLazyObject($group));
                self::assertInstanceOf(Group::class, $group);
            }
        }

        self::assertEquals(0, $invoked, 'Primer was not invoked when all references were already managed.');
    }

    public function testPrimeReferencesInvokesPrimer(): void
    {
        $group1  = new Group();
        $group2  = new Group();
        $account = new Account();

        $user = new User();
        $user->addGroup($group1);
        $user->addGroup($group2);
        $user->setAccount($account);

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        /** @var array<int, array<int, mixed>> $invokedArgs */
        $invokedArgs = [];
        $primer      = static function (DocumentManager $dm, ClassMetadata $class, array $ids, array $hints) use (&$invokedArgs) {
            $invokedArgs[] = func_get_args();
        };

        // Note: using a secondary read preference here can cause issues when using transactions
        // Using a primaryPreferred works just as well to check if the hint is passed on to the primer
        $readPreference = new ReadPreference(ReadPreference::PRIMARY_PREFERRED);
        $this->dm->createQueryBuilder(User::class)
            ->field('account')->prime($primer)
            ->field('groups')->prime($primer)
            ->setReadPreference($readPreference)
            ->getQuery()
            ->toArray();

        self::assertIsArray($invokedArgs[0]);
        self::assertIsArray($invokedArgs[1]);
        self::assertCount(2, $invokedArgs, 'Primer was invoked once for each referenced class.');
        self::assertArrayHasKey(Query::HINT_READ_PREFERENCE, $invokedArgs[0][3], 'Primer was invoked with UnitOfWork hints from original query.');
        self::assertSame($readPreference, $invokedArgs[0][3][Query::HINT_READ_PREFERENCE], 'Primer was invoked with UnitOfWork hints from original query.');

        $accountIds = [$account->getId()];
        $groupIds   = [$group1->getId(), $group2->getId()];

        self::assertEquals($accountIds, $invokedArgs[0][2]);
        self::assertEquals($groupIds, $invokedArgs[1][2]);
    }

    public function testPrimeReferencesInFindAndModifyResult(): void
    {
        $group = new Group();
        $user  = new User();

        $this->dm->persist($user);
        $this->dm->persist($group);
        $this->dm->flush();

        $groupDBRef = $this->dm->createReference($group, ClassMetadataTestUtil::getFieldMapping([
            'storeAs' => ClassMetadata::REFERENCE_STORE_AS_DB_REF,
            'targetDocument' => Group::class,
        ]));

        $this->dm->clear();

        $qb = $this->dm->createQueryBuilder(User::class)
            ->findAndUpdate()
            ->returnNew(true)
            ->field('groups')->push($groupDBRef)->prime(true);

        $user = $qb->getQuery()->execute();

        assert($user instanceof User);

        self::assertCount(1, $user->getGroups());

        foreach ($user->getGroups() as $group) {
            self::assertFalse(self::isLazyObject($group));
            self::assertInstanceOf(Group::class, $group);
        }
    }

    public function testPrimeEmbeddedReferenceOneLevelDeep(): void
    {
        $user1 = new User();
        $user2 = new User();
        $phone = new Phonenumber('555-GET-THIS', $user2);

        $user1->addPhonenumber($phone);
        $user1->setUsername('SomeName');

        $this->dm->persist($user1);
        $this->dm->persist($user2);
        $this->dm->flush();

        $this->dm->clear();

        $qb = $this->dm->createQueryBuilder(User::class)
            ->field('username')->equals('SomeName')
            ->field('phonenumbers.lastCalledBy')->prime(true);

        $user = $qb->getQuery()->getSingleResult();
        assert($user instanceof User);
        $phonenumbers = $user->getPhonenumbers();

        self::assertCount(1, $phonenumbers);

        $phonenumber = $phonenumbers->current();

        self::assertFalse(self::isLazyObject($phonenumber));
        self::assertInstanceOf(Phonenumber::class, $phonenumber);
    }

    public function testPrimeEmbeddedReferenceTwoLevelsDeep(): void
    {
        $product = new ConfigurableProduct('Bundle');

        $product->addOption(
            new Option(
                'Lens1',
                new Money(75.00, new Currency('USD', 1)),
                new StockItem('Filter1', new Money(50.00, new Currency('USD', 1)), 1),
            ),
        );
        $product->addOption(
            new Option(
                'Lens2',
                new Money(120.00, new Currency('USD', 1)),
                new StockItem('Filter2', new Money(100.00, new Currency('USD', 1)), 1),
            ),
        );
        $product->addOption(
            new Option(
                'Lens3',
                new Money(180.00, new Currency('USD', 1)),
                new StockItem('Filter3', new Money(0.01, new Currency('USD', 1)), 1),
            ),
        );

        $this->dm->persist($product);
        $this->dm->flush();
        $this->dm->clear();

        $qb = $this->dm->createQueryBuilder(ConfigurableProduct::class)
            ->field('name')->equals('Bundle')
            ->field('options.money.currency')->prime(true);

        $query = $qb->getQuery();

        $product = $query->getSingleResult();
        assert($product instanceof ConfigurableProduct);

        $option = $product->getOption('Lens2');
        assert($option instanceof Option);

        $money = $option->getPrice(true);
        assert($money instanceof Money);

        $currency = $money->getCurrency();

        self::assertTrue(self::isLazyObject($currency));
        self::assertInstanceOf(Currency::class, $currency);
        self::assertFalse($this->uow->isUninitializedObject($currency));
    }

    public function testPrimeReferencesInReferenceMany(): void
    {
        $commentAuthor = new User();
        $this->dm->persist($commentAuthor);

        $postAuthor = new User();
        $this->dm->persist($postAuthor);

        $comment         = new Comment('foo', new DateTime());
        $comment->author = $commentAuthor;
        $this->dm->persist($comment);

        $post = new BlogPost('foo');
        $post->setUser($postAuthor);
        $post->addComment($comment);

        $this->dm->flush();
        $this->dm->clear();

        $post = $this->dm->find(BlogPost::class, $post->id);
        self::assertInstanceOf(BlogPost::class, $post);

        $comment = $post->comments->first();
        self::assertTrue(self::isLazyObject($comment->author));
        self::assertFalse($this->uow->isUninitializedObject($comment->author));
    }

    public function testPrimeReferencesInReferenceManyWithRepositoryMethodEager(): void
    {
        $commentAuthor = new User();
        $this->dm->persist($commentAuthor);

        $postAuthor = new User();
        $this->dm->persist($postAuthor);

        $comment         = new Comment('foo', new DateTime());
        $comment->author = $commentAuthor;
        $this->dm->persist($comment);

        $post = new BlogPost('foo');
        $post->setUser($postAuthor);
        $post->addComment($comment);

        $this->dm->flush();
        $this->dm->clear();

        $post = $this->dm->find(BlogPost::class, $post->id);
        self::assertInstanceOf(BlogPost::class, $post);

        $comment = $post->repoCommentsWithPrimer->first();
        self::assertTrue(self::isLazyObject($comment->author));
        self::assertFalse($this->uow->isUninitializedObject($comment->author));
    }
}
