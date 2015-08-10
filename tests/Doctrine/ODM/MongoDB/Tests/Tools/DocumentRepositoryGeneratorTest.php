<?php

namespace Doctrine\ODM\MongoDB\Tests\Tools;

use Doctrine\Common\EventManager;
use Doctrine\ODM\MongoDB\Hydrator\HydratorFactory;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Tools\DocumentRepositoryGenerator;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Stubs\DocumentManager;

class DocumentRepositoryGeneratorTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    /**
     * @var string
     */
    private $tmpDir;

    /**
     * @var DocumentRepositoryGenerator
     */
    private $generator;

    /**
     * @var string
     */
    private $testBucket;

    /**
     * @var string
     */
    private $testBucketPath;

    public function setUp()
    {
        parent::setUp();

        $this->tmpDir = sys_get_temp_dir();

        // We create a temporary directory for each test
        $this->testBucket = uniqid("doctrine_mongo_odm_");
        $this->testBucketPath = $this->tmpDir . DIRECTORY_SEPARATOR . $this->testBucket;
        mkdir($this->testBucketPath);

        $this->generator = new DocumentRepositoryGenerator();
    }

    public function tearDown()
    {
        parent::tearDown();

        if (isset($this->testBucketPath) && !empty($this->testBucketPath)) {
            $ri = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->testBucketPath));
            foreach ($ri AS $file) {
                /* @var $file \SplFileInfo */
                if ($file->isFile()) {
                    \unlink($file->getPathname());
                }
            }
            rmdir($this->testBucketPath);
        }
    }

    /**
     * Checks if class files have been generated, and if is possible to load the classes.
     * @param string $fullClassName
     */
    private function tryLoadingRepositoryClass($fullClassName)
    {
        $classNameParts = explode('\\', $fullClassName);
        $simpleClassName = $classNameParts[count($classNameParts)-1];

        $path = $this->testBucketPath . DIRECTORY_SEPARATOR . $simpleClassName .'.php';

        $this->assertFileExists($path);

        require_once $path;

        $dm = new DocumentManager();
        $em = new EventManager();
        $hf = new HydratorFactory($dm, $em, $this->testBucketPath, $this->testBucket, 0);
        $uow = new UnitOfWork($dm, $em, $hf);

        return new $fullClassName($dm, $uow, new ClassMetadata($fullClassName));
    }

    public function testPersistedDocumentRepositoryClassWithSimpleNamespaceMapping()
    {
        $namespace = $this->testBucket;

        $this->generator->writeDocumentRepositoryClass($namespace . '\\TestDocumentRepository', $this->tmpDir);

        $this->tryLoadingRepositoryClass($namespace . '\\TestDocumentRepository');
    }

    public function testPersistedDocumentRepositoryClassWithArbitraryNamespaceMapping()
    {
        $namespace = 'A\B\C\D';

        $this->generator->writeDocumentRepositoryClass(
            $namespace . '\\TestDocumentRepository', $this->testBucketPath, $namespace
        );

        $this->tryLoadingRepositoryClass($namespace . '\\TestDocumentRepository');
    }
}
