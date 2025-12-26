<?php

declare(strict_types=1);

namespace Documents;

use DateTime;
use DateTimeImmutable;
use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;

#[ODM\Document]
class UserTyped
{
    #[ODM\Id]
    public string $id;

    #[ODM\Field]
    public string $username;

    #[ODM\Field]
    public DateTime $dateTime;

    #[ODM\Field]
    public DateTimeImmutable $dateTimeImmutable;

    /** @var mixed[] */
    #[ODM\Field]
    public array $array;

    #[ODM\Field]
    public bool $boolean;

    #[ODM\Field]
    public float $float;

    #[ODM\Field]
    public int $int;

    /** @var CustomCollection<array-key, object> */
    #[ODM\EmbedMany]
    public CustomCollection $embedMany;

    /** @var CustomCollection<array-key, object> */
    #[ODM\ReferenceMany]
    public CustomCollection $referenceMany;
}
