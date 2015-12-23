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
		return [
			[
				['fieldName' => 1],
				['fieldName']
			],
			[
				['fieldName' => [
					'$elemMatch' => [
						'embedded' => 1
					]
				]],
				['fieldName.embedded']
			],
			[
				['fieldName' => [
					'$in' => [1]
				]],
				['fieldName']
			],
			[
				['fieldName' => [
					'$gt' => 1
				]],
				['fieldName']
			],
			[
				['$or' => [
					[
						'fieldName1' => [
							'$in' => [1]
						]
					],
					[
						'fieldName2' => [
							'$in' => [1]
						]
					],
					[
						'fieldName3' => 1
					]
				]],
				['fieldName1', 'fieldName2', 'fieldName3']
			],
			[
				['$and' => [
					[
						'fieldName1' => [
							'$in' => [1]
						]
					],
					[
						'fieldName2' => [
							'$in' => [1]
						]
					],
					[
						'fieldName3' => 1
					]
				]],
				['fieldName1', 'fieldName2', 'fieldName3']
			]
		];
	}

	private function assertFieldsExtracted(array $query, array $fields)
	{
		$extractor = new FieldExtractor($query);
		$this->assertEquals($fields, $extractor->getFields());
	}
}
