<?php

namespace Documents\Tournament;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document
 * @ODM\InheritanceType("SINGLE_COLLECTION")
 * @ODm\DiscriminatorMap({"solo"="ParticipantSolo", "team"="ParticipantTeam"})
 */
class Participant
{
    /** @ODM\Id */
    private $id;

    /** @ODM\Field */
    private $name;

    /** @ODM\ReferenceOne(targetDocument="Tournament", cascade={"all"}) */
    protected $tournament;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setTournament(Tournament $tournament)
    {
        $this->tournament = $tournament;
    }

    public function getTournament()
    {
        return $this->tournament;
    }
}
