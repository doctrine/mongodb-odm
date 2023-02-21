<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Iterator\Iterator;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\PersistentCollection;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\Account;
use Documents\Address;
use Documents\Agent;
use Documents\Album;
use Documents\Bars\Bar;
use Documents\Bars\Location;
use Documents\Category;
use Documents\Employee;
use Documents\Functional\EmbeddedTestLevel0;
use Documents\Functional\EmbeddedTestLevel0b;
use Documents\Functional\EmbeddedTestLevel1;
use Documents\Functional\EmbeddedTestLevel2;
use Documents\Functional\FavoritesUser;
use Documents\Functional\NotAnnotatedDocument;
use Documents\Functional\NotSaved;
use Documents\Functional\NullFieldValues;
use Documents\Functional\PreUpdateTestProduct;
use Documents\Functional\PreUpdateTestSellable;
use Documents\Functional\PreUpdateTestSeller;
use Documents\Functional\SameCollection1;
use Documents\Functional\SameCollection2;
use Documents\Functional\SameCollection3;
use Documents\Functional\SimpleEmbedAndReference;
use Documents\Group;
use Documents\GuestServer;
use Documents\Manager;
use Documents\Phonenumber;
use Documents\Profile;
use Documents\Project;
use Documents\Song;
use Documents\SubCategory;
use Documents\User;
use Documents\UserUpsert;
use Documents\UserUpsertChild;
use Documents\UserUpsertIdStrategyNone;
use InvalidArgumentException;
use MongoDB\BSON\ObjectId;

use function assert;
use function bcscale;

class FunctionalTest extends BaseTest
{
    private int $initialScale;

    public function setUp(): void
    {
        parent::setUp();

        $this->initialScale = bcscale(2);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        bcscale($this->initialScale);
    }

    public function provideUpsertObjects(): array
    {
        return [
            [UserUpsert::class, new ObjectId('4f18f593acee41d724000005'), 'user'],
            [UserUpsertIdStrategyNone::class, 'jwage', 'user'],
            [UserUpsertChild::class, new ObjectId('4f18f593acee41d724000005'), 'child'],
        ];
    }

    /**
     * @param ObjectId|string $id
     *
     * @dataProvider provideUpsertObjects
     */
    public function testUpsertObject(string $className, $id, string $discriminator): void
    {
        $user           = new $className();
        $user->id       = (string) $id;
        $user->username = 'test';
        $user->count    = 1;
        $group          = new Group('Group');
        $user->groups   = [$group];
        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $check = $this->dm->getDocumentCollection($className)->findOne(['_id' => $id]);
        self::assertNotNull($check);
        self::assertEquals((string) $id, (string) $check['_id']);
        self::assertEquals($group->getId(), (string) $check['groups'][0]['$id']);
        self::assertEquals($discriminator, $check['discriminator']);
        self::assertArrayHasKey('nullableField', $check);
        self::assertNull($check['nullableField']);

        $group2 = new Group('Group');

        $user                = new $className();
        $user->id            = $id;
        $user->hits          = 5;
        $user->count         = 2;
        $user->groups        = [$group2];
        $user->nullableField = 'foo';
        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $check = $this->dm->getDocumentCollection($className)->findOne(['_id' => $id]);
        self::assertEquals($discriminator, $check['discriminator']);
        self::assertEquals(3, $check['count']);
        self::assertEquals(5, $check['hits']);
        self::assertCount(2, $check['groups']);
        self::assertEquals($group->getId(), (string) $check['groups'][0]['$id']);
        self::assertEquals($group2->getId(), (string) $check['groups'][1]['$id']);
        self::assertArrayHasKey('username', $check);
        self::assertEquals('test', $check['username']);
        self::assertEquals('foo', $check['nullableField']);

        $user       = new $className();
        $user->id   = $id;
        $user->hits = 100;
        $this->dm->persist($user);
        $this->dm->flush();

        $check = $this->dm->getDocumentCollection($className)->findOne(['_id' => $id]);
        self::assertEquals($discriminator, $check['discriminator']);
        self::assertEquals(3, $check['count']);
        self::assertEquals(100, $check['hits']);
        self::assertCount(2, $check['groups']);
        self::assertEquals($group->getId(), (string) $check['groups'][0]['$id']);
        self::assertEquals($group2->getId(), (string) $check['groups'][1]['$id']);
        self::assertArrayHasKey('username', $check);
        self::assertEquals('test', $check['username']);
        self::assertEquals('foo', $check['nullableField']);
    }

