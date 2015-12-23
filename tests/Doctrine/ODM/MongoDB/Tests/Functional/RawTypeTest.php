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

		$result = $this->dm->getDocumentCollection(get_class($test))->findOne(['_id' => new \MongoId($test->id)]);
		$this->assertEquals($value, $result['raw']);
	}

	public function getTestRawTypeData()
	{
		return [
			['test'],
			[1],
			[0],
			[['test' => 'test']],
			[new \MongoDate()],
			[true],
			[['date' => new \MongoDate()]],
			[new \MongoId()]
		];
	}
}

/** @ODM\Document */
class RawType
{
	/** @ODM\Id */
	public $id;

	/** @ODM\Raw */
	public $raw;
}
