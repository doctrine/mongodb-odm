<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Doctrine\Persistence\NotifyPropertyChanged;
use Doctrine\Persistence\PropertyChangedListener;

class ChangeTrackingNotifyTest extends BaseTestCase
{
    public function testOriginalValueIsKeptWhenAFieldChangesTwiceBeforeFlush(): void
    {
        $document = new NotifyChangeTrackingDocument();
        $this->dm->persist($document);
        $this->dm->flush();

        $id = $document->getId();

        $document->incrementCount();
        $document->incrementCount(2);

        $this->dm->flush();
        $this->dm->clear();

        $document = $this->dm->find(NotifyChangeTrackingDocument::class, $id);

        self::assertInstanceOf(NotifyChangeTrackingDocument::class, $document);
        self::assertSame(3, $document->getCount());
    }
}

#[ODM\Document]
#[ODM\ChangeTrackingPolicy('NOTIFY')]
class NotifyChangeTrackingDocument implements NotifyPropertyChanged
{
    /** @var string|null */
    #[ODM\Id]
    private $id;

    /** @var int|null */
    #[ODM\Field(type: 'int', strategy: 'increment')]
    private $count;

    /** @var list<PropertyChangedListener> */
    private $listeners = [];

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getCount(): ?int
    {
        return $this->count;
    }

    public function incrementCount(int $amount = 1): void
    {
        $newValue = ($this->count ?? 0) + $amount;

        foreach ($this->listeners as $listener) {
            $listener->propertyChanged($this, 'count', $this->count, $newValue);
        }

        $this->count = $newValue;
    }

    public function addPropertyChangedListener(PropertyChangedListener $listener): void
    {
        $this->listeners[] = $listener;
    }
}
