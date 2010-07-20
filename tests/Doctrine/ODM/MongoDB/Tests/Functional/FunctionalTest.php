<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

require_once __DIR__ . '/../../../../../TestInit.php';

use Documents\User,
    Documents\Phonenumber,
    Documents\Employee,
    Documents\Manager,
    Documents\Address,
    Documents\Group,
    Documents\Project;

class FunctionalTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{

    public function tearDown()
    {
        parent::tearDown();
        $documents = array(
            'Doctrine\ODM\MongoDB\Tests\Functional\AlsoLoad',
            'Doctrine\ODM\MongoDB\Tests\Functional\NotAnnotatedDocument',
            'Doctrine\ODM\MongoDB\Tests\Functional\NotSaved',
            'Doctrine\ODM\MongoDB\Tests\Functional\NullFieldValues',
            'Doctrine\ODM\MongoDB\Tests\Functional\SimpleEmbedAndReference',
            'Doctrine\ODM\MongoDB\Tests\Functional\FavoritesUser',
        );
        foreach ($documents as $document) {
            $this->dm->getDocumentCollection($document)->drop();
        }
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
        $this->dm->persist($address);
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
        $this->dm->getDocumentCollection('Doctrine\ODM\MongoDB\Tests\Functional\NotAnnotatedDocument')->drop();

        $test = new NotAnnotatedDocument();
        $test->field = 'test';
        $test->transientField = 'w00t';
        $this->dm->persist($test);
        $this->dm->flush($test);
        $this->dm->clear();

        $test = $this->dm->find('Doctrine\ODM\MongoDB\Tests\Functional\NotAnnotatedDocument')
            ->getSingleResult();
        $this->assertNotNull($test);
        $this->assertFalse(isset($test->transientField));
    }

    public function testNullFieldValuesAllowed()
    {
        $this->dm->getDocumentCollection('Doctrine\ODM\MongoDB\Tests\Functional\NullFieldValues')->drop();

        $test = new NullFieldValues();
        $test->field = null;
        $this->dm->persist($test);
        $this->dm->flush();

        $test = $this->dm->find('Doctrine\ODM\MongoDB\Tests\Functional\NullFieldValues')
            ->hydrate(false)
            ->getResults();
        $document = current($test);
        $this->assertNotNull($test);
        $this->assertNull($document['field']);

        $document = $this->dm->find('Doctrine\ODM\MongoDB\Tests\Functional\NullFieldValues')
            ->getSingleResult();
        $document->field = 'test';
        $this->dm->flush();
        $this->dm->clear();

        $document = $this->dm->find('Doctrine\ODM\MongoDB\Tests\Functional\NullFieldValues')
            ->getSingleResult();
        $this->assertEquals('test', $document->field);
        $document->field = null;
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->find('Doctrine\ODM\MongoDB\Tests\Functional\NullFieldValues')
            ->hydrate(false)
            ->getSingleResult();
        $this->assertNull($test['field']);
        $this->assertFalse(isset($test['transientField']));
    }

    public function testAlsoLoadOnProperty()
    {
        $collection = $this->dm->getDocumentCollection('Doctrine\ODM\MongoDB\Tests\Functional\AlsoLoad');
        $collection->drop();
        $collection->insert(array(
            'bar' => 'w00t'
        ));
        $document = $this->dm->find('Doctrine\ODM\MongoDB\Tests\Functional\AlsoLoad', array('bar' => 'w00t'))
            ->getSingleResult();
        $this->assertEquals('w00t', $document->foo);

        $collection->insert(array(
            'foo' => 'cool'
        ));
        $document = $this->dm->find('Doctrine\ODM\MongoDB\Tests\Functional\AlsoLoad', array('bar' => 'w00t'))
            ->getSingleResult();
        $this->assertNotNull($document->foo);

        $collection->insert(array(
            'zip' => 'test'
        ));
        $document = $this->dm->find('Doctrine\ODM\MongoDB\Tests\Functional\AlsoLoad', array('bar' => 'w00t'))
            ->getSingleResult();
        $this->assertNotNull($document->foo);
    }

