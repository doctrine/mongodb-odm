<?php

namespace Doctrine\ODM\MongoDB\Tests\Mapping\Types;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\Types\Type;
use Doctrine\ODM\MongoDB\Tests\Mapping\Types\CustomTypeException;

require_once __DIR__."/Mapping/Documents/GlobalNamespaceDocument.php";

/**
 * Tests of the commit order calculation.
 *
 * IMPORTANT: When writing tests here consider that a lot of graph constellations
 * can have many valid orderings, so you may want to build a graph that has only
 * 1 valid order to simplify your tests.
 */
class CustomTypeTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{

    public static function setUpBeforeClass()
    {
        Type::addType('date_collection', 'Doctrine\ODM\MongoDB\Tests\Mapping\Types\DateCollectionType');
    }

    /**
     * @expectedException        Doctrine\ODM\MongoDB\Tests\Mapping\Types\CustomTypeException
     * @expectedExceptionMessage Currently converting to DB value
     */
    public function testCustomTypeToDBValueIsCalled()
    {
        $country = new \DoctrineGlobal_Country;
        $country->national_holidays = '2012-07-14';

        $this->dm->persist($country);
        $this->dm->flush();
    }

    public function testCustomTypeToDBValue()
    {
        $country = new \DoctrineGlobal_Country;
        $country->national_holidays = array(new \DateTime, new \DateTime);

        $this->dm->persist($country);
        $this->dm->flush();

        $this->dm->refresh($country);

        $this->assertInstanceOf('\MongoDate', $country->national_holidays[0]);
    }
    
    /**
     * @expectedException        Doctrine\ODM\MongoDB\Tests\Mapping\Types\CustomTypeException
     * @expectedExceptionMessage Currently converting to PHP value
     */
    public function testCustomTypeToPHPValue()
    {
        $country = new \DoctrineGlobal_Country;
        $country->national_holidays = array(new \DateTime, new \DateTime);

        $this->dm->persist($country);
        $this->dm->flush();

        $this->dm->refresh($country);
    }
}