    public function testInheritedAssociationMappings(): void
    {
        $class = $this->dm->getClassMetadata(UserUpsertChild::class);
        self::assertTrue(isset($class->associationMappings['groups']));
    }

    public function testNestedCategories(): void
    {
        $root   = new Category('Root');
        $child1 = new SubCategory('Child 1');
        $child2 = new SubCategory('Child 2');
        $child1->addChild($child2);
        $root->addChild($child1);

        $this->dm->persist($root);
        $this->dm->flush();

        $child1->setName('Child 1 Changed');
        $child2->setName('Child 2 Changed');
        $root->setName('Root Changed');
        $this->dm->flush();

        $test = $this->dm->getDocumentCollection(Category::class)->findOne();
        self::assertEquals('Child 1 Changed', $test['children'][0]['name']);
        self::assertEquals('Child 2 Changed', $test['children'][0]['children'][0]['name']);
        self::assertEquals('Root Changed', $test['name']);
    }

    public function testManyEmbedded(): void
    {
        $album = new Album('Jon');
        $album->addSong(new Song('Song #1'));
        $album->addSong(new Song('Song #2'));
        $this->dm->persist($album);
        $this->dm->flush();

        $songs = $album->getSongs();

        $songs[0]->setName('Song #1 Changed');
        $songs->add(new Song('Song #3'));
        $this->dm->flush();

        $test = $this->dm->getDocumentCollection(Album::class)->findOne(['name' => 'Jon']);
        self::assertEquals('Song #1 Changed', $test['songs'][0]['name']);

        $album->setName('jwage');
        $songs[1]->setName('ok');
        $songs->add(new Song('Song #4'));
        $songs->add(new Song('Song #5'));
        unset($songs[0]);
        $this->dm->flush();

        $test = $this->dm->getDocumentCollection(Album::class)->findOne(['name' => 'jwage']);

        self::assertEquals('jwage', $test['name']);
        self::assertEquals('ok', $test['songs'][0]['name']);
        self::assertEquals('Song #3', $test['songs'][1]['name']);
        self::assertEquals('Song #4', $test['songs'][2]['name']);
        self::assertEquals('Song #5', $test['songs'][3]['name']);
        self::assertCount(4, $test['songs']);

        $songs->clear();
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->getDocumentCollection(Album::class)->findOne(['name' => 'jwage']);
        self::assertFalse(isset($test['songs']));
    }

    public function testNewEmbedded(): void
    {
        $subAddress = new Address();
        $subAddress->setCity('Old Sub-City');

        $address = new Address();
        $address->setCity('Old City');
        $address->setSubAddress($subAddress);

        $user = new Project('Project');
        $user->setAddress($address);
        $this->dm->persist($user);
        $this->dm->flush();

        $address->setCity('New City');
        $subAddress->setCity('New Sub-City');
        $this->dm->flush();

        $test = $this->dm->getDocumentCollection(Project::class)->findOne(['name' => 'Project']);

        self::assertEquals('New Sub-City', $test['address']['subAddress']['city']);
        self::assertEquals('New City', $test['address']['city']);
    }

    public function testPersistingNewDocumentWithOnlyOneReference(): void
    {
        $server       = new GuestServer();
        $server->name = 'test';
        $this->dm->persist($server);
        $this->dm->flush();
        $id = $server->id;

        $this->dm->clear();

        $server = $this->dm->getReference(GuestServer::class, $id);

        $agent         = new Agent();
        $agent->server = $server;
        $this->dm->persist($agent);
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->getDocumentCollection(Agent::class)->findOne();

        self::assertEquals('servers', $test['server']['$ref']);
        self::assertTrue(isset($test['server']['$id']));
        self::assertEquals('server_guest', $test['server']['_doctrine_class_name']);
    }

