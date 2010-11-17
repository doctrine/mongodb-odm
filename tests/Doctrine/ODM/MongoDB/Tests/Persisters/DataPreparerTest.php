<?php

namespace Doctrine\ODM\MongoDB\Tests\Persisters;

class DataPreparerTest extends \PHPUnit_Framework_TestCase
{
    private $dataPreparer;
    private $dm;
    private $uow;
    
    public function setUp()
    {
        $this->dm = $this->getMockDocumentManager();
        $this->uow = $this->getMockUnitOfWork();
        $this->dataPreparer = new DataPreparer($this->dm, $this->uow, '$');
    }
    
    public function testPrepareInsertData()
    {
        
    }

}