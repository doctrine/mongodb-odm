<?php

declare(strict_types=1);

namespace Documents\Tournament;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
#[ODM\InheritanceType('SINGLE_COLLECTION')]
#[ODM\DiscriminatorMap(['football' => TournamentFootball::class, 'tennis' => TournamentTennis::class])]
class Tournament
{
    /** @var string|null */
    #[ODM\Id]
    private $id;

    /** @var string */
    #[ODM\Field]
    private $name;

    /** @var Collection<int, Participant>|array<Participant> */
    #[ODM\ReferenceMany(targetDocument: Participant::class, cascade: ['all'])]
    protected $participants = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function addParticipant(Participant $participant): void
    {
        $this->participants[] = $participant;
        $participant->setTournament($this);
    }
}
