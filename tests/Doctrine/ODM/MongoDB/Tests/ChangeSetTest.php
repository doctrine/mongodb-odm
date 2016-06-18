<?php

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\ChangeSet\CollectionChangeSet;
use Doctrine\ODM\MongoDB\ChangeSet\FieldChange;
use Documents\Album;
use Documents\Song;

class ChangeSetTest extends BaseTest
{
    public function testCollection()
    {
        $album = new Album('The Mongos');
        $album->addSong(new Song('Demo'));
        $this->dm->persist($album);
        $this->uow->computeChangeSets();
        $this->assertInstanceOf(
            FieldChange::class,
            $this->uow->getDocumentChangeSet($album)['songs'],
            'When document is new collection should be in FieldChange'
        );
        $this->dm->flush();

        $originalSongs = $album->getSongs();
        $album->setSongs(new ArrayCollection([ new Song('Track') ]));
        $this->uow->computeChangeSets();
        $this->assertInstanceOf(
            FieldChange::class,
            $this->uow->getDocumentChangeSet($album)['songs'],
            'Exchanged collection instance should be denoted by FieldChange'
        );
        $this->assertSame($originalSongs, $this->uow->getDocumentChangeSet($album)['songs'][0]);
        $this->assertSame($album->getSongs(), $this->uow->getDocumentChangeSet($album)['songs'][1]);
        $this->dm->flush();

        $oldSong = $album->getSongs()[0];
        $newSong = new Song('Hidden Track');
        $album->getSongs()->removeElement($oldSong);
        $album->addSong($newSong);
        $this->uow->computeChangeSets();
        /** @var CollectionChangeSet $songsChange */
        $songsChange = $this->uow->getDocumentChangeSet($album)['songs'];
        $this->assertInstanceOf(
            CollectionChangeSet::class,
            $songsChange,
            'Mutating collection instance should be denoted by CollectionChangeSet'
        );
        $this->assertCount(1, $songsChange->getDeletedObjects());
        $this->assertSame($oldSong, $songsChange->getDeletedObjects()[0]);
        $this->assertCount(1, $songsChange->getInsertedObjects());
        $this->assertSame($newSong, $songsChange->getInsertedObjects()[0]);
    }
}