    public function testAlsoLoadOnMethod()
    {
        $collection = $this->dm->getDocumentCollection('Doctrine\ODM\MongoDB\Tests\Functional\AlsoLoad');
        $collection->drop();
        $collection->insert(array(
            'name' => 'Jonathan Wage',
            'test1' => 'test1'
        ));
        $document = $this->dm->find('Doctrine\ODM\MongoDB\Tests\Functional\AlsoLoad', array('name' => 'Jonathan Wage'))
            ->getSingleResult();
        $this->assertEquals('Jonathan', $document->firstName);
        $this->assertEquals('Wage', $document->lastName);
        $this->assertEquals('test1', $document->test);

        $collection->insert(array(
            'fullName' => 'Jonathan Wage',
            'test2' => 'test2'
        ));
        $document = $this->dm->find('Doctrine\ODM\MongoDB\Tests\Functional\AlsoLoad', array('fullName' => 'Jonathan Wage'))
            ->getSingleResult();
        $this->assertEquals('Jonathan', $document->firstName);
        $this->assertEquals('Wage', $document->lastName);
        $this->assertEquals('test2', $document->test);

        $collection->insert(array(
            'test' => 'test'
        ));
        $document = $this->dm->find('Doctrine\ODM\MongoDB\Tests\Functional\AlsoLoad', array('test' => 'test'))
            ->getSingleResult();
        $this->assertEquals('test', $document->test);
    }

    public function testSimplerEmbedAndReference()
    {
        $class = $this->dm->getClassMetadata('Doctrine\ODM\MongoDB\Tests\Functional\SimpleEmbedAndReference');
        $this->assertEquals('many', $class->fieldMappings['embedMany']['type']);
        $this->assertEquals('one', $class->fieldMappings['embedOne']['type']);
        $this->assertEquals('many', $class->fieldMappings['referenceMany']['type']);
        $this->assertEquals('one', $class->fieldMappings['referenceOne']['type']);
    }

    public function testNotSavedFields()
    {
        $collection = $this->dm->getDocumentCollection('Doctrine\ODM\MongoDB\Tests\Functional\NotSaved');
        $collection->drop();
        $collection->insert(array(
            'name' => 'Jonathan Wage',
            'notSaved' => 'test'
        ));
        $notSaved = $this->dm->findOne('Doctrine\ODM\MongoDB\Tests\Functional\NotSaved');
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

        $user = $this->dm->findOne('Doctrine\ODM\MongoDB\Tests\Functional\FavoritesUser', array('name' => 'favorites'));
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
        $seller->name = 'Jon';

        $this->dm->persist($seller);
        $this->dm->persist($product);
        $this->dm->flush();

        $sellable = new PreUpdateTestSellable();
        $sellable->product = $product;
        $sellable->seller = $seller;

        $product->sellable = $sellable;

        $this->dm->flush();
        $this->dm->clear();

        $product = $this->dm->findOne('Doctrine\ODM\MongoDB\Tests\Functional\PreUpdateTestProduct', array('name' => 'Product'));
        $this->assertInstanceOf('Doctrine\ODM\MongoDB\Tests\Functional\PreUpdateTestProduct', $product->sellable->getProduct());
        $this->assertInstanceOf('Doctrine\ODM\MongoDB\Tests\Functional\PreUpdateTestSeller', $product->sellable->getSeller());

        $product = new PreUpdateTestProduct();
        $product->name = 'Product2';

        $this->dm->persist($product);
        $this->dm->flush();

        $sellable = new PreUpdateTestSellable();
        $sellable->product = $product;
        $sellable->seller = $this->dm->findOne('Doctrine\ODM\MongoDB\Tests\Functional\PreUpdateTestSeller', array('name' => 'Jon'));

        $product->sellable = $sellable;

        $this->dm->flush();
        $this->dm->clear();

        $product = $this->dm->findOne('Doctrine\ODM\MongoDB\Tests\Functional\PreUpdateTestProduct', array('name' => 'Product2'));
        $this->assertEquals('Jon', $product->sellable->getSeller()->getName());
        $this->assertEquals('Product2', $product->sellable->getProduct()->getName());
    }

    public function testSameCollectionTest()
    {
        $test1 = new SameCollectionTest1();
        $test1->name = 'test1';
        $this->dm->persist($test1);

        $test2 = new SameCollectionTest2();
        $test2->name = 'test2';
        $this->dm->persist($test2);
        $this->dm->flush();

        $test = $this->dm->findOne('Doctrine\ODM\MongoDB\Tests\Functional\SameCollectionTest1', array('name' => 'test1'));
        $this->assertInstanceOf('Doctrine\ODM\MongoDB\Tests\Functional\SameCollectionTest1', $test);

        $test = $this->dm->findOne('Doctrine\ODM\MongoDB\Tests\Functional\SameCollectionTest1', array('name' => 'test2'));
        $this->assertInstanceOf('Doctrine\ODM\MongoDB\Tests\Functional\SameCollectionTest2', $test);
    }
}

