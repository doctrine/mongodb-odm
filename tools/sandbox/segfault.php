<?php

require_once __DIR__ . '/config.php';

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

// Your code here...

try{
$qb = $dm->createQueryBuilder('DoesNotRequireIndexesDocument')
    ->field('notIndexed')->equals('test')
    ->requireIndexes();
$query = $qb->getQuery();
$query->execute();
} catch (Doctrine\ODM\MongoDB\MongoDBException $e) {}

/**
 * @ODM\Document(requireIndexes=false)
 */
class DoesNotRequireIndexesDocument
{
    /** @ODM\Id */
    public $id;

    /** @ODM\String @ODM\Index */
    public $indexed;

    /** @ODM\String */
    public $notIndexed;
}