    public function testCollection(): void
    {
        $user = new User();
        $user->setUsername('joncolltest');
        $user->log('test');
        $user->log('test');
        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $coll     = $this->dm->getDocumentCollection(User::class);
        $document = $coll->findOne(['username' => 'joncolltest']);
        self::assertCount(2, $document['logs']);

        $document = $this->dm->getRepository(User::class)->findOneBy(['username' => 'joncolltest']);
        self::assertCount(2, $document->getLogs());
        $document->log('test');
        $this->dm->flush();
        $this->dm->clear();

        $document = $this->dm->getRepository(User::class)->findOneBy(['username' => 'joncolltest']);
        self::assertCount(3, $document->getLogs());
        $document->setLogs(['ok', 'test']);
        $this->dm->flush();
        $this->dm->clear();

        $document = $this->dm->getRepository(User::class)->findOneBy(['username' => 'joncolltest']);
        self::assertEquals(['ok', 'test'], $document->getLogs());
    }

    public function testSameObjectValuesInCollection(): void
    {
        $user = new User();
        $user->setUsername('testing');
        $user->getPhonenumbers()->add(new Phonenumber('6155139185'));
        $user->getPhonenumbers()->add(new Phonenumber('6155139185'));
        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->getRepository(User::class)->findOneBy(['username' => 'testing']);
        self::assertCount(2, $user->getPhonenumbers());
    }

    public function testIncrement(): void
    {
        $user = new User();
        $user->setUsername('jon');
        $user->setCount(100);
        $user->setFloatCount(100);
        $user->setDecimal128Count('0.50');

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->getRepository(User::class)->findOneBy(['username' => 'jon']);

        $user->incrementCount(5);
        $user->incrementFloatCount(5);
        $user->incrementDecimal128Count('0.20');
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->getRepository(User::class)->findOneBy(['username' => 'jon']);
        self::assertSame(105, $user->getCount());
        self::assertSame(105.0, $user->getFloatCount());
        self::assertSame('0.70', $user->getDecimal128Count());

        $user->setCount(50);
        $user->setFloatCount(50);
        $user->setDecimal128Count('9.99');

        $this->dm->flush();
        $this->dm->clear();
        $user = $this->dm->getRepository(User::class)->findOneBy(['username' => 'jon']);
        self::assertSame(50, $user->getCount());
        self::assertSame(50.0, $user->getFloatCount());
        self::assertSame('9.99', $user->getDecimal128Count());
    }

    public function testIncrementWithFloat(): void
    {
        $user = new User();
        $user->setUsername('jon');
        $user->setCount(100);
        $user->setFloatCount(100);

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->getRepository(User::class)->findOneBy(['username' => 'jon']);

        $user->incrementCount(1.337);
        $user->incrementFloatCount(1.337);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->getRepository(User::class)->findOneBy(['username' => 'jon']);
        self::assertSame(101, $user->getCount());
        self::assertSame(101.337, $user->getFloatCount());

        $user->incrementCount(9.163);
        $user->incrementFloatCount(9.163);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->getRepository(User::class)->findOneBy(['username' => 'jon']);
        self::assertSame(110, $user->getCount());
        self::assertSame(110.5, $user->getFloatCount());
    }

    public function testIncrementSetsNull(): void
    {
        $user = new User();
        $user->setUsername('jon');
        $user->setCount(10);
        $user->setFloatCount(10);
        $user->setDecimal128Count('10.00');

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->getRepository(User::class)->findOneBy(['username' => 'jon']);
        self::assertSame(10, $user->getCount());
        self::assertSame(10.0, $user->getFloatCount());
        self::assertSame('10.00', $user->getDecimal128Count());

        $user->incrementCount(1);
        $user->incrementFloatCount(1);
        $user->incrementDecimal128Count('1');
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->getRepository(User::class)->findOneBy(['username' => 'jon']);
        self::assertSame(11, $user->getCount());
        self::assertSame(11.0, $user->getFloatCount());
        self::assertSame('11.00', $user->getDecimal128Count());

        $user->setCount(null);
        $user->setFloatCount(null);
        $user->setDecimal128Count(null);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->getRepository(User::class)->findOneBy(['username' => 'jon']);
        self::assertNull($user->getCount());
        self::assertNull($user->getFloatCount());
        self::assertNull($user->getDecimal128Count());
    }