/**
 * @Document(collection="same_collection")
 * @DiscriminatorField(fieldName="type")
 * @DiscriminatorMap({"test1"="Doctrine\ODM\MongoDB\Tests\Functional\SameCollectionTest1", "test2"="Doctrine\ODM\MongoDB\Tests\Functional\SameCollectionTest2"})
 */
class SameCollectionTest1
{
    /** @Id */
    public $id;

    /** @String */
    public $name;

    /** @String */
    public $test;
}

/**
 * @Document(collection="same_collection")
 * @DiscriminatorField(fieldName="type")
 * @DiscriminatorMap({"test1"="Doctrine\ODM\MongoDB\Tests\Functional\SameCollectionTest1", "test2"="Doctrine\ODM\MongoDB\Tests\Functional\SameCollectionTest2"})
 */
class SameCollectionTest2
{
    /** @Id */
    public $id;

    /** @String */
    public $name;

    /** @String */
    public $ok;

    /** @String */
    public $w00t;
}

/**
 * @Document(collection="pre_update_test_product")
 */
class PreUpdateTestProduct
{
    /** @Id */
    public $id;

    /** @String */
    public $name;

    /** @Embedded(targetDocument="PreUpdateTestSellable") */
    public $sellable;

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }
}

/**
 * @EmbeddedDocument
 */
class PreUpdateTestSellable
{
    /** @Reference(targetDocument="PreUpdateTestProduct") */
    public $product;

    /** @Reference(targetDocument="PreUpdateTestSeller") */
    public $seller;

    public function getProduct()
    {
        return $this->product;
    }

    public function getSeller()
    {
        return $this->seller;
    }
}

/**
 * @Document(collection="pre_update_test_seller")
 * @HasLifecycleCallbacks
 */
class PreUpdateTestSeller
{
    /** @Id */
    public $id;

    /** @String */
    public $name;

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    /** @PreUpdate */
    public function preUpdate()
    {
    }
}

/** @Document(collection="favorites_user") */
class FavoritesUser
{
    /** @Id */
    private $id;

    /** @String */
    private $name;

    /** @Reference */
    private $favorites = array();

    /** @Embedded */
    private $embedded = array();

    /** @Reference */
    private $favorite;

    /** @Embedded */
    private $embed;

    public function setFavorite($favorite)
    {
        $this->favorite = $favorite;
    }

    public function getFavorite()
    {
        return $this->favorite;
    }

    public function setEmbed($embed)
    {
        $this->embed = $embed;
    }

    public function getEmbed()
    {
        return $this->embed;
    }

    public function embed($document)
    {
        $this->embedded[] = $document;
    }

    public function getEmbedded()
    {
        return $this->embedded;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function addFavorite($favorite)
    {
        $this->favorites[] = $favorite;
    }

    public function getFavorites()
    {
        return $this->favorites;
    }
}

/** @Document(collection="functional_tests") */
class NotSaved
{
    /** @Id */
    public $id;
    /** @String */
    public $name;

    /** @NotSaved */
    public $notSaved;
}

/** @Document(collection="functional_tests") */
class SimpleEmbedAndReference
{
    /** @Embed(targetDocument="Reference") */
    public $embedMany = array();

    /** @Reference(targetDocument="Embedded") */
    public $referenceMany = array();

    /** @Embed(targetDocument="Reference") */
    public $embedOne;

    /** @Reference(targetDocument="Embedded") */
    public $referenceOne;
}

/** @Document(collection="functional_tests") */
class AlsoLoad
{
    /**
     * @AlsoLoad({"bar", "zip"})
     */
    public $foo;

    /** @NotSaved */
    public $bar;

    /** @NotSaved */
    public $zip;

    /** @NotSaved */
    public $name;

    /** @NotSaved */
    public $fullName;

    /** @String */
    public $firstName;

    /** @String */
    public $lastName;

    /** @String */
    public $test;

    /** @String */
    public $test1;

    /** @String */
    public $test2;

    /** @AlsoLoad({"name", "fullName"}) */
    public function populateFirstAndLastName($name)
    {
        $e = explode(' ', $name);
        $this->firstName = $e[0];
        $this->lastName = $e[1];
    }

    /** @AlsoLoad({"test1", "test2"}) */
    public function populateTest($test)
    {
        $this->test = $test;
    }
}

/** @Document(collection="functional_tests") */
class NullFieldValues
{
    /** @Field(nullable=true) */
    public $field;
}

/** @Document(collection="functional_tests") */
class NotAnnotatedDocument
{
    /** @Field */
    public $field;

    public $transientField;
}