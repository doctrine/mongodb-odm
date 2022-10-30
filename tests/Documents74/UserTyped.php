<?php

declare(strict_types=1);

namespace Documents74;

use DateTime;
use DateTimeImmutable;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document() */
#[ODM\Document]
class UserTyped
{
    /** @ODM\Id */
    #[ODM\Id]
    public string $id;

    /** @ODM\Field */
    #[ODM\Field]
    public string $username;

    /** @ODM\Field */
    #[ODM\Field]
    public DateTime $dateTime;

    /** @ODM\Field */
    #[ODM\Field]
    public DateTimeImmutable $dateTimeImmutable;

    /**
     * @ODM\Field
     *
     * @var mixed[]
     */
    #[ODM\Field]
    public array $array;

    /** @ODM\Field */
    #[ODM\Field]
    public bool $boolean;

    /** @ODM\Field */
    #[ODM\Field]
    public float $float;

    /** @ODM\Field */
    #[ODM\Field]
    public int $int;

    /**
     * @ODM\EmbedMany
     *
     * @var CustomCollection<array-key, object>
     */
    #[ODM\EmbedMany]
    public CustomCollection $embedMany;

    /**
     * @ODM\ReferenceMany
     *
     * @var CustomCollection<array-key, object>
     */
    #[ODM\ReferenceMany]
    public CustomCollection $referenceMany;
}
