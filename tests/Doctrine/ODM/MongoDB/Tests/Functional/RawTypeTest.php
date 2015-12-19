<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class RawTypeTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
	/**
	 * @dataProvider getTestRawTypeData
	 */
	public function testRawType($value)
	{
		$test = new RawType();
		$test->raw = $value;

		$this->dm->persist($test);
		$this->dm->flush();

		$result = $this->dm->getDocumentCollection(get_class($test))->findOne(array('_id' => new \MongoId($test->id)));
		$this->assertEquals($value, $result['raw']);
	}

	public function getTestRawTypeData()
	{
		return array(
			array('test'),
			array(1),
			array(0),
			array(array('test' => 'test')),
			array(new \MongoDate()),
			array(true),
			array(array('date' => new \MongoDate())),
			array(new \MongoId())
		);
	}
}

/** @ODM\Document */
class RawType
{
	/** @ODM\Id */
	public $id;

	/** @ODM\Field(type="raw") */
	public $raw;
}
