<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\PersistentCollection;
use Documents\Bars\Bar;
use Documents\Bars\Location;
use Documents\User;
use Documents\Account;
use Documents\Phonenumber;
use Documents\Employee;
use Documents\Manager;
use Documents\Address;
use Documents\Group;
use Documents\Project;
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
use Documents\Album;
use Documents\Song;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class FunctionalTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function provideUpsertObjects()
    {
        return array(
            array('Documents\\UserUpsert', new \MongoId('4f18f593acee41d724000005'), 'user'),
            array('Documents\\UserUpsertIdStrategyNone', 'jwage', 'user'),
            array('Documents\\UserUpsertChild', new \MongoId('4f18f593acee41d724000005'), 'child')
        );
    }

    /**
     * @dataProvider provideUpsertObjects
     */
    public function testUpsertObject($className, $id, $discriminator)
    {
        $user = new $className();
        $user->id = (string) $id;
        $user->username = 'test';
        $user->count = 1;
        $group = new \Documents\Group('Group');
        $user->groups = array($group);
        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $check = $this->dm->getDocumentCollection($className)->findOne(array('_id' => $id));
        $this->assertNotNull($check);
        $this->assertEquals((string) $id, (string) $check['_id']);
        $this->assertEquals($group->getId(), (string) $check['groups'][0]['$id']);
        $this->assertEquals($discriminator, $check['discriminator']);

        $group2 = new \Documents\Group('Group');

        $user = new $className();
        $user->id = $id;
        $user->hits = 5;
        $user->count = 2;
        $user->groups = array($group2);
        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $check = $this->dm->getDocumentCollection($className)->findOne(array('_id' => $id));
        $this->assertEquals($discriminator, $check['discriminator']);
        $this->assertEquals(3, $check['count']);
        $this->assertEquals(5, $check['hits']);
        $this->assertEquals(2, count($check['groups']));
        $this->assertEquals($group->getId(), (string) $check['groups'][0]['$id']);
        $this->assertEquals($group2->getId(), (string) $check['groups'][1]['$id']);
        $this->assertTrue(isset($check['username']));
        $this->assertEquals('test', $check['username']);

        $user = new $className();
        $user->id = $id;
        $user->hits = 100;
        $this->dm->persist($user);
        $this->dm->flush();

        $check = $this->dm->getDocumentCollection($className)->findOne(array('_id' => $id));
        $this->assertEquals($discriminator, $check['discriminator']);
        $this->assertEquals(3, $check['count']);
        $this->assertEquals(100, $check['hits']);
        $this->assertEquals(2, count($check['groups']));
        $this->assertEquals($group->getId(), (string) $check['groups'][0]['$id']);
        $this->assertEquals($group2->getId(), (string) $check['groups'][1]['$id']);
        $this->assertTrue(isset($check['username']));
        $this->assertEquals('test', $check['username']);
    }

    public function testInheritedAssociationMappings()
    {
        $class = $this->dm->getClassMetadata('Documents\UserUpsertChild');
        $this->assertTrue(isset($class->associationMappings['groups']));
    }

    public function testFlushSingleDocument()
    {
        $user1 = new \Documents\ForumUser();
        $user1->username = 'romanb';
        $user2 = new \Documents\ForumUser();
        $user2->username = 'jwage';
        $this->dm->persist($user1);
        $this->dm->persist($user2);
        $this->dm->flush();

        $user1->username = 'changed';
        $user2->username = 'changed';
        $this->dm->flush($user1);

        $check = $this->dm->getDocumentCollection('Documents\ForumUser')->find(array('username' => 'jwage'));
        $this->assertNotNull($check);

        $check = $this->dm->getDocumentCollection('Documents\ForumUser')->find(array('username' => 'changed'));
        $this->assertNotNull($check);
    }

    public function testNestedCategories()
    {
        $root = new \Documents\Category('Root');
        $child1 = new \Documents\SubCategory('Child 1');
        $child2 = new \Documents\SubCategory('Child 2');
        $child1->addChild($child2);
        $root->addChild($child1);

        $this->dm->persist($root);
        $this->dm->flush();

        $child1->setName('Child 1 Changed');
        $child2->setName('Child 2 Changed');
        $root->setName('Root Changed');
        $this->dm->flush();

        $test = $this->dm->getDocumentCollection('Documents\Category')->findOne();
        $this->assertEquals('Child 1 Changed', $test['children'][0]['name']);
        $this->assertEquals('Child 2 Changed', $test['children'][0]['children'][0]['name']);
        $this->assertEquals('Root Changed', $test['name']);
    }

    public function testManyEmbedded()
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

        $test = $this->dm->getDocumentCollection('Documents\Album')->findOne(array('name' => 'Jon'));
        $this->assertEquals('Song #1 Changed', $test['songs'][0]['name']);

        $album->setName('jwage');
        $songs[1]->setName('ok');
        $songs->add(new Song('Song #4'));
        $songs->add(new Song('Song #5'));
        unset($songs[0]);
        $this->dm->flush();

        $test = $this->dm->getDocumentCollection('Documents\Album')->findOne(array('name' => 'jwage'));

        $this->assertEquals('jwage', $test['name']);
        $this->assertEquals('ok', $test['songs'][0]['name']);
        $this->assertEquals('Song #3', $test['songs'][1]['name']);
        $this->assertEquals('Song #4', $test['songs'][2]['name']);
        $this->assertEquals('Song #5', $test['songs'][3]['name']);
        $this->assertEquals(4, count($test['songs']));

        $songs->clear();
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->getDocumentCollection('Documents\Album')->findOne(array('name' => 'jwage'));
        $this->assertFalse(isset($test['songs']));
    }

    public function testNewEmbedded()
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

        $test = $this->dm->getDocumentCollection('Documents\Project')->findOne(array('name' => 'Project'));

        $this->assertEquals('New Sub-City', $test['address']['subAddress']['city']);
        $this->assertEquals('New City', $test['address']['city']);
    }

    public function testPersistingNewDocumentWithOnlyOneReference()
    {
        $server = new \Documents\GuestServer();
        $server->name = 'test';
        $this->dm->persist($server);
        $this->dm->flush();
        $id = $server->id;

        $this->dm->clear();

        $server = $this->dm->getReference('Documents\GuestServer', $id);

        $agent = new \Documents\Agent();
        $agent->server = $server;
        $this->dm->persist($agent);
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->getDocumentCollection('Documents\Agent')->findOne();

        $this->assertEquals('servers', $test['server']['$ref']);
        $this->assertTrue(isset($test['server']['$id']));
        $this->assertEquals(DOCTRINE_MONGODB_DATABASE, $test['server']['$db']);
        $this->assertEquals('server_guest', $test['server']['_doctrine_class_name']);
    }

    public function testCollection()
    {
        $user = new \Documents\User();
        $user->setUsername('joncolltest');
        $user->log(array('test'));
        $user->log(array('test'));
        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $coll = $this->dm->getDocumentCollection('Documents\User');
        $document = $coll->findOne(array('username' => 'joncolltest'));
        $this->assertEquals(2, count($document['logs']));

        $document = $this->dm->getRepository('Documents\User')->findOneBy(array('username' => 'joncolltest'));
        $this->assertEquals(2, count($document->getLogs()));
        $document->log(array('test'));
        $this->dm->flush();
        $this->dm->clear();

        $document = $this->dm->getRepository('Documents\User')->findOneBy(array('username' => 'joncolltest'));
        $this->assertEquals(3, count($document->getLogs()));
        $document->setLogs(array('ok', 'test'));
        $this->dm->flush();
        $this->dm->clear();

        $document = $this->dm->getRepository('Documents\User')->findOneBy(array('username' => 'joncolltest'));
        $this->assertEquals(array('ok', 'test'), $document->getLogs());
    }

    public function testSameObjectValuesInCollection()
    {
        $user = new User();
        $user->setUsername('testing');
        $user->getPhonenumbers()->add(new Phonenumber('6155139185'));
        $user->getPhonenumbers()->add(new Phonenumber('6155139185'));
        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->getRepository('Documents\User')->findOneBy(array('username' => 'testing'));
        $this->assertEquals(2, count($user->getPhonenumbers()));
    }

    public function testIncrement()
    {
        $user = new User();
        $user->setUsername('jon');
        $user->setCount(100);
        $user->setFloatCount(100);

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->getRepository('Documents\User')->findOneBy(array('username' => 'jon'));

        $user->incrementCount(5);
        $user->incrementFloatCount(5);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->getRepository('Documents\User')->findOneBy(array('username' => 'jon'));
        $this->assertSame(105, $user->getCount());
        $this->assertSame(105.0, $user->getFloatCount());

        $user->setCount(50);
        $user->setFloatCount(50);

        $this->dm->flush();
        $this->dm->clear();
        $user = $this->dm->getRepository('Documents\User')->findOneBy(array('username' => 'jon'));
        $this->assertSame(50, $user->getCount());
        $this->assertSame(50.0, $user->getFloatCount());
    }

    public function testIncrementWithFloat()
    {
        $user = new User();
        $user->setUsername('jon');
        $user->setCount(100);
        $user->setFloatCount(100);

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->getRepository('Documents\User')->findOneBy(array('username' => 'jon'));

        $user->incrementCount(1.337);
        $user->incrementFloatCount(1.337);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->getRepository('Documents\User')->findOneBy(array('username' => 'jon'));
        $this->assertSame(101, $user->getCount());
        $this->assertSame(101.337, $user->getFloatCount());

        $user->incrementCount(9.163);
        $user->incrementFloatCount(9.163);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->getRepository('Documents\User')->findOneBy(array('username' => 'jon'));
        $this->assertSame(110, $user->getCount());
        $this->assertSame(110.5, $user->getFloatCount());
    }

    public function testIncrementSetsNull()
    {
        $user = new User();
        $user->setUsername('jon');
        $user->setCount(10);
        $user->setFloatCount(10);

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->getRepository('Documents\User')->findOneBy(array('username' => 'jon'));
        $this->assertSame(10, $user->getCount());
        $this->assertSame(10.0, $user->getFloatCount());

        $user->incrementCount(1);
        $user->incrementFloatCount(1);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->getRepository('Documents\User')->findOneBy(array('username' => 'jon'));
        $this->assertSame(11, $user->getCount());
        $this->assertSame(11.0, $user->getFloatCount());

        $user->setCount(null);
        $user->setFloatCount(null);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->getRepository('Documents\User')->findOneBy(array('username' => 'jon'));
        $this->assertSame(null, $user->getCount());
        $this->assertSame(null, $user->getFloatCount());
    }

    public function testTest()
    {
        $employee = new Employee();
        $employee->setName('Employee');
        $employee->setSalary(50000.00);
        $employee->setStarted(new \DateTime());

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
        $manager->setStarted(new \DateTime());
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

        $result = $this->dm->createQueryBuilder('Documents\Manager')
            ->field('name')->equals('Manager')
            ->hydrate(false)
            ->getQuery()
            ->getSingleResult();

        $this->assertEquals(200000.00, $result['salary']);
        $this->assertEquals(2, count($result['projects']));
        $this->assertEquals(1, count($result['notes']));
        $this->assertEquals('Gave user 100k a year raise', $result['notes'][0]);
    }

    public function testNotAnnotatedDocument()
    {
        $this->dm->getDocumentCollection('Documents\Functional\NotAnnotatedDocument')->drop();

        $test = new NotAnnotatedDocument();
        $test->field = 'test';
        $test->transientField = 'w00t';
        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->find('Documents\Functional\NotAnnotatedDocument', $test->id);
        $this->assertNotNull($test);
        $this->assertFalse(isset($test->transientField));
    }

    public function testNullFieldValuesAllowed()
    {
        $this->dm->getDocumentCollection('Documents\Functional\NullFieldValues')->drop();

        $test = new NullFieldValues();
        $test->field = null;
        $this->dm->persist($test);
        $this->dm->flush();

        $document = $this->dm->createQueryBuilder('Documents\Functional\NullFieldValues')
            ->hydrate(false)
            ->getQuery()
            ->getSingleResult();

        $this->assertNotNull($document);
        $this->assertNull($document['field']);

        $document = $this->dm->find('Documents\Functional\NullFieldValues', $test->id);
        $document->field = 'test';
        $this->dm->flush();
        $this->dm->clear();

        $document = $this->dm->find('Documents\Functional\NullFieldValues', $test->id);
        $this->assertEquals('test', $document->field);
        $document->field = null;
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->createQueryBuilder('Documents\Functional\NullFieldValues')
            ->hydrate(false)
            ->getQuery()
            ->getSingleResult();
        $this->assertNull($test['field']);
        $this->assertFalse(isset($test['transientField']));
    }

    public function testSimplerEmbedAndReference()
    {
        $class = $this->dm->getClassMetadata('Documents\Functional\SimpleEmbedAndReference');
        $this->assertEquals('many', $class->fieldMappings['embedMany']['type']);
        $this->assertEquals('one', $class->fieldMappings['embedOne']['type']);
        $this->assertEquals('many', $class->fieldMappings['referenceMany']['type']);
        $this->assertEquals('one', $class->fieldMappings['referenceOne']['type']);
    }

    public function testNotSavedFields()
    {
        $collection = $this->dm->getDocumentCollection('Documents\Functional\NotSaved');
        $collection->drop();
        $test = array(
            'name' => 'Jonathan Wage',
            'notSaved' => 'test'
        );
        $collection->insert($test);
        $notSaved = $this->dm->find('Documents\Functional\NotSaved', $test['_id']);
        $this->assertEquals('Jonathan Wage', $notSaved->name);
        $this->assertEquals('test', $notSaved->notSaved);

        $notSaved = new NotSaved();
        $notSaved->name = 'Roman Borschel';
        $notSaved->notSaved = 'test';
        $this->dm->persist($notSaved);
        $this->dm->flush();
        $this->dm->clear();

        $notSaved = $collection->findOne(array('name' => 'Roman Borschel'));
        $this->assertEquals('Roman Borschel', $notSaved['name']);
        $this->assertFalse(isset($notSaved['notSaved']));
    }

    /**
     * @expectedException \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function testTypeClassMissing()
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

        /** @var $test FavoritesUser */
        $test = $this->dm->find('Documents\Functional\FavoritesUser', $user->getId());

        /** @var $collection PersistentCollection */
        $collection = $test->getFavorites();
        $collection->getTypeClass();
    }

    public function testTypeClass()
    {
        $bar = new Bar("Jon's Pub");
        $bar->addLocation(new Location('West Nashville'));
        $bar->addLocation(new Location('East Nashville'));
        $bar->addLocation(new Location('North Nashville'));
        $this->dm->persist($bar);
        $this->dm->flush();
        $this->dm->clear();

        /** @var $test Bar */
        $test = $this->dm->find('Documents\Bars\Bar', $bar->getId());

        /** @var $collection PersistentCollection */
        $collection = $test->getLocations();
        $this->assertInstanceOf('Doctrine\ODM\MongoDB\Mapping\ClassMetadata', $collection->getTypeClass());
    }

    public function testFavoritesReference()
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

        $test = $this->dm->getDocumentCollection('Documents\Functional\FavoritesUser')->findOne(array('name' => 'favorites'));
        $this->assertTrue(isset($test['favorites'][0]['type']));
        $this->assertEquals('project', $test['favorites'][0]['type']);
        $this->assertEquals('group', $test['favorites'][1]['type']);
        $this->assertTrue(isset($test['favorite']['_doctrine_class_name']));
        $this->assertEquals('Documents\Project', $test['favorite']['_doctrine_class_name']);

        $user = $this->dm->getRepository('Documents\Functional\FavoritesUser')->findOneBy(array('name' => 'favorites'));
        $favorites = $user->getFavorites();
        $this->assertInstanceOf('Documents\Project', $favorites[0]);
        $this->assertInstanceOf('Documents\Group', $favorites[1]);

        $embedded = $user->getEmbedded();
        $this->assertInstanceOf('Documents\Address', $embedded[0]);
        $this->assertInstanceOf('Documents\Phonenumber', $embedded[1]);

        $this->assertInstanceOf('Documents\Address', $user->getEmbed());
        $this->assertInstanceOf('Documents\Project', $user->getFavorite());
    }

    public function testPreUpdate()
    {
        $product = new PreUpdateTestProduct();
        $product->name = 'Product';

        $seller = new PreUpdateTestSeller();
        $seller->name = 'Seller';

        $this->dm->persist($seller);
        $this->dm->persist($product);
        $this->dm->flush();

        $sellable = new PreUpdateTestSellable();
        $sellable->product = $product;
        $sellable->seller = $seller;

        $product->sellable = $sellable;

        $this->dm->flush();
        $this->dm->clear();

        $product = $this->dm->getRepository('Documents\Functional\PreUpdateTestProduct')->findOneBy(array('name' => 'Product'));

        $this->assertInstanceOf('Documents\Functional\PreUpdateTestSellable', $product->sellable);
        $this->assertInstanceOf('Documents\Functional\PreUpdateTestProduct', $product->sellable->getProduct());
        $this->assertInstanceOf('Documents\Functional\PreUpdateTestSeller', $product->sellable->getSeller());

        $product = new PreUpdateTestProduct();
        $product->name = 'Product2';

        $this->dm->persist($product);
        $this->dm->flush();

        $sellable = new PreUpdateTestSellable();
        $sellable->product = $product;
        $sellable->seller = $this->dm->getRepository('Documents\Functional\PreUpdateTestSeller')->findOneBy(array('name' => 'Seller'));

        $product->sellable = $sellable;

        $this->dm->flush();
        $this->dm->clear();

        $product = $this->dm->getRepository('Documents\Functional\PreUpdateTestProduct')->findOneBy(array('name' => 'Product2'));
        $this->assertEquals('Seller', $product->sellable->getSeller()->getName());
        $this->assertEquals('Product2', $product->sellable->getProduct()->getName());
    }

    public function testSameCollectionTest()
    {
        $test1 = new SameCollection1();
        $test1->name = 'test1';
        $this->dm->persist($test1);

        $test2 = new SameCollection2();
        $test2->name = 'test2';
        $this->dm->persist($test2);
        $this->dm->flush();

        $test3 = new SameCollection3();
        $test3->name = 'test3';
        $this->dm->persist($test3);
        $this->dm->flush();

        $test = $this->dm->getRepository('Documents\Functional\SameCollection1')->findOneBy(array('name' => 'test1'));
        $this->assertNotNull($test);
        $this->assertInstanceOf('Documents\Functional\SameCollection1', $test);

        $test = $this->dm->getRepository('Documents\Functional\SameCollection2')->findOneBy(array('name' => 'test2'));
        $this->assertNotNull($test);
        $this->assertInstanceOf('Documents\Functional\SameCollection2', $test);

        $test = $this->dm->getRepository('Documents\Functional\SameCollection1')->findOneBy(array('name' => 'test3'));
        $this->assertNotNull($test);
        $this->assertInstanceOf('Documents\Functional\SameCollection1', $test);

        $test = $this->dm->getRepository('Documents\Functional\SameCollection2')->findOneBy(array('name' => 'test1'));
        $this->assertNull($test);

        $qb = $this->dm->createQueryBuilder(array(
            'Documents\Functional\SameCollection1',
            'Documents\Functional\SameCollection2')
        );
        $q = $qb->getQuery();
        $test = $q->execute();
        $this->assertEquals(3, count($test));

        $test = $this->dm->getRepository('Documents\Functional\SameCollection1')->findAll();
        $this->assertEquals(2, count($test));

        $qb = $this->dm->createQueryBuilder('Documents\Functional\SameCollection1');
        $query = $qb->getQuery();
        $test = $query->execute();
        $this->assertEquals(2, count($test));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testNotSameCollectionThrowsException()
    {
        $test = $this->dm->createQueryBuilder(array(
             'Documents\User',
             'Documents\Profile')
         )->getQuery()->execute();
    }

    public function testEmbeddedNesting()
    {
        $test = new EmbeddedTestLevel0();
        $test->name = 'test';

        $level1_0 = new EmbeddedTestLevel1();
        $level1_0->name = 'test level1 #1';
        $test->level1[0] = $level1_0;

        $level1_1 = new EmbeddedTestLevel1();
        $level1_1->name = 'test level1 #2';
        $test->level1[1] = $level1_1;

        $level2_0 = new EmbeddedTestLevel2();
        $level2_0->name = 'test level2 #1';
        $level1_1->level2[0] = $level2_0;

        $level2_1 = new EmbeddedTestLevel2();
        $level2_1->name = 'test level2 #2';
        $level1_1->level2[1] = $level2_1;

        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->clear();

        $check = $this->dm->getRepository('Documents\Functional\EmbeddedTestLevel0')->find($test->id);
        $this->assertEquals('test', $check->name);
        $this->assertInstanceOf('Documents\Functional\EmbeddedTestLevel1', $check->level1[0]);
        $this->assertInstanceOf('Documents\Functional\EmbeddedTestLevel1', $check->level1[1]);
        $this->assertInstanceOf('Documents\Functional\EmbeddedTestLevel2', $check->level1[1]->level2[0]);
        $this->assertInstanceOf('Documents\Functional\EmbeddedTestLevel2', $check->level1[1]->level2[1]);
        $this->assertEquals(2, count($check->level1));
        $this->assertEquals(2, count($check->level1[1]->level2));
    }

    public function testEmbeddedInheritance()
    {
        // create a level0b (inherits from level0)
        $test = new EmbeddedTestLevel0b();
        $test->name = 'test b';

        // embed a level1
        $level1 = new EmbeddedTestLevel1();
        $level1->name = 'level 1';
        $test->oneLevel1 = $level1;

        // save the level0b
        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->clear();

        // fetch the level0b from db
        $test = $this->dm->find('Documents\Functional\EmbeddedTestLevel0b', $test->id);

        // add a level2 in the level0b.level1
        $level2 = new EmbeddedTestLevel2();
        $level2->name = 'level 2';
        $test->oneLevel1->level2[] = $level2;

        // OK, there is one level2
        $this->assertEquals(1, count($test->oneLevel1->level2));

        // save again
        $this->dm->flush();
        $this->dm->clear();

        // fetch again
        $test = $this->dm->find('Documents\Functional\EmbeddedTestLevel0b', $test->id);

        // Uh oh, the level2 was not persisted!
        $this->assertEquals(1, count($test->oneLevel1->level2));
    }

    public function testModifyGroupsArrayDirectly()
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

        $user = $this->dm->find('Documents\User', $user->getId());
        $this->assertNotNull($user);

        // remove two of the groups and pass the groups back into the User
        $groups = $user->getGroups();
        unset($groups[0]);
        unset($groups[2]);

        $user->setGroups($groups);

        $this->assertEquals(1, count($user->getGroups()));

        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find('Documents\User', $user->getId());
        $this->assertEquals(1, count($user->getGroups()));
    }

    public function testReplaceEntireGroupsArray()
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

        $user = $this->dm->find('Documents\User', $user->getId());
        $this->assertNotNull($user);

        // Issue is collection must be initialized
        $groups = $user->getGroups();
        $groups[0]; // initialize collection

        // reffectively remove two of the groups
        //$user->getGroups()->clear();
        //$user->getGroups()->add($group2);

        $user->setGroups(array($group2));

        $this->assertEquals(1, count($user->getGroups()));

        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find('Documents\User', $user->getId());
        $this->assertEquals(1, count($user->getGroups()));
    }

    public function testFunctionalParentAssociations()
    {
        $a = new ParentAssociationTestA('a');
        $a->child = new ParentAssociationTestB('b');
        $a->child->children[] = new ParentAssociationTestC('c1');
        $a->child->children[] = new ParentAssociationTestC('c2');
        $this->dm->persist($a);
        $this->dm->flush();

        $unitOfWork = $this->dm->getUnitOfWork();

        list($mapping, $document) = $unitOfWork->getParentAssociation($a->child->children[0]);
        $this->assertSame($a->child, $document);

        list($mapping, $document) = $unitOfWork->getParentAssociation($a->child->children[1]);
        $this->assertSame($a->child, $document);

        list($mapping, $document) = $unitOfWork->getParentAssociation($a->child);
        $this->assertSame($a, $document);
    }
}

/** @ODM\Document */
class ParentAssociationTestA
{
    /** @ODM\Id */
    public $id;
    /** @ODM\Field(type="string") */
    public $name;
    /** @ODM\EmbedOne */
    public $child;
    public function __construct($name)
    {
        $this->name = $name;
    }
}

/** @ODM\EmbeddedDocument */
class ParentAssociationTestB
{
    /** @ODM\Field(type="string") */
    public $name;
    /** @ODM\EmbedMany */
    public $children = array();
    public function __construct($name)
    {
        $this->name = $name;
    }
}

/** @ODM\EmbeddedDocument */
class ParentAssociationTestC
{
    /** @ODM\Field(type="string") */
    public $name;
    public function __construct($name)
    {
        $this->name = $name;
    }
}
