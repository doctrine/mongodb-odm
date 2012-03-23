<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class BinDataTest extends BaseTest
{
	/**
	 * @dataProvider provideData
	 */
	public function testBinData($field, $data, $type)
	{
		$test = new BinDataTestUser();
		$test->$field = $data;
		$this->dm->persist($test);
		$this->dm->flush();

		$check = $this->dm->getDocumentCollection(get_class($test))->findOne(array());
		$this->assertInstanceOf('MongoBinData', $check[$field]);
		$this->assertEquals($type, $check[$field]->type);
		$this->assertEquals($data, $check[$field]->bin);
	}

	public function provideData()
	{
		return array(
			array('bin', 'test', \MongoBinData::BYTE_ARRAY),
			array('binFunc', 'test', \MongoBinData::FUNC),
			array('BinUUID', 'test', \MongoBinData::UUID),
			array('binMd5', 'test', \MongoBinData::MD5),
			array('binCustom', 'test', -128) // \MongoBinData::CUSTOM produces 128 which is wrong
		);
	}
}

/** @ODM\Document */
class BinDataTestUser
{
	/** @ODM\Id */
	public $id;

	/** @ODM\Bin */
	public $bin;

	/** @ODM\Bin(type="bin_func") */
	public $binFunc;

	/** @ODM\Bin(type="bin_uuid") */
	public $BinUUID;

	/** @ODM\Bin(type="bin_md5") */
	public $binMd5;

	/** @ODM\Bin(type="bin_custom") */
	public $binCustom;
}