    public function testTest(): void
    {
        $employee = new Employee();
        $employee->setName('Employee');
        $employee->setSalary(50000.00);
        $employee->setStarted(new DateTime());

        $address = new Address();
        $address->setAddress('555 Doctrine Rd.');
        $address->setCity('Nashville');
        $address->setState('TN');
        $address->setZipcode('37209');
        $employee->setAddress($address);

        $project = new Project('New Project');
        $manager = new Manager();
        $manager->setName('Manager');
        $manager->setSalary(100000.00);
        $manager->setStarted(new DateTime());
        $manager->addProject($project);

        $this->dm->persist($employee);
        $this->dm->persist($project);
        $this->dm->persist($manager);
        $this->dm->flush();

        $newProject = new Project('Another Project');
        $manager->setSalary(200000.00);
        $manager->addNote('Gave user 100k a year raise');
        $manager->incrementChanges(2);
        $manager->addProject($newProject);

        $this->dm->persist($newProject);
        $this->dm->flush();
        $this->dm->clear();

        $result = $this->dm->createQueryBuilder(Manager::class)
            ->field('name')->equals('Manager')
            ->hydrate(false)
            ->getQuery()
            ->getSingleResult();

        self::assertEquals(200000.00, $result['salary']);
        self::assertCount(2, $result['projects']);
        self::assertCount(1, $result['notes']);
        self::assertEquals('Gave user 100k a year raise', $result['notes'][0]);
    }

    public function testNotAnnotatedDocument(): void
    {
        $this->dm->getDocumentCollection(NotAnnotatedDocument::class)->drop();

        $test                 = new NotAnnotatedDocument();
        $test->field          = 'test';
        $test->transientField = 'w00t';
        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->find(NotAnnotatedDocument::class, $test->id);
        self::assertNotNull($test);
        self::assertFalse(isset($test->transientField));
    }

    public function testNullFieldValuesAllowed(): void
    {
        $this->dm->getDocumentCollection(NullFieldValues::class)->drop();

        $test        = new NullFieldValues();
        $test->field = null;
        $this->dm->persist($test);
        $this->dm->flush();

        $document = $this->dm->createQueryBuilder(NullFieldValues::class)
            ->hydrate(false)
            ->getQuery()
            ->getSingleResult();

        self::assertNotNull($document);
        self::assertNull($document['field']);

        $document        = $this->dm->find(NullFieldValues::class, $test->id);
        $document->field = 'test';
        $this->dm->flush();
        $this->dm->clear();

        $document = $this->dm->find(NullFieldValues::class, $test->id);
        self::assertEquals('test', $document->field);
        $document->field = null;
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->createQueryBuilder(NullFieldValues::class)
            ->hydrate(false)
            ->getQuery()
            ->getSingleResult();
        self::assertNull($test['field']);
        self::assertFalse(isset($test['transientField']));
    }

    public function testSimplerEmbedAndReference(): void
    {
        $class = $this->dm->getClassMetadata(SimpleEmbedAndReference::class);
        self::assertEquals('many', $class->fieldMappings['embedMany']['type']);
        self::assertEquals('one', $class->fieldMappings['embedOne']['type']);
        self::assertEquals('many', $class->fieldMappings['referenceMany']['type']);
        self::assertEquals('one', $class->fieldMappings['referenceOne']['type']);
    }

    public function testNotSavedFields(): void
    {
        $collection = $this->dm->getDocumentCollection(NotSaved::class);
        $collection->drop();
        $test = [
            '_id' => new ObjectId(),
            'name' => 'Jonathan Wage',
            'notSaved' => 'test',
        ];
        $collection->insertOne($test);
        $notSaved = $this->dm->find(NotSaved::class, $test['_id']);
        self::assertEquals('Jonathan Wage', $notSaved->name);
        self::assertEquals('test', $notSaved->notSaved);

        $notSaved           = new NotSaved();
        $notSaved->name     = 'Roman Borschel';
        $notSaved->notSaved = 'test';
        $this->dm->persist($notSaved);
        $this->dm->flush();
        $this->dm->clear();

        $notSaved = $collection->findOne(['name' => 'Roman Borschel']);
        self::assertEquals('Roman Borschel', $notSaved['name']);
        self::assertFalse(isset($notSaved['notSaved']));
    }

