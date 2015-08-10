<?php

namespace Doctrine\ODM\MongoDB\Tests\Tools;

use Doctrine\ODM\MongoDB\Tools\DocumentGenerator;
use Doctrine\ODM\MongoDB\Tools\DocumentRepositoryGenerator;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo;

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

    public function testPersistedDocumentRepositoryClassWithSimpleNamespaceMapping()
    {
        $namespace = $this->testBucket;

        $this->generator->writeDocumentRepositoryClass($namespace . '\\TestDocumentRepository', $this->tmpDir);

        $this->assertFileExists($this->testBucketPath . DIRECTORY_SEPARATOR ."TestDocumentRepository.php");
    }

    public function testPersistedDocumentRepositoryClassWithArbitraryNamespaceMapping()
    {
        $namespace = 'A\B\C\D';

        $this->generator->writeDocumentRepositoryClass(
            $namespace . '\\TestDocumentRepository', $this->testBucketPath, $namespace
        );

        $this->assertFileExists($this->testBucketPath . DIRECTORY_SEPARATOR ."TestDocumentRepository.php");
    }
}
