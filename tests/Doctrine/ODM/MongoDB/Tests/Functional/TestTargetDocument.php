<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class TargetDocumentTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testMappedSuperClassAsTargetDocument()
    {
    	$test = new TargetDocumentTestDocument();
    	$test->reference = new TargetDocumentTestReference();
    	$this->dm->persist($test);
    	$this->dm->persist($test->reference);
    	$this->dm->flush();
    }
}

/** @ODM\Document */
class TargetDocumentTestDocument
{
	/** @ODM\Id */
	public $id;

	/** @ODM\ReferenceOne(targetDocument="Doctrine\ODM\MongoDB\Tests\Functional\TargetDocumentTestReference") */
	public $reference;
}

/** @ODM\MappedSuperclass */
abstract class TargetDocumentTestReference
{
	/** @ODM\Id */
	public $id;
}