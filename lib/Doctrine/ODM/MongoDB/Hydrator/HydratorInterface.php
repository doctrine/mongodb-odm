<?php

namespace Doctrine\ODM\MongoDB\Hydrator;

interface HydratorInterface
{
    function hydrate($document, $data);
}