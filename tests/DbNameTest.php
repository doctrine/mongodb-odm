<?php

require_once 'TestInit.php';

use Documents\Account;

class DbNameTest extends BaseTest
{
    public function testPrefixAndSuffixForDbName()
    {
        $accountClass = get_class(new Account);

        $db1 = $this->dm->getDocumentDB($accountClass);

        $this->dm->getConfiguration()->setPrefixDbName('test_');
        $db2 = $this->dm->getDocumentDB($accountClass);

        $this->dm->getConfiguration()->setSuffixDbName('_test');
        $db3 = $this->dm->getDocumentDB($accountClass);

        $this->assertTrue($db1 !== $db2);
        $this->assertTrue($db1 !== $db3);
        $this->assertTrue($db2 !== $db3);
    }
}
