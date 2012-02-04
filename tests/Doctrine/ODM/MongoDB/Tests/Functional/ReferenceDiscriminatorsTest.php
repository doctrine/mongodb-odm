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

    /**
     * This test demonstrates a CommentableAct being published to feed items.
     */
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

    /**
     * This tests demonstrates a race condition between two requests which are
     * both publishing a CommentableAct to feed items.
     */
    public function testReferenceDiscriminatorsRaceCondition()
    {
        $this->dm->persist($commentableAct1 = new CommentableAct('user_status_update'));
        $this->dm->persist($programMainDashboardItem1 = new ProgramMainDashboardItem($commentableAct1, 'fitness_bootcamp'));
        $this->dm->persist($programMemberDashboardItem1 = new ProgramMembersDashboardItem($commentableAct1, 'fitness_bootcamp'));
        $this->dm->persist($userDashboardItem1 = new UserDashboardItem($commentableAct1, 'bob'));
        $this->dm->persist($userProfileItem1 = new UserProfileItem($commentableAct1, 'bob'));

        $this->dm->persist($commentableAct2 = new CommentableAct('user_status_update'));
        $this->dm->persist($programMainDashboardItem2 = new ProgramMainDashboardItem($commentableAct2, 'fitness_bootcamp'));
        $this->dm->persist($programMemberDashboardItem2 = new ProgramMembersDashboardItem($commentableAct2, 'fitness_bootcamp'));
        $this->dm->persist($userDashboardItem2 = new UserDashboardItem($commentableAct2, 'bob'));
        $this->dm->persist($userProfileItem2 = new UserProfileItem($commentableAct2, 'bob'));

        $this->dm->flush();
        $this->dm->clear();

        $commentableAct1 = $this->dm->find(__NAMESPACE__ . '\CommentableAct', $commentableAct1->getId());
        $programMainDashboardItem1 = $this->dm->find(__NAMESPACE__ . '\ProgramMainDashboardItem', $programMainDashboardItem1->getId());
        $programMemberDashboardItem1 = $this->dm->find(__NAMESPACE__ . '\ProgramMembersDashboardItem', $programMemberDashboardItem1->getId());
        $userDashboardItem1 = $this->dm->find(__NAMESPACE__ . '\UserDashboardItem', $userDashboardItem1->getId());
        $userProfileItem1 = $this->dm->find(__NAMESPACE__ . '\UserProfileItem', $userProfileItem1->getId());

        $commentableAct2 = $this->dm->find(__NAMESPACE__ . '\CommentableAct', $commentableAct2->getId());
        $programMainDashboardItem2 = $this->dm->find(__NAMESPACE__ . '\ProgramMainDashboardItem', $programMainDashboardItem2->getId());
        $programMemberDashboardItem2 = $this->dm->find(__NAMESPACE__ . '\ProgramMembersDashboardItem', $programMemberDashboardItem2->getId());
        $userDashboardItem2 = $this->dm->find(__NAMESPACE__ . '\UserDashboardItem', $userDashboardItem2->getId());
        $userProfileItem2 = $this->dm->find(__NAMESPACE__ . '\UserProfileItem', $userProfileItem2->getId());

        $this->assertSame($commentableAct1, $programMainDashboardItem1->getAct());
        $this->assertSame($commentableAct1, $programMemberDashboardItem1->getAct());
        $this->assertSame($commentableAct1, $userDashboardItem1->getAct());
        $this->assertSame($commentableAct1, $userProfileItem1->getAct());

        $this->assertSame($commentableAct2, $programMainDashboardItem2->getAct());
        $this->assertSame($commentableAct2, $programMemberDashboardItem2->getAct());
        $this->assertSame($commentableAct2, $userDashboardItem2->getAct());
        $this->assertSame($commentableAct2, $userProfileItem2->getAct());
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
