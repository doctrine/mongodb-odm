<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class ReferenceDiscriminatorsTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function setUp()
    {
        parent::setUp();
        $this->dm->getSchemaManager()->ensureDocumentIndexes(__NAMESPACE__ . '\CommentableAct');
        $this->dm->getSchemaManager()->ensureDocumentIndexes(__NAMESPACE__ . '\ProgramMainDashboardItem');
        $this->dm->getSchemaManager()->ensureDocumentIndexes(__NAMESPACE__ . '\ProgramMembersDashboardItem');
        $this->dm->getSchemaManager()->ensureDocumentIndexes(__NAMESPACE__ . '\UserDashboardItem');
        $this->dm->getSchemaManager()->ensureDocumentIndexes(__NAMESPACE__ . '\UserProfileItem');
    }

    public function testReferenceDiscriminators()
    {
        $this->dm->persist($commentableAct = new CommentableAct('user_status_update'));
        $this->dm->persist($programMainDashboardItem = new ProgramMainDashboardItem($commentableAct, 'fitness_bootcamp'));
        $this->dm->persist($programMemberDashboardItem = new ProgramMembersDashboardItem($commentableAct, 'fitness_bootcamp'));
        $this->dm->persist($userDashboardItem = new UserDashboardItem($commentableAct, 'bob'));
        $this->dm->persist($userProfileItem = new UserProfileItem($commentableAct, 'bob'));
        $this->dm->flush();
        $this->dm->clear();

        $commentableAct = $this->dm->find(__NAMESPACE__ . '\CommentableAct', $commentableAct->getId());
        $programMainDashboardItem = $this->dm->find(__NAMESPACE__ . '\ProgramMainDashboardItem', $programMainDashboardItem->getId());
        $programMemberDashboardItem = $this->dm->find(__NAMESPACE__ . '\ProgramMembersDashboardItem', $programMemberDashboardItem->getId());
        $userDashboardItem = $this->dm->find(__NAMESPACE__ . '\UserDashboardItem', $userDashboardItem->getId());
        $userProfileItem = $this->dm->find(__NAMESPACE__ . '\UserProfileItem', $userProfileItem->getId());

        $this->assertSame($commentableAct, $programMainDashboardItem->getAct());
        $this->assertSame($commentableAct, $programMemberDashboardItem->getAct());
        $this->assertSame($commentableAct, $userDashboardItem->getAct());
        $this->assertSame($commentableAct, $userProfileItem->getAct());
    }
}

/**
* @ODM\Document(collection="act_act")
* @ODM\InheritanceType("SINGLE_COLLECTION")
* @ODM\DiscriminatorField(fieldName="discriminator")
* @ODM\DiscriminatorMap({"act"="Act", "commentable_act"="CommentableAct"})
*/
class Act
{
    /** @ODM\Id */
    protected $id;

    /** @ODM\String */
    protected $type;

    public function __construct($type)
    {
        $this->type = $type;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getType()
    {
        return $this->type;
    }
}

/** @ODM\Document */
class CommentableAct extends Act
{
    /**
     * @ODM\Collection
     **/
    protected $comments = array();

    public function __construct($type, array $comments = array())
    {
        parent::__construct($type);
        $this->comments = $comments;
    }

    public function getComments()
    {
        return $this->comments;
    }
}

/** @ODM\MappedSuperclass */
abstract class FeedItem
{
    /** @ODM\Id */
    protected $id;

    /** @ODM\ReferenceOne(targetDocument="Act") */
    protected $act;

    public function __construct(Act $act)
    {
        $this->act = $act;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getAct()
    {
        return $this->act;
    }
}

/**
 * @ODM\MappedSuperclass
 * @ODM\UniqueIndex(keys={"programId"="asc", "act.$id"="asc"}, options={"unique"="true", "dropDups"="true"})
 */
abstract class ProgramFeedItem extends FeedItem
{
    /** @ODM\String */
    protected $programId;

    public function __construct(Act $act, $programId)
    {
        parent::__construct($act);
        $this->programId = $programId;
    }

    public function getProgramId()
    {
        return $this->programId;
    }
}

/** @ODM\Document(collection="act_program_main_dashboard_item") */
class ProgramMainDashboardItem extends ProgramFeedItem
{
}

/** @ODM\Document(collection="act_program_members_dashboard_item") */
class ProgramMembersDashboardItem extends ProgramFeedItem
{
}

/**
 * @ODM\MappedSuperclass
 * @ODM\UniqueIndex(keys={"userId"="asc", "act.$id"="asc"}, options={"unique"="true", "dropDups"="true"})
 */
abstract class UserFeedItem extends FeedItem
{
    /** @ODM\String */
    protected $userId;

    public function __construct(Act $act, $userId)
    {
        parent::__construct($act);
        $this->userId = $userId;
    }

    public function getUserId()
    {
        return $this->userId;
    }
}

/** @ODM\Document(collection="act_user_dashboard_item") */
class UserDashboardItem extends ProgramFeedItem
{
}

/** @ODM\Document(collection="act_user_profile_item") */
class UserProfileItem extends ProgramFeedItem
{
}
