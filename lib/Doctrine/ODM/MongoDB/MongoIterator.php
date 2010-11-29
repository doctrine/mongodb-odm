<?php

namespace Doctrine\ODM\MongoDB;

use Countable, Iterator;

interface MongoIterator extends Iterator, Countable
{
    function toArray();
    function getSingleResult();
}