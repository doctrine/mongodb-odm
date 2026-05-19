<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\Common\EventSubscriber;
use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Doctrine\ODM\MongoDB\Event\PreUpdateEventArgs;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\ForumUser;
use Throwable;

/**
 * Verifies that the per-phase bulkWrite refactor preserves the lifecycle
 * event contract — preUpdate fires before the write goes to the server,
 * postUpdate fires after, and postPersist only fires if the bulk succeeded.
 */
final class BulkWriteLifecycleEventTest extends BaseTestCase
{
    public function testPreUpdateFiresBeforeBulkWriteAndPostUpdateAfter(): void
    {
        $user           = new ForumUser();
        $user->username = 'pre';
        $this->dm->persist($user);
        $this->dm->flush();

        $subscriber = new BulkWriteLifecycleSubscriber();
        $this->dm->getEventManager()->addEventSubscriber($subscriber);

        $user->username = 'post';
        $this->dm->flush();

        self::assertSame(['preUpdate', 'postUpdate'], $subscriber->events);
        self::assertSame(['preUpdate' => 'post', 'postUpdate' => 'post'], $subscriber->usernames);
        // preUpdate fired with a non-empty change set; the BulkWriteQueue had not yet been drained.
        self::assertSame(['username' => ['pre', 'post']], $subscriber->preUpdateChangeSet);
    }

    public function testPostPersistDoesNotFireWhenBulkWriteFails(): void
    {
        $subscriber = new BulkWriteLifecycleSubscriber();
        $this->dm->getEventManager()->addEventSubscriber($subscriber);

        $this->createFailPoint('insert');

        $user           = new ForumUser();
        $user->username = 'fails';
        $this->dm->persist($user);

        try {
            $this->dm->flush();
            self::fail('Expected the configured fail point to make bulkWrite throw.');
        } catch (Throwable) {
            // Expected — the bulkWrite returned an error.
        }

        self::assertNotContains('postPersist', $subscriber->events, 'postPersist must not fire if the per-collection bulkWrite raised an error.');
    }
}

class BulkWriteLifecycleSubscriber implements EventSubscriber
{
    /** @var list<string> */
    public array $events = [];

    /** @var array<string, string> */
    public array $usernames = [];

    /** @var array<string, array{mixed, mixed}> */
    public array $preUpdateChangeSet = [];

    public function getSubscribedEvents(): array
    {
        return [Events::preUpdate, Events::postUpdate, Events::postPersist, Events::postRemove];
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $document = $args->getDocument();
        if (! $document instanceof ForumUser) {
            return;
        }

        $this->events[]               = 'preUpdate';
        $this->usernames['preUpdate'] = $document->username;
        $this->preUpdateChangeSet     = $args->getDocumentChangeSet();
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $document = $args->getDocument();
        if (! $document instanceof ForumUser) {
            return;
        }

        $this->events[]                = 'postUpdate';
        $this->usernames['postUpdate'] = $document->username;
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $document = $args->getDocument();
        if (! $document instanceof ForumUser) {
            return;
        }

        $this->events[] = 'postPersist';
    }

    public function postRemove(LifecycleEventArgs $args): void
    {
        $document = $args->getDocument();
        if (! $document instanceof ForumUser) {
            return;
        }

        $this->events[] = 'postRemove';
    }
}
