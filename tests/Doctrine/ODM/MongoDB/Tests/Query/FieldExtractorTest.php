<?php

namespace Doctrine\ODM\MongoDB\Tests\Query;

use Doctrine\ODM\MongoDB\Query\FieldExtractor;

class FieldExtractorTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    /**
     * @dataProvider provideGetFieldsWithEqualityCondition
     */
    public function testGetFieldsWithEqualityCondition($query, $expected)
    {
        $extractor = new FieldExtractor($query);
        $this->assertEquals($expected, $extractor->getFieldsWithEqualityCondition());
    }
    
    public function provideGetFieldsWithEqualityCondition()
    {
        $tests = array();
        $tests[] = array(array(), array());
        $tests[] = array(array('foo' => 'test'), array('foo'));
        $tests[] = array(
            array(
                'foo' => 'test',
                '$or' => array(
                    array('foo' => 'test'),
                    array('bar' => 'test')
                )
            ),
            array('foo', 'bar')
        );
        $tests[] = array(
            array(
                'foo' => array(
                    '$exists' => 1,
                    '$gt' => 1
                ),
                'bar' => 'test'
            ),
            array('bar')
        );
        $tests[] = array(
            array(
                'foo' => 'test',
                'bar' => array(
                    '$elemMatch' => array(
                        'baz' => 'test',
                        '$or' => array(
                            array('bat' => 'test'),
                            array('qux' => array('$exists' => 1))
                        )
                    )
                )
            ),
            array('foo', 'bar.baz', 'bar.bat')
        );
        return $tests;
    }
    
    /**
     * @dataProvider provideTestExtractOrClauses
     */
    public function testExtractOrClauses($query, $expected)
    {
        $extractor = new FieldExtractor($query);
        $this->assertEquals($expected, $extractor->getOrClauses());
    }
    
    public function provideTestExtractOrClauses()
    {
        $tests = array();
        $tests[] = array(array(), array());
        $tests[] = array(array('foo' => 'test'), array());
        $tests[] = array(
            array(
                'foo' => 'test',
                '$or' => array(
                    array('foo' => 'test'),
                    array('bar' => 'test')
                )
            ),
            array(
                array('foo' => 'test'),
                array('bar' => 'test')
            )
        );
        $tests[] = array(
            array(
                '$or' => array(
                    array('foo' => 'test'),
                    array('bar' => 'test')
                ),
                '$and' => array(
                    array('baz' => 'test'),
                    array(
                        '$or' => array(
                            array('bat' => 'test'),
                            array('qux' => 'test')
                        )
                    )
                )
            ),
            array(
                array('foo' => 'test'),
                array('bar' => 'test'),
                array('bat' => 'test'),
                array('qux' => 'test')
            )
        );
        $tests[] = array(
            array(
                '$or' => array(
                    array('foo' => 'test'),
                    array(
                        '$or' => array(
                            array('bar' => 'test'),
                            array('baz' => 'test')
                        )
                    )
                )
            ),
            array(
                array('foo' => 'test'),
                array('bar' => 'test'),
                array('baz' => 'test'),
            )
        );
        $tests[] = array(
            array(
                'foo' => array(
                    '$elemMatch' => array(
                        '$or' => array(
                            array('foo' => 'test'),
                            array(
                                '$or' => array(
                                    array('bar' => 'test'),
                                    array('baz' => 'test')
                                )
                            )
                        )
                    )
                )
            ),
            array(
                array('foo' => 'test'),
                array('bar' => 'test'),
                array('baz' => 'test')
            )
        );
        return $tests;
    }
    
    /**
     * @dataProvider provideTestGetQueryWithoutOrClauses
     */
    public function testGetQueryWithoutOrClauses($query, $expected)
    {
        $extractor = new FieldExtractor($query);
        $this->assertEquals($expected, $extractor->getQueryWithoutOrClauses());
    }
    
    public function provideTestGetQueryWithoutOrClauses()
    {
        $tests = array();
        $tests[] = array(array(), array());
        $tests[] = array(array('foo' => 'test'), array('foo' => 'test'));
        $tests[] = array(
            array(
                'foo' => 'test',
                '$or' => array(
                    array('foo' => 'test'),
                    array('bar' => 'test')
                )
            ),
            array(
                'foo' => 'test'
            )
        );
        $tests[] = array(
            array(
                '$or' => array(
                    array('foo' => 'test'),
                    array('bar' => 'test')
                ),
                '$and' => array(
                    array('baz' => 'test'),
                    array(
                        '$or' => array(
                            array('bat' => 'test'),
                            array('qux' => 'test')
                        )
                    )
                )
            ),
            array(
                '$and' => array(
                    array('baz' => 'test'),
                )
            )
        );
        $tests[] = array(
            array(
                '$or' => array(
                    array('foo' => 'test'),
                    array(
                        '$or' => array(
                            array('bar' => 'test'),
                            array('baz' => 'test')
                        )
                    )
                )
            ),
            array()
        );
        $tests[] = array(
            array(
                'foo' => array(
                    '$elemMatch' => array(
                        '$or' => array(
                            array('foo' => 'test'),
                            array(
                                '$or' => array(
                                    array('bar' => 'test'),
                                    array('baz' => 'test')
                                )
                            )
                        )
                    )
                )
            ),
            array()
        );
        $tests[] = array(
            array(
                'foo' => array(
                    '$elemMatch' => array(
                        'bat' => 'test',
                        '$or' => array(
                            array('foo' => 'test'),
                            array(
                                '$or' => array(
                                    array('bar' => 'test'),
                                    array('baz' => 'test')
                                )
                            )
                        )
                    )
                )
            ),
             array(
                'foo' => array(
                    '$elemMatch' => array(
                        'bat' => 'test'
                    )
                )
            )
        );
        return $tests;
    }
    
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