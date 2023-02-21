<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class ReferenceDiscriminatorsTest extends BaseTest
{
    public function setUp(): void
    {
        parent::setUp();

        $this->dm->getSchemaManager()->ensureDocumentIndexes(CommentableAction::class);
        $this->dm->getSchemaManager()->ensureDocumentIndexes(GroupMainActivityStreamItem::class);
        $this->dm->getSchemaManager()->ensureDocumentIndexes(GroupMembersActivityStreamItem::class);
        $this->dm->getSchemaManager()->ensureDocumentIndexes(UserDashboardActivityStreamItem::class);
        $this->dm->getSchemaManager()->ensureDocumentIndexes(UserProfileActivityStreamItem::class);
    }

    /**
     * This test demonstrates a CommentableAction being published to activity streams.
     */
    public function testReferenceDiscriminators(): void
    {
        $this->dm->persist($commentableAction           = new CommentableAction('actionType'));
        $this->dm->persist($groupMainActivityStreamItem = new GroupMainActivityStreamItem($commentableAction, 'groupId'));
        $this->dm->persist($groupMemberActivityStreamItem   = new GroupMembersActivityStreamItem($commentableAction, 'groupId'));
        $this->dm->persist($userDashboardActivityStreamItem = new UserDashboardActivityStreamItem($commentableAction, 'userId'));
        $this->dm->persist($userProfileActivityStreamItem = new UserProfileActivityStreamItem($commentableAction, 'userId'));

        $this->dm->flush();
        $this->dm->clear();

        $commentableAction               = $this->dm->find(CommentableAction::class, $commentableAction->getId());
        $groupMainActivityStreamItem     = $this->dm->find(GroupMainActivityStreamItem::class, $groupMainActivityStreamItem->getId());
        $groupMemberActivityStreamItem   = $this->dm->find(GroupMembersActivityStreamItem::class, $groupMemberActivityStreamItem->getId());
        $userDashboardActivityStreamItem = $this->dm->find(UserDashboardActivityStreamItem::class, $userDashboardActivityStreamItem->getId());
        $userProfileActivityStreamItem   = $this->dm->find(UserProfileActivityStreamItem::class, $userProfileActivityStreamItem->getId());

        self::assertSame($commentableAction, $groupMainActivityStreamItem->getAction());
        self::assertSame($commentableAction, $groupMemberActivityStreamItem->getAction());
        self::assertSame($commentableAction, $userDashboardActivityStreamItem->getAction());
        self::assertSame($commentableAction, $userProfileActivityStreamItem->getAction());
    }

    /**
     * This tests demonstrates a race condition between two requests which are
     * both publishing a CommentableAction to activity streams.
     */
    public function testReferenceDiscriminatorsRaceCondition(): void
    {
        $this->dm->persist($commentableAction1           = new CommentableAction('actionType'));
        $this->dm->persist($groupMainActivityStreamItem1 = new GroupMainActivityStreamItem($commentableAction1, 'groupId'));
        $this->dm->persist($groupMemberActivityStreamItem1   = new GroupMembersActivityStreamItem($commentableAction1, 'groupId'));
        $this->dm->persist($userDashboardActivityStreamItem1 = new UserDashboardActivityStreamItem($commentableAction1, 'userId'));
        $this->dm->persist($userProfileActivityStreamItem1 = new UserProfileActivityStreamItem($commentableAction1, 'userId'));

        $this->dm->persist($commentableAction2           = new CommentableAction('actionType'));
        $this->dm->persist($groupMainActivityStreamItem2 = new GroupMainActivityStreamItem($commentableAction2, 'groupId'));
        $this->dm->persist($groupMemberActivityStreamItem2   = new GroupMembersActivityStreamItem($commentableAction2, 'groupId'));
        $this->dm->persist($userDashboardActivityStreamItem2 = new UserDashboardActivityStreamItem($commentableAction2, 'userId'));
        $this->dm->persist($userProfileActivityStreamItem2 = new UserProfileActivityStreamItem($commentableAction2, 'userId'));

        $this->dm->flush();
        $this->dm->clear();

        $commentableAction1               = $this->dm->find(CommentableAction::class, $commentableAction1->getId());
        $groupMainActivityStreamItem1     = $this->dm->find(GroupMainActivityStreamItem::class, $groupMainActivityStreamItem1->getId());
        $groupMemberActivityStreamItem1   = $this->dm->find(GroupMembersActivityStreamItem::class, $groupMemberActivityStreamItem1->getId());
        $userDashboardActivityStreamItem1 = $this->dm->find(UserDashboardActivityStreamItem::class, $userDashboardActivityStreamItem1->getId());
        $userProfileActivityStreamItem1   = $this->dm->find(UserProfileActivityStreamItem::class, $userProfileActivityStreamItem1->getId());

        $commentableAction2               = $this->dm->find(CommentableAction::class, $commentableAction2->getId());
        $groupMainActivityStreamItem2     = $this->dm->find(GroupMainActivityStreamItem::class, $groupMainActivityStreamItem2->getId());
        $groupMemberActivityStreamItem2   = $this->dm->find(GroupMembersActivityStreamItem::class, $groupMemberActivityStreamItem2->getId());
        $userDashboardActivityStreamItem2 = $this->dm->find(UserDashboardActivityStreamItem::class, $userDashboardActivityStreamItem2->getId());
        $userProfileActivityStreamItem2   = $this->dm->find(UserProfileActivityStreamItem::class, $userProfileActivityStreamItem2->getId());

        self::assertSame($commentableAction1, $groupMainActivityStreamItem1->getAction());
        self::assertSame($commentableAction1, $groupMemberActivityStreamItem1->getAction());
        self::assertSame($commentableAction1, $userDashboardActivityStreamItem1->getAction());
        self::assertSame($commentableAction1, $userProfileActivityStreamItem1->getAction());

        self::assertSame($commentableAction2, $groupMainActivityStreamItem2->getAction());
        self::assertSame($commentableAction2, $groupMemberActivityStreamItem2->getAction());
        self::assertSame($commentableAction2, $userDashboardActivityStreamItem2->getAction());
        self::assertSame($commentableAction2, $userProfileActivityStreamItem2->getAction());
    }
}

