<?php

namespace Doctrine\ODM\MongoDB\Tests\Query;

use Doctrine\ODM\MongoDB\Query\FieldExtractor;

class FieldExtractorTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
	/**
	 * @dataProvider getQueriesAndFields
	 **/
	public function testFieldExtractor($query, $fields)
	{
		$this->assertFieldsExtracted($query, $fields);
	}

	public function getQueriesAndFields()
	{
		return array(
			array(
				array('fieldName' => 1),
				array('fieldName')
			),
			array(
				array('fieldName' => array(
					'$elemMatch' => array(
						'embedded' => 1
					)
				)),
				array('fieldName.embedded')
			),
			array(
				array('fieldName' => array(
					'$in' => array(1)
				)),
				array('fieldName')
			),
			array(
				array('fieldName' => array(
					'$gt' => 1
				)),
				array('fieldName')
			),
			array(
				array('$or' => array(
					array(
						'fieldName1' => array(
							'$in' => array(1)
						)
					),
					array(
						'fieldName2' => array(
							'$in' => array(1)
						)
					),
					array(
						'fieldName3' => 1
					)
				)),
				array('fieldName1', 'fieldName2', 'fieldName3')
			),
			array(
				array('$and' => array(
					array(
						'fieldName1' => array(
							'$in' => array(1)
						)
					),
					array(
						'fieldName2' => array(
							'$in' => array(1)
						)
					),
					array(
						'fieldName3' => 1
					)
				)),
				array('fieldName1', 'fieldName2', 'fieldName3')
			)
		);
	}

	private function assertFieldsExtracted(array $query, array $fields)
	{
		$extractor = new FieldExtractor($query);
		$this->assertEquals($fields, $extractor->getFields());
	}
}