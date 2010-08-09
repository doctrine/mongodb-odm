<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

require_once __DIR__ . '/../../../../../TestInit.php';

use Documents\User,
    Documents\Phonenumber,
    Documents\Employee,
    Documents\Manager,
    Documents\Address,
    Documents\Group,
    Documents\Project,
    Documents\Agent,
    Documents\Server,
    Documents\GuestServer,
    Documents\Functional\AlsoLoad,
    Documents\Functional\EmbeddedTestLevel0,
    Documents\Functional\EmbeddedTestLevel1,
    Documents\Functional\EmbeddedTestLevel2,
    Documents\Functional\FavoritesUser,
    Documents\Functional\NotAnnotatedDocument,
    Documents\Functional\NotSaved,
    Documents\Functional\NullFieldValues,
    Documents\Functional\PreUpdateTestProduct,
    Documents\Functional\PreUpdateTestSellable,
    Documents\Functional\PreUpdateTestSeller,
    Documents\Functional\SameCollection1,
    Documents\Functional\SameCollection2,
    Documents\Functional\SimpleEmbedAndReference;

class FunctionalTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    /*
    public function testNewEmbedded()
    {
        $address = new Address();
        $address->setCity('Nashville');
        $address->count = 1;

        $user = new Project('Test');
        $user->setAddress($address);
        $this->dm->persist($user);
        $this->dm->flush();

        $address->count = 5;
        $address->setCity('Atlanta');
        $this->dm->flush();

        $test = $this->dm->getDocumentCollection('Documents\Project')->findOne(array('name' => 'Test'));
        $this->assertEquals('Atlanta', $test['address']['city']);
        $this->assertEquals(5, $test['address']['count']);
    }
    */

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
        $this->assertEquals('doctrine_odm_tests', $test['server']['$db']);
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

        $document = $this->dm->findOne('Documents\User', array('username' => 'joncolltest'));
        $this->assertEquals(2, count($document->getLogs()));
        $document->log(array('test'));
        $this->dm->flush();
        $this->dm->clear();

        $document = $this->dm->findOne('Documents\User', array('username' => 'joncolltest'));
        $this->assertEquals(3, count($document->getLogs()));
        $document->setLogs(array('ok', 'test'));
        $this->dm->flush();
        $this->dm->clear();

        $document = $this->dm->findOne('Documents\User', array('username' => 'joncolltest'));
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

        $user = $this->dm->findOne('Documents\User', array('username' => 'testing'));
        $this->assertEquals(2, count($user->getPhonenumbers()));
    }

    public function testSearchEmbeddedDocumentDQL()
    {
        $user = new \Documents\User();
        $user->setUsername('jwage');
        $address = new \Documents\Address();
        $address->setCity('nashville');
        $user->setAddress($address);

        $user->addPhonenumber(new \Documents\Phonenumber('6155139185'));
        $this->dm->persist($user);
        $this->dm->flush();

        $this->assertNotNull($this->dm->find('Documents\User', array('phonenumbers.phonenumber' => '6155139185'))->getSingleResult());

        $query = $this->dm->query("find all Documents\User where phonenumbers.phonenumber = '6155139185'");
        $this->assertNotNull($query->getSingleResult());

        $query = $this->dm->query("find all Documents\User where phonenumbers.phonenumber = ?", array('6155139185'));
        $this->assertNotNull($query->getSingleResult());

        $query = $this->dm->query('find all Documents\User where address.city = ?', 'nashville');
        $this->assertNotNull($query->getSingleResult());

        $query = $this->dm->query('find all Documents\User where phonenumbers size :size', array(':size' => 1));
        $this->assertNotNull($query->getSingleResult());

        $query = $this->dm->query('find all Documents\User where phonenumbers size ?', 1);
        $this->assertNotNull($query->getSingleResult());

        $query = $this->dm->query('find all Documents\User where phonenumbers size 1');
        $this->assertNotNull($query->getSingleResult());

        $this->dm->query('update Documents\User set address.city = ?', 'atlanta')
            ->execute();

        $query = $this->dm->query('find all Documents\User where address.city = ?', 'atlanta');
        $this->assertNotNull($query->getSingleResult());

        $this->dm->query('remove Documents\User where address.city = ?', 'atlanta')
            ->execute();

        $query = $this->dm->query('find all Documents\User where address.city = ?', 'atlanta');
        $this->assertNull($query->getSingleResult());

        $this->dm->query("insert Documents\User set username = 'jonwage', address.city = 'atlanta'")
            ->execute();
        $document = $this->dm->getDocumentCollection('Documents\User')->findOne(array('username' => 'jonwage'));
        $this->assertEquals('atlanta', $document['address']['city']);
        $this->assertEquals('jonwage', $document['username']);
    }

    public function testFunctionalDQLQuery()
    {
        $user = new \Documents\User();
        $user->setUsername('jwage');
        $this->dm->persist($user);
        $this->dm->flush();

        $query = $this->dm->query("find all Documents\User where username = :username", array(':username' => 'jwage'));
        $this->assertNotNull($query->getSingleResult());
    }

    public function testIncrement()
    {
        $user = new User();
        $user->setUsername('jon');
        $user->setCount(100);

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->findOne('Documents\User', array('username' => 'jon'));

        $user->incrementCount(5);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->findOne('Documents\User', array('username' => 'jon'));
        $this->assertEquals(105, $user->getCount());

        $user->setCount(50);

        $this->dm->flush();
        $this->dm->clear();
        $user = $this->dm->findOne('Documents\User', array('username' => 'jon'));
        $this->assertEquals(50, $user->getCount());
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

        $results = $this->dm->find('Documents\Manager', array('name' => 'Manager'))
            ->hydrate(false)
            ->getResults();
        $result = current($results);

        $this->assertEquals(1, count($results));
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
        $this->dm->flush($test);
        $this->dm->clear();

        $test = $this->dm->find('Documents\Functional\NotAnnotatedDocument')
            ->getSingleResult();
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

        $test = $this->dm->find('Documents\Functional\NullFieldValues')
            ->hydrate(false)
            ->getResults();
        $document = current($test);
        $this->assertNotNull($test);
        $this->assertNull($document['field']);

        $document = $this->dm->find('Documents\Functional\NullFieldValues')
            ->getSingleResult();
        $document->field = 'test';
        $this->dm->flush();
        $this->dm->clear();

        $document = $this->dm->find('Documents\Functional\NullFieldValues')
            ->getSingleResult();
        $this->assertEquals('test', $document->field);
        $document->field = null;
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->find('Documents\Functional\NullFieldValues')
            ->hydrate(false)
            ->getSingleResult();
        $this->assertNull($test['field']);
        $this->assertFalse(isset($test['transientField']));
    }

    public function testAlsoLoadOnProperty()
    {
        $collection = $this->dm->getDocumentCollection('Documents\Functional\AlsoLoad');
        $collection->drop();
        $collection->insert(array(
            'bar' => 'w00t'
        ));
        $document = $this->dm->find('Documents\Functional\AlsoLoad', array('bar' => 'w00t'))
            ->getSingleResult();
        $this->assertEquals('w00t', $document->foo);

        $collection->insert(array(
            'foo' => 'cool'
        ));
        $document = $this->dm->find('Documents\Functional\AlsoLoad', array('bar' => 'w00t'))
            ->getSingleResult();
        $this->assertNotNull($document->foo);

        $collection->insert(array(
            'zip' => 'test'
        ));
        $document = $this->dm->find('Documents\Functional\AlsoLoad', array('bar' => 'w00t'))
            ->getSingleResult();
        $this->assertNotNull($document->foo);
    }

    public function testAlsoLoadOnMethod()
    {
        $collection = $this->dm->getDocumentCollection('Documents\Functional\AlsoLoad');
        $collection->drop();
        $collection->insert(array(
            'name' => 'Jonathan Wage',
            'test1' => 'test1'
        ));
        $document = $this->dm->find('Documents\Functional\AlsoLoad', array('name' => 'Jonathan Wage'))
            ->getSingleResult();
        $this->assertEquals('Jonathan', $document->firstName);
        $this->assertEquals('Wage', $document->lastName);
        $this->assertEquals('test1', $document->test);

        $collection->insert(array(
            'fullName' => 'Jonathan Wage',
            'test2' => 'test2'
        ));
        $document = $this->dm->find('Documents\Functional\AlsoLoad', array('fullName' => 'Jonathan Wage'))
            ->getSingleResult();
        $this->assertEquals('Jonathan', $document->firstName);
        $this->assertEquals('Wage', $document->lastName);
        $this->assertEquals('test2', $document->test);

        $collection->insert(array(
            'test' => 'test'
        ));
        $document = $this->dm->find('Documents\Functional\AlsoLoad', array('test' => 'test'))
            ->getSingleResult();
        $this->assertEquals('test', $document->test);
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
        $collection->insert(array(
            'name' => 'Jonathan Wage',
            'notSaved' => 'test'
        ));
        $notSaved = $this->dm->findOne('Documents\Functional\NotSaved');
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

        $user = $this->dm->findOne('Documents\Functional\FavoritesUser', array('name' => 'favorites'));
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

        $product = $this->dm->findOne('Documents\Functional\PreUpdateTestProduct', array('name' => 'Product'));
        $this->assertInstanceOf('Documents\Functional\PreUpdateTestProduct', $product->sellable->getProduct());
        $this->assertInstanceOf('Documents\Functional\PreUpdateTestSeller', $product->sellable->getSeller());

        $product = new PreUpdateTestProduct();
        $product->name = 'Product2';

        $this->dm->persist($product);
        $this->dm->flush();

        $sellable = new PreUpdateTestSellable();
        $sellable->product = $product;
        $sellable->seller = $this->dm->findOne('Documents\Functional\PreUpdateTestSeller', array('name' => 'Seller'));

        $product->sellable = $sellable;

        $this->dm->flush();
        $this->dm->clear();

        $product = $this->dm->findOne('Documents\Functional\PreUpdateTestProduct', array('name' => 'Product2'));
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

        $test = $this->dm->findOne('Documents\Functional\SameCollection1', array('name' => 'test1'));
        $this->assertNotNull($test);
        $this->assertInstanceOf('Documents\Functional\SameCollection1', $test);

        $test = $this->dm->findOne('Documents\Functional\SameCollection2', array('name' => 'test2'));
        $this->assertNotNull($test);
        $this->assertInstanceOf('Documents\Functional\SameCollection2', $test);

        $test = $this->dm->findOne('Documents\Functional\SameCollection2', array('name' => 'test1'));
        $this->assertNull($test);

        $test = $this->dm->find(array(
            'Documents\Functional\SameCollection1',
            'Documents\Functional\SameCollection2')
        )->getResults();
        $this->assertEquals(2, count($test));

        $q = $this->dm->createQuery(array(
            'Documents\Functional\SameCollection1',
            'Documents\Functional\SameCollection2')
        );
        $test = $q->execute();
        $this->assertEquals(2, count($test));

        $test = $this->dm->find('Documents\Functional\SameCollection1')->getResults();
        $this->assertEquals(1, count($test));

        $test = $this->dm->createQuery('Documents\Functional\SameCollection1')->execute();
        $this->assertEquals(1, count($test));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testNotSameCollectionThrowsException()
    {
        $test = $this->dm->createQuery(array(
             'Documents\User',
             'Documents\Profile')
         )->execute();
    }

    public function testEmbedded()
    {
        $test = new EmbeddedTestLevel0();
        $test->name = 'test';

        $level1 = new EmbeddedTestLevel1();
        $level1->name = 'test level1 #1';
        $test->level1[] = $level1;

        $level2 = new EmbeddedTestLevel2();
        $level2->name = 'test level2 #1';
        $level1->level2[] = $level2;

        $level2 = new EmbeddedTestLevel2();
        $level2->name = 'test level2 #2';
        $level1->level2[] = $level2;

        $level1 = new EmbeddedTestLevel1();
        $level1->name = 'test level1 #2';
        $test->level1[] = $level1;

        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->clear();

        $check = $this->dm->findOne('Documents\Functional\EmbeddedTestLevel0');
        $this->assertEquals('test', $check->name);
        $this->assertInstanceOf('Documents\Functional\EmbeddedTestLevel1', $test->level1[0]);
        $this->assertInstanceOf('Documents\Functional\EmbeddedTestLevel2', $test->level1[0]->level2[0]);
        $this->assertEquals(2, count($test->level1));
        $this->assertEquals(2, count($test->level1[0]->level2));
    }
}