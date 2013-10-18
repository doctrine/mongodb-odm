<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\Tournament\ParticipantTeam;
use Documents\Tournament\TournamentFootball;

class BidirectionalInheritanceTest extends BaseTest
{
    /**
     * Test bi-directional reference "one to many", both owning sides and with inheritance maps.
     */
    public function testOneToManyWithoutSides()
    {
        $tournament = new TournamentFootball('tournament_name');

        $participant1 = new ParticipantTeam('name1');
        $tournament->addParticipant($participant1);

        $participant2 = new ParticipantTeam('name2');
        $tournament->addParticipant($participant2);

        $this->dm->persist($tournament);
        $this->dm->flush();

        $this->assertTrue(true, 'Should not provoke an infinite loop');
    }
}