    public function testTypeClassMissing(): void
    {
        $project = new Project('Test Project');
        $this->dm->persist($project);
        $this->dm->flush();

        $group = new Group('Test Group');
        $this->dm->persist($group);
        $this->dm->flush();

        $user = new FavoritesUser();
        $user->setName('favorites');
        $user->addFavorite($project);
        $user->addFavorite($group);
        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->find(FavoritesUser::class, $user->getId());
        assert($test instanceof FavoritesUser);

        $collection = $test->getFavorites();
        assert($collection instanceof PersistentCollection);
        $this->expectException(MongoDBException::class);
        $collection->getTypeClass();
    }

    public function testTypeClass(): void
    {
        $bar = new Bar("Jon's Pub");
        $bar->addLocation(new Location('West Nashville'));
        $bar->addLocation(new Location('East Nashville'));
        $bar->addLocation(new Location('North Nashville'));
        $this->dm->persist($bar);
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->find(Bar::class, $bar->getId());
        assert($test instanceof Bar);

        $collection = $test->getLocations();
        assert($collection instanceof PersistentCollection);
        self::assertInstanceOf(ClassMetadata::class, $collection->getTypeClass());
    }

    public function testFavoritesReference(): void
    {
        $project = new Project('Test Project');
        $this->dm->persist($project);
        $this->dm->flush();

        $group = new Group('Test Group');
        $this->dm->persist($group);
        $this->dm->flush();

        $user = new FavoritesUser();
        $user->setName('favorites');
        $user->addFavorite($project);
        $user->addFavorite($group);

        $address = new Address();
        $address->setAddress('6512 Mercomatic Ct.');
        $address->setCity('Nashville');
        $address->setState('TN');
        $address->setZipcode('37209');

        $user->embed($address);
        $user->setEmbed($address);

        $document = new Phonenumber('6155139185');
        $user->embed($document);
        $user->setFavorite($project);

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->getDocumentCollection(FavoritesUser::class)->findOne(['name' => 'favorites']);
        self::assertTrue(isset($test['favorites'][0]['type']));
        self::assertEquals('project', $test['favorites'][0]['type']);
        self::assertEquals('group', $test['favorites'][1]['type']);
        self::assertTrue(isset($test['favorite']['_doctrine_class_name']));
        self::assertEquals(Project::class, $test['favorite']['_doctrine_class_name']);

        $user      = $this->dm->getRepository(FavoritesUser::class)->findOneBy(['name' => 'favorites']);
        $favorites = $user->getFavorites();
        self::assertInstanceOf(Project::class, $favorites[0]);
        self::assertInstanceOf(Group::class, $favorites[1]);

        $embedded = $user->getEmbedded();
        self::assertInstanceOf(Address::class, $embedded[0]);
        self::assertInstanceOf(Phonenumber::class, $embedded[1]);

        self::assertInstanceOf(Address::class, $user->getEmbed());
        self::assertInstanceOf(Project::class, $user->getFavorite());
    }

