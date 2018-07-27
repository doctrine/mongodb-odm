<?php

declare(strict_types=1);

namespace Documents\Tournament;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document
 * @ODM\InheritanceType("SINGLE_COLLECTION")
 * @ODM\DiscriminatorMap({"football"=TournamentFootball::class,"tennis": TournamentTennis::class})
 */
class Tournament
{
    /** @ODM\Id */
    private $id;

    /** @ODM\Field */
    private $name;

    /** @ODM\ReferenceMany(targetDocument=Participant::class, cascade={"all"}) */
    protected $participants = [];

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

    public function addParticipant(Participant $participant)
    {
        $this->participants[] = $participant;
        $participant->setTournament($this);
    }
}