/**
 * @ODM\Document(collection="rdt_action")
 * @ODM\InheritanceType("SINGLE_COLLECTION")
 * @ODM\DiscriminatorField("discriminator")
 * @ODM\DiscriminatorMap({"action"=Action::class, "commentable_action"=CommentableAction::class})
 */
class Action
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    protected $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    protected $type;

    public function __construct(string $type)
    {
        $this->type = $type;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }
}

/** @ODM\Document */
class CommentableAction extends Action
{
    /**
     * @ODM\Field(type="collection") *
     *
     * @var string[]
     */
    protected $comments = [];

    /** @param string[] $comments */
    public function __construct(string $type, array $comments = [])
    {
        parent::__construct($type);

        $this->comments = $comments;
    }

    /** @return string[] */
    public function getComments(): array
    {
        return $this->comments;
    }
}

/** @ODM\MappedSuperclass */
abstract class ActivityStreamItem
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    protected $id;

    /**
     * @ODM\ReferenceOne(targetDocument=Action::class)
     *
     * @var Action
     */
    protected $action;

    public function __construct(Action $action)
    {
        $this->action = $action;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getAction(): Action
    {
        return $this->action;
    }
}

/**
 * @ODM\MappedSuperclass
 * @ODM\UniqueIndex(keys={"groupId"="asc", "action.$id"="asc"}, options={"unique"=true})
 */
abstract class GroupActivityStreamItem extends ActivityStreamItem
{
    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    protected $groupId;

    public function __construct(Action $action, string $groupId)
    {
        parent::__construct($action);

        $this->groupId = $groupId;
    }

    public function getGroupId(): string
    {
        return $this->groupId;
    }
}

/** @ODM\Document(collection="rdt_group_main_activity_stream_item") */
class GroupMainActivityStreamItem extends GroupActivityStreamItem
{
}

/** @ODM\Document(collection="rdt_group_members_activity_stream_item") */
class GroupMembersActivityStreamItem extends GroupActivityStreamItem
{
}

/**
 * @ODM\MappedSuperclass
 * @ODM\UniqueIndex(keys={"userId"="asc", "action.$id"="asc"}, options={"unique"=true})
 */
abstract class UserActivityStreamItem extends ActivityStreamItem
{
    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    protected $userId;

    public function __construct(Action $action, string $userId)
    {
        parent::__construct($action);

        $this->userId = $userId;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }
}

/** @ODM\Document(collection="rdt_user_dashboard_activity_stream_item") */
class UserDashboardActivityStreamItem extends UserActivityStreamItem
{
}

/** @ODM\Document(collection="rdt_user_profile_activity_stream_item") */
class UserProfileActivityStreamItem extends UserActivityStreamItem
{
}
