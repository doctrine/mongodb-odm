<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\Functional\Ticket\GH1178\UserId;
use Doctrine\ODM\MongoDB\Types\Type;

/**
 * @author Marcos Passos <marcos@croct.com>
 *
 * @group GH-1178
 *
 * @see   https://github.com/doctrine/mongodb-odm/issues/GH1178
 */
class GH1178Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public static function setUpBeforeClass()
    {
        Type::addType('user_id_gh1178', __NAMESPACE__ . '\UserIdType');
    }

    public function testCustomIdTypeValueConversions()
    {
        $user = new User($id = new UserId(1));
        $user->date = new \DateTime();

        $this->dm->persist($user);
        $this->dm->flush();

        $this->dm->clear();

        $user = $this->dm->find(__NAMESPACE__ . '\User', 1);

        $this->assertEquals('UserIdGH1178', $user->id);
    }
}

class UserIdType extends Type
{
    public function closureToMongo()
    {
        return '$return = $value->value;';
    }

    public function convertToDatabaseValue($value)
    {
        if (!$value instanceof UserId) {
            return $value;
        }

      //  var_dump($value);
        return $value->value;
    }

    public function closureToPHP()
    {
        return '$return = new Doctrine\ODM\MongoDB\Tests\Functional\Ticket\GH1178\UserId($value);';
    }

    public function convertToPHPValue($value)
    {
        var_dump($value);
        if ($value === null) {
            return null;
        }

        return new UserId($value);
    }
}

/** @ODM\Document */
class User
{
    /**
     * @ODM\Id(strategy="NONE", type="user_id_gh1178")
     */
    public $id;

    /**
     * @ODM\Field(type="string");
     */
    public $test;

    /**
     * @ODM\Field(type="date");
     */
    public $date;

    public function __construct(UserId $id)
    {
        $this->id = 1;
    }
}
