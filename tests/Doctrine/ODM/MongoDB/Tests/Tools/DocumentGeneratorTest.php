<?php

namespace Doctrine\ODM\MongoDB\Tests\Tools;

use Doctrine\ODM\MongoDB\Tools\DocumentGenerator;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo;

class DocumentGeneratorTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    private $generator;
    private $tmpDir;
    private $namespace;

    public function setUp()
    {
        parent::setUp();
        $this->namespace = uniqid("doctrine_");
        $this->tmpDir = \sys_get_temp_dir();
        \mkdir($this->tmpDir . \DIRECTORY_SEPARATOR . $this->namespace);
        $this->generator = new DocumentGenerator();
        $this->generator->setGenerateAnnotations(true);
        $this->generator->setGenerateStubMethods(true);
        $this->generator->setRegenerateDocumentIfExists(false);
        $this->generator->setUpdateDocumentIfExists(true);
    }

    public function tearDown()
    {
        parent::tearDown();
        $ri = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->tmpDir . '/' . $this->namespace));
        foreach ($ri AS $file) {
            /* @var $file \SplFileInfo */
            if ($file->isFile()) {
                \unlink($file->getPathname());
            }
        }
        rmdir($this->tmpDir . '/' . $this->namespace);
    }

    public function generateBookDocumentFixture()
    {
        $metadata = new ClassMetadataInfo($this->namespace . '\DocumentGeneratorBook');
        $metadata->namespace = $this->namespace;
        $metadata->customRepositoryClassName = $this->namespace  . '\DocumentGeneratorBookRepository';

        $metadata->collection = 'book';
        $metadata->mapField(array('fieldName' => 'name', 'type' => 'string'));
        $metadata->mapField(array('fieldName' => 'status', 'type' => 'string'));
        $metadata->mapField(array('fieldName' => 'id', 'type' => 'integer', 'id' => true));
        $metadata->mapOneReference(array('fieldName' => 'author', 'targetDocument' => 'Doctrine\ODM\MongoDB\Tests\Tools\DocumentGeneratorAuthor'));
        $metadata->mapManyReference(array(
            'fieldName' => 'comments',
            'targetDocument' => 'Doctrine\ODM\MongoDB\Tests\Tools\DocumentGeneratorComment'
        ));
        $metadata->mapManyReference(array(
                'fieldName' => 'searches',
                'targetDocument' => 'Doctrine\ODM\MongoDB\Tests\Tools\DocumentGeneratorSearch'
        ));
        $metadata->addLifecycleCallback('loading', 'postLoad');
        $metadata->addLifecycleCallback('willBeRemoved', 'preRemove');
        $metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_CUSTOM);

        $this->generator->writeDocumentClass($metadata, $this->tmpDir);

        return $metadata;
    }

    /**
     * @param  ClassMetadataInfo $metadata
     * @return DocumentGeneratorBook
     */
    public function newInstance($metadata)
    {
        $path = $this->tmpDir . '/'. $this->namespace . '/DocumentGeneratorBook.php';
        $this->assertFileExists($path);
        require_once $path;

        return new $metadata->name;
    }

    public function testGeneratedDocumentClass()
    {
        $metadata = $this->generateBookDocumentFixture();

        $book = $this->newInstance($metadata);

        $this->assertTrue(class_exists($metadata->name), "Class does not exist.");
        $this->assertTrue(method_exists($metadata->namespace . '\DocumentGeneratorBook', '__construct'), "DocumentGeneratorBook::__construct() missing.");
        $this->assertTrue(method_exists($metadata->namespace . '\DocumentGeneratorBook', 'getId'), "DocumentGeneratorBook::getId() missing.");
        $this->assertTrue(method_exists($metadata->namespace . '\DocumentGeneratorBook', 'setName'), "DocumentGeneratorBook::setName() missing.");
        $this->assertTrue(method_exists($metadata->namespace . '\DocumentGeneratorBook', 'getName'), "DocumentGeneratorBook::getName() missing.");
        $this->assertTrue(method_exists($metadata->namespace . '\DocumentGeneratorBook', 'setAuthor'), "DocumentGeneratorBook::setAuthor() missing.");
        $this->assertTrue(method_exists($metadata->namespace . '\DocumentGeneratorBook', 'getAuthor'), "DocumentGeneratorBook::getAuthor() missing.");
        $this->assertTrue(method_exists($metadata->namespace . '\DocumentGeneratorBook', 'getComments'), "DocumentGeneratorBook::getComments() missing.");
        $this->assertTrue(method_exists($metadata->namespace . '\DocumentGeneratorBook', 'addComment'), "DocumentGeneratorBook::addComment() missing.");
        $this->assertTrue(method_exists($metadata->namespace . '\DocumentGeneratorBook', 'removeComment'), "DocumentGeneratorBook::removeComment() missing.");
        $this->assertTrue(method_exists($metadata->namespace . '\DocumentGeneratorBook', 'getSearches'), "DocumentGeneratorBook::getSearches() missing.");
        $this->assertTrue(method_exists($metadata->namespace . '\DocumentGeneratorBook', 'addSearch'), "DocumentGeneratorBook::addSearch() missing.");
        $this->assertTrue(method_exists($metadata->namespace . '\DocumentGeneratorBook', 'removeSearch'), "DocumentGeneratorBook::removeSearch() missing.");

        $book->setName('Jonathan H. Wage');
        $this->assertEquals('Jonathan H. Wage', $book->getName());

        $author = new DocumentGeneratorAuthor();
        $book->setAuthor($author);
        $this->assertEquals($author, $book->getAuthor());

        $comment = new DocumentGeneratorComment();
        $book->addComment($comment);
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $book->getComments());
        $this->assertEquals(new \Doctrine\Common\Collections\ArrayCollection(array($comment)), $book->getComments());
        $book->removeComment($comment);
        $this->assertEquals(new \Doctrine\Common\Collections\ArrayCollection(array()), $book->getComments());
    }

    public function testDocumentUpdatingWorks()
    {
        $metadata = $this->generateBookDocumentFixture();
        $metadata->mapField(array('fieldName' => 'test', 'type' => 'string'));

        $this->generator->writeDocumentClass($metadata, $this->tmpDir);

        $this->assertFileExists($this->tmpDir . "/" . $this->namespace . "/DocumentGeneratorBook.php");

        $book = $this->newInstance($metadata);
        $reflClass = new \ReflectionClass($metadata->name);

        $this->assertTrue($reflClass->hasProperty('name'), "Regenerating keeps property 'name'.");
        $this->assertTrue($reflClass->hasProperty('status'), "Regenerating keeps property 'status'.");
        $this->assertTrue($reflClass->hasProperty('id'), "Regenerating keeps property 'id'.");

        $this->assertTrue($reflClass->hasProperty('test'), "Check for property test failed.");
        $this->assertTrue($reflClass->getProperty('test')->isProtected(), "Check for protected property test failed.");
        $this->assertTrue($reflClass->hasMethod('getTest'), "Check for method 'getTest' failed.");
        $this->assertTrue($reflClass->getMethod('getTest')->isPublic(), "Check for public visibility of method 'getTest' failed.");
        $this->assertTrue($reflClass->hasMethod('setTest'), "Check for method 'getTest' failed.");
        $this->assertTrue($reflClass->getMethod('getTest')->isPublic(), "Check for public visibility of method 'getTest' failed.");
    }

    public function testDocumentExtendsStdClass()
    {
        $this->generator->setClassToExtend('stdClass');
        $metadata = $this->generateBookDocumentFixture();

        $book = $this->newInstance($metadata);
        $this->assertInstanceOf('stdClass', $book);
    }

    public function testLifecycleCallbacks()
    {
        $metadata = $this->generateBookDocumentFixture();

        $book = $this->newInstance($metadata);
        $reflClass = new \ReflectionClass($metadata->name);

        $this->assertTrue($reflClass->hasMethod('loading'), "Check for postLoad lifecycle callback.");
        $this->assertTrue($reflClass->hasMethod('willBeRemoved'), "Check for preRemove lifecycle callback.");
    }

    public function testLoadMetadata()
    {
        $metadata = $this->generateBookDocumentFixture();

        $book = $this->newInstance($metadata);

        $cm = new \Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo($metadata->name);
        $reader = new \Doctrine\Common\Annotations\AnnotationReader();
        $driver = new \Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver($reader);
        $driver->loadMetadataForClass($cm->name, $cm);

        $this->assertEquals($cm->getCollection(), $metadata->getCollection());
        $this->assertEquals($cm->lifecycleCallbacks, $metadata->lifecycleCallbacks);
        $this->assertEquals($cm->identifier, $metadata->identifier);
        $this->assertEquals($cm->idGenerator, $metadata->idGenerator);
        $this->assertEquals($cm->customRepositoryClassName, $metadata->customRepositoryClassName);
    }

    public function testLoadPrefixedMetadata()
    {
        $metadata = $this->generateBookDocumentFixture();

        $book = $this->newInstance($metadata);

        $cm = new \Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo($metadata->name);
        $reader = new \Doctrine\Common\Annotations\AnnotationReader();
        $driver = new \Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver($reader);
        $driver->loadMetadataForClass($cm->name, $cm);

        $this->assertEquals($cm->getCollection(), $metadata->getCollection());
        $this->assertEquals($cm->lifecycleCallbacks, $metadata->lifecycleCallbacks);
        $this->assertEquals($cm->identifier, $metadata->identifier);
        $this->assertEquals($cm->idGenerator, $metadata->idGenerator);
        $this->assertEquals($cm->customRepositoryClassName, $metadata->customRepositoryClassName);
    }

    public function testTraitPropertiesAndMethodsAreNotDuplicated()
    {
        $cmf = $this->dm->getMetadataFactory();
        $user = new \Doctrine\ODM\MongoDB\Tests\Tools\GH297\User();
        $metadata = $cmf->getMetadataFor(get_class($user));
        $metadata->name = $this->namespace . "\User";
        $metadata->namespace = $this->namespace;
        $this->generator->writeDocumentClass($metadata, $this->tmpDir);
        $this->assertFileExists($this->tmpDir . "/" . $this->namespace . "/User.php");
        require $this->tmpDir . "/" . $this->namespace . "/User.php";
        $reflClass = new \ReflectionClass($metadata->name);
        $this->assertSame($reflClass->hasProperty('address'), false);
        $this->assertSame($reflClass->hasMethod('setAddress'), false);
        $this->assertSame($reflClass->hasMethod('getAddress'), false);
    }

    public function testTraitPropertiesAndMethodsAreNotDuplicatedInChildClasses()
    {
        $cmf = $this->dm->getMetadataFactory();
        $user = new \Doctrine\ODM\MongoDB\Tests\Tools\GH297\Admin();
        $metadata = $cmf->getMetadataFor(get_class($user));
        $metadata->name = $this->namespace . "\DDC2372Admin";
        $metadata->namespace = $this->namespace;
        $this->generator->writeDocumentClass($metadata, $this->tmpDir);
        $this->assertFileExists($this->tmpDir . "/" . $this->namespace . "/DDC2372Admin.php");
        require $this->tmpDir . "/" . $this->namespace . "/DDC2372Admin.php";
        $reflClass = new \ReflectionClass($metadata->name);
        $this->assertSame($reflClass->hasProperty('address'), false);
        $this->assertSame($reflClass->hasMethod('setAddress'), false);
        $this->assertSame($reflClass->hasMethod('getAddress'), false);
    }

    /**
     * Tests that properties, getters and setters are not duplicated on children classes
     *
     * @see https://github.com/doctrine/mongodb-odm/issues/1299
     */
    public function testMethodsAndPropertiesAreNotDuplicatedInChildClasses()
    {
        $cmf = $this->dm->getMetadataFactory();
        $nsDir = $this->tmpDir.'/'.$this->namespace;

        // Copy GH1299User class to temp dir
        $content = str_replace(
            'namespace Doctrine\ODM\MongoDB\Tests\Tools\GH1299',
            'namespace '.$this->namespace,
            file_get_contents(__DIR__.'/GH1299/GH1299User.php')
        );
        $fname = $nsDir.'/GH1299User.php';
        file_put_contents($fname, $content);
        require $fname;

        // Generate document class
        $metadata = $cmf->getMetadataFor($this->namespace.'\GH1299User');
        $this->generator->writeDocumentClass($metadata, $this->tmpDir);

        // Make a copy of the generated class that does not extend the BaseUser class
        $source = file_get_contents($fname);

        // class _DDC1590User extends DDC1590Entity { ... }
        $source2 = str_replace('class GH1299User', 'class _GH1299User', $source);
        $fname2  = $nsDir.'/_DDC1590User.php';
        file_put_contents($fname2, $source2);
        require $fname2;

        $source3 = str_replace('class GH1299User extends BaseUser', 'class __GH1299User', $source);
        $fname3  = $nsDir.'/_GH1299User.php';
        file_put_contents($fname3, $source3);
        require $fname3;

        // The GH1299User class that extends BaseUser should have all properties, getters and setters
        // (some of them are inherited from BaseUser)
        $reflClass2 = new \ReflectionClass($this->namespace.'\_GH1299User');

        $this->assertTrue($reflClass2->hasProperty('id'));
        $this->assertTrue($reflClass2->hasProperty('name'));
        $this->assertTrue($reflClass2->hasProperty('lastname'));

        $this->assertTrue($reflClass2->hasMethod('getId'));
        $this->assertFalse($reflClass2->hasMethod('setId'));
        $this->assertTrue($reflClass2->hasMethod('getName'));
        $this->assertTrue($reflClass2->hasMethod('setName'));
        $this->assertTrue($reflClass2->hasMethod('getLastname'));
        $this->assertTrue($reflClass2->hasMethod('setLastname'));

        // The class that does not extend BaseUser should not have the properties and methods / setters
        // from the BaseUser class, but only its own specific ones
        $reflClass3 = new \ReflectionClass($this->namespace.'\__GH1299User');

        $this->assertFalse($reflClass3->hasProperty('id'));
        $this->assertFalse($reflClass3->hasProperty('name'));
        $this->assertTrue($reflClass3->hasProperty('lastname'));

        $this->assertFalse($reflClass3->hasMethod('getId'));
        $this->assertFalse($reflClass3->hasMethod('setId'));
        $this->assertFalse($reflClass3->hasMethod('getName'));
        $this->assertFalse($reflClass3->hasMethod('setName'));
        $this->assertTrue($reflClass3->hasMethod('getLastname'));
        $this->assertTrue($reflClass3->hasMethod('setLastname'));
    }
}

class DocumentGeneratorAuthor {}
class DocumentGeneratorComment {}
class DocumentGeneratorSearch {}
