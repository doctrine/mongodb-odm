<?php

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\ChangeSet\CollectionChangeSet;
use Doctrine\ODM\MongoDB\ChangeSet\FieldChange;
use Doctrine\ODM\MongoDB\ChangeSet\ObjectChangeSet;
use Documents\Album;
use Documents\Ecommerce\Currency;
use Documents\Ecommerce\Money;
use Documents\Ecommerce\StockItem;
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
            'When document is new, collection should be in FieldChange'
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
        $newSong = new Song('Track #1');
        $hiddenSong = new Song('Hidden Track');
        $album->getSongs()->removeElement($oldSong);
        $album->addSong($newSong);
        $album->addSong($hiddenSong);
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
        $this->assertCount(2, $songsChange->getInsertedObjects());
        $this->assertSame($newSong, $songsChange->getInsertedObjects()[0]);
        $this->assertSame($hiddenSong, $songsChange->getInsertedObjects()[1]);
        $this->dm->flush();
        
        $newSong->setName('Really long track');
        $hiddenSong->setName('Surprise!');
        $this->uow->computeChangeSets();
        /** @var CollectionChangeSet $songsChange */
        $songsChange = $this->uow->getDocumentChangeSet($album)['songs'];
        $this->assertCount(2, $songsChange->getChangedObjects());
        $expected = [
            [ 'Track #1', 'Really long track' ],
            [ 'Hidden Track', 'Surprise!' ],
        ];
        foreach ($songsChange->getChangedObjects() as $i => $changedObject) {
            $this->assertCount(1, $changedObject);
            $this->assertSame($expected[$i][0], $changedObject['name'][0]);
            $this->assertSame($expected[$i][1], $changedObject['name'][1]);
        }
    }
    
    public function testEmbedOne()
    {
        $euro = new Currency('EURO');
        $item = new StockItem('Google', new Money(100, $euro));
        $this->dm->persist($item);
        $this->uow->computeChangeSets();
        $this->assertInstanceOf(
            FieldChange::class,
            $this->uow->getDocumentChangeSet($item)['cost'],
            'When document is new embed one should be in FieldChange'
        );
        $this->dm->flush();

        $originalCost = $item->getCostInstance();
        $item->setCost(new Money(200, $euro));
        $this->uow->computeChangeSets();
        $this->assertInstanceOf(
            FieldChange::class,
            $this->uow->getDocumentChangeSet($item)['cost'],
            'Exchanged embed one instance should be denoted by FieldChange'
        );
        $this->assertSame($originalCost, $this->uow->getDocumentChangeSet($item)['cost'][0]);
        $this->assertSame($item->getCostInstance(), $this->uow->getDocumentChangeSet($item)['cost'][1]);
        $this->dm->flush();
        
        $usd = new Currency('USD');
        $item->getCostInstance()->setCurrency($usd);
        $this->uow->computeChangeSets();
        $costChange = $this->uow->getDocumentChangeSet($item)['cost'];
        $this->assertInstanceOf(
            ObjectChangeSet::class,
            $costChange,
            'Mutating embed one instance should be denoted by ObjectChangeSet'
        );
        $this->assertCount(1, $costChange);
        $this->assertInstanceOf(FieldChange::class, $costChange['currency']);
        $this->assertSame($euro, $costChange['currency'][0]);
        $this->assertSame($usd, $costChange['currency'][1]);
    }
}
