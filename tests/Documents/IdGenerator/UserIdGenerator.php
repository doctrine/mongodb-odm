<?php

namespace Documents\IdGenerator;

class UserIdGenerator extends \Doctrine\ODM\MongoDB\Id\AbstractIdGenerator
{
    
    /* (non-PHPdoc)
     * @see \Doctrine\ODM\MongoDB\Id\AbstractIdGenerator::generate()
     */
    public function generate(\Doctrine\ODM\MongoDB\DocumentManager $dm, $document)
    {
        if(!$document->getUsername()){
            throw new \Exception('username is required to make an id');
        }
        return md5($document->getUsername());
    }
    
}