    public function testPreUpdate(): void
    {
        $product       = new PreUpdateTestProduct();
        $product->name = 'Product';

        $seller       = new PreUpdateTestSeller();
        $seller->name = 'Seller';

        $this->dm->persist($seller);
        $this->dm->persist($product);
        $this->dm->flush();

        $sellable          = new PreUpdateTestSellable();
        $sellable->product = $product;
        $sellable->seller  = $seller;

        $product->sellable = $sellable;

        $this->dm->flush();
        $this->dm->clear();

        $product = $this->dm->getRepository(PreUpdateTestProduct::class)->findOneBy(['name' => 'Product']);

        self::assertInstanceOf(PreUpdateTestSellable::class, $product->sellable);
        self::assertInstanceOf(PreUpdateTestProduct::class, $product->sellable->getProduct());
        self::assertInstanceOf(PreUpdateTestSeller::class, $product->sellable->getSeller());

        $product       = new PreUpdateTestProduct();
        $product->name = 'Product2';

        $this->dm->persist($product);
        $this->dm->flush();

        $sellable          = new PreUpdateTestSellable();
        $sellable->product = $product;
        $sellable->seller  = $this->dm->getRepository(PreUpdateTestSeller::class)->findOneBy(['name' => 'Seller']);

        $product->sellable = $sellable;

        $this->dm->flush();
        $this->dm->clear();

        $product = $this->dm->getRepository(PreUpdateTestProduct::class)->findOneBy(['name' => 'Product2']);
        self::assertEquals('Seller', $product->sellable->getSeller()->getName());
        self::assertEquals('Product2', $product->sellable->getProduct()->getName());
    }

    public function testSameCollectionTest(): void
    {
        $test1       = new SameCollection1();
        $test1->name = 'test1';
        $this->dm->persist($test1);

        $test2       = new SameCollection2();
        $test2->name = 'test2';
        $this->dm->persist($test2);
        $this->dm->flush();

        $test3       = new SameCollection3();
        $test3->name = 'test3';
        $this->dm->persist($test3);
        $this->dm->flush();

        $test = $this->dm->getRepository(SameCollection1::class)->findOneBy(['name' => 'test1']);
        self::assertNotNull($test);
        self::assertInstanceOf(SameCollection1::class, $test);

        $test = $this->dm->getRepository(SameCollection2::class)->findOneBy(['name' => 'test2']);
        self::assertNotNull($test);
        self::assertInstanceOf(SameCollection2::class, $test);

        $test = $this->dm->getRepository(SameCollection1::class)->findOneBy(['name' => 'test3']);
        self::assertNotNull($test);
        self::assertInstanceOf(SameCollection1::class, $test);

        $test = $this->dm->getRepository(SameCollection2::class)->findOneBy(['name' => 'test1']);
        self::assertNull($test);

        $qb     = $this->dm->createQueryBuilder([
            SameCollection1::class,
            SameCollection2::class,
        ]);
        $q      = $qb->getQuery();
        $result = $q->execute();
        self::assertInstanceOf(Iterator::class, $result);
        $test = $result->toArray();
        self::assertCount(3, $test);

        $test = $this->dm->getRepository(SameCollection1::class)->findAll();
        self::assertCount(2, $test);

        $qb     = $this->dm->createQueryBuilder(SameCollection1::class);
        $query  = $qb->getQuery();
        $result = $query->execute();
        self::assertInstanceOf(Iterator::class, $result);
        $test = $result->toArray();
        self::assertCount(2, $test);
    }

    public function testNotSameCollectionThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->dm->createQueryBuilder([
            User::class,
            Profile::class,
        ])->getQuery()->execute();
    }

    public function testEmbeddedNesting(): void
    {
        $test       = new EmbeddedTestLevel0();
        $test->name = 'test';

        $level1_0        = new EmbeddedTestLevel1();
        $level1_0->name  = 'test level1 #1';
        $test->level1[0] = $level1_0;

        $level1_1        = new EmbeddedTestLevel1();
        $level1_1->name  = 'test level1 #2';
        $test->level1[1] = $level1_1;

        $level2_0            = new EmbeddedTestLevel2();
        $level2_0->name      = 'test level2 #1';
        $level1_1->level2[0] = $level2_0;

        $level2_1            = new EmbeddedTestLevel2();
        $level2_1->name      = 'test level2 #2';
        $level1_1->level2[1] = $level2_1;

        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->clear();

        $check = $this->dm->getRepository(EmbeddedTestLevel0::class)->find($test->id);
        self::assertEquals('test', $check->name);
        self::assertInstanceOf(EmbeddedTestLevel1::class, $check->level1[0]);
        self::assertInstanceOf(EmbeddedTestLevel1::class, $check->level1[1]);
        self::assertInstanceOf(EmbeddedTestLevel2::class, $check->level1[1]->level2[0]);
        self::assertInstanceOf(EmbeddedTestLevel2::class, $check->level1[1]->level2[1]);
        self::assertCount(2, $check->level1);
        self::assertCount(2, $check->level1[1]->level2);
    }

    public function testEmbeddedInheritance(): void
    {
        // create a level0b (inherits from level0)
        $test       = new EmbeddedTestLevel0b();
        $test->name = 'test b';

        // embed a level1
        $level1          = new EmbeddedTestLevel1();
        $level1->name    = 'level 1';
        $test->oneLevel1 = $level1;

        // save the level0b
        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->clear();

        // fetch the level0b from db
        $test = $this->dm->find(EmbeddedTestLevel0b::class, $test->id);

        // add a level2 in the level0b.level1
        $level2                    = new EmbeddedTestLevel2();
        $level2->name              = 'level 2';
        $test->oneLevel1->level2[] = $level2;

        // OK, there is one level2
        self::assertCount(1, $test->oneLevel1->level2);

        // save again
        $this->dm->flush();
        $this->dm->clear();

        // fetch again
        $test = $this->dm->find(EmbeddedTestLevel0b::class, $test->id);

        // Uh oh, the level2 was not persisted!
        self::assertCount(1, $test->oneLevel1->level2);
    }

    public function testModifyGroupsArrayDirectly(): void
    {
        $account = new Account();
        $account->setName('Jon Test Account');

        $user = new User();
        $user->setUsername('jon333');
        $user->setPassword('changeme');
        $user->setAccount($account);

        $user->addGroup(new Group('administrator'));
        $user->addGroup(new Group('member'));
        $user->addGroup(new Group('moderator'));

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find(User::class, $user->getId());
        self::assertNotNull($user);

        // remove two of the groups and pass the groups back into the User
        $groups = $user->getGroups();
        unset($groups[0]);
        unset($groups[2]);

        $user->setGroups($groups);

        self::assertCount(1, $user->getGroups());

        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find(User::class, $user->getId());
        self::assertCount(1, $user->getGroups());
    }

    public function testReplaceEntireGroupsArray(): void
    {
        $account = new Account();
        $account->setName('Jon Test Account');

        $user = new User();
        $user->setUsername('jon333');
        $user->setPassword('changeme');
        $user->setAccount($account);

        $group2 = new Group('member');
        $user->addGroup(new Group('administrator'));
        $user->addGroup($group2);
        $user->addGroup(new Group('moderator'));

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find(User::class, $user->getId());
        self::assertNotNull($user);

        // Issue is collection must be initialized
        $groups = $user->getGroups();
        $groups[0]; // initialize collection

        // reffectively remove two of the groups
        //$user->getGroups()->clear();
        //$user->getGroups()->add($group2);

        $user->setGroups([$group2]);

        self::assertCount(1, $user->getGroups());

        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find(User::class, $user->getId());
        self::assertCount(1, $user->getGroups());
    }

    public function testFunctionalParentAssociations(): void
    {
        $a                    = new ParentAssociationTestA('a');
        $a->child             = new ParentAssociationTestB('b');
        $a->child->children[] = new ParentAssociationTestC('c1');
        $a->child->children[] = new ParentAssociationTestC('c2');
        $this->dm->persist($a);
        $this->dm->flush();

        $unitOfWork = $this->dm->getUnitOfWork();

        [$mapping, $document] = $unitOfWork->getParentAssociation($a->child->children[0]);
        self::assertSame($a->child, $document);

        [$mapping, $document] = $unitOfWork->getParentAssociation($a->child->children[1]);
        self::assertSame($a->child, $document);

        [$mapping, $document] = $unitOfWork->getParentAssociation($a->child);
        self::assertSame($a, $document);
    }
}

/** @ODM\Document */
class ParentAssociationTestA
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;
    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    public $name;

    /**
     * @ODM\EmbedOne
     *
     * @var object|null
     */
    public $child;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}

/** @ODM\EmbeddedDocument */
class ParentAssociationTestB
{
    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $name;
    /**
     * @ODM\EmbedMany
     *
     * @var Collection<int, object>|array<object>
     */
    public $children = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}

/** @ODM\EmbeddedDocument */
class ParentAssociationTestC
{
    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    public $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
