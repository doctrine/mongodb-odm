<?php

declare(strict_types=1);

namespace Documents\Tournament;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document
 * @ODM\InheritanceType("SINGLE_COLLECTION")
 * @ODM\DiscriminatorMap({"solo"=ParticipantSolo::class, "team"=ParticipantTeam::class})
 */
class Participant
{
    /** @ODM\Id */
    private $id;

    /** @ODM\Field */
    private $name;

    /** @ODM\ReferenceOne(targetDocument=Tournament::class, cascade={"all"}) */
    protected $tournament;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setName($name): void
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setTournament(Tournament $tournament): void
    {
        $this->tournament = $tournament;
    }

    public function getTournament()
    {
        return $this->tournament;
    }
}
