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
        Type::addType('user_id', __NAMESPACE__ . '\UserIdType');
    }

    public function testCustomIdTypeValueConversions()
    {
        $user = new UserGH1178($id = new UserId(1));
        $this->dm->persist($user);
        $this->dm->flush();

        $this->dm->clear();

        $this->assertEquals($user, $this->dm->find(__NAMESPACE__ . '\UserGH1178', $id));
    }

    public function testRepositoryFindByCustomIdObject()
    {
        $user = new UserGH1178($id = new UserId(1));
        $this->dm->persist($user);
        $this->dm->flush();

        $this->dm->clear();

        $repository = $this->dm->getRepository(UserGH1178::class);
        $this->assertEquals($user, $repository->findOneBy(array('id' => $id)));
    }

    public function testFindByReference()
    {
        $user = new UserGH1178($id = new UserId(1));
        $this->dm->persist($user);

        $comment = new CommentGH1178($user);
        $this->dm->persist($comment);

        $this->dm->flush();

        $query = $this->dm
            ->createQueryBuilder(CommentGH1178::class)
            ->find()
            ->field('user')
            ->references($user)
            ->getQuery();

        $this->assertEquals($comment, $query->getSingleResult());
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
        return $value->value;
    }

    public function closureToPHP()
    {
        return '$return = new \Doctrine\ODM\MongoDB\Tests\Functional\Ticket\GH1178\UserId($value);';
    }

    public function convertToPHPValue($value)
    {
        if ($value === null) {
            return null;
        }

        return new UserId($value);
    }
}

/** @ODM\Document */
class CommentGH1178
{
    /**
     * @ODM\Id
     */
    public $id;

    /**
     * @ODM\ReferenceOne(targetDocument="Doctrine\ODM\MongoDB\Tests\Functional\Ticket\UserGH1178")
     */
    public $user;

    public function __construct(UserGH1178 $user)
    {
        $this->user = $user;
    }
}


/** @ODM\Document */
class UserGH1178
{
    /**
     * @ODM\Id(strategy="NONE", type="user_id")
     */
    public $id;

    public function __construct(UserId $id)
    {
        $this->id = $id;
    }
}
