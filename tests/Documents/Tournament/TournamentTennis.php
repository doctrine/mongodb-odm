<?php

declare(strict_types=1);

namespace Documents\Tournament;

use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;

#[ODM\Document]
class TournamentTennis extends Tournament
{
}
