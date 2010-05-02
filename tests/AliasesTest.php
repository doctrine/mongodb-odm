<?php

require_once 'TestInit.php';

use Documents\User;

class AliasesTest extends BaseTest
{
    public function testAliases()
    {
        $user = new User();
        $user->aliasTest = 'w00t';
        $this->dm->persist($user);
        $this->dm->flush();

        $user2 = $this->dm->getDocumentCollection('Documents\User')->findOne(array('_id' => new MongoId($user->id)));
        $this->assertEquals('w00t', $user2[0]);

        $user->aliasTest = 'ok';
        $this->dm->flush();

        $user2 = $this->dm->getDocumentCollection('Documents\User')->findOne(array('_id' => new MongoId($user->id)));
        $this->assertEquals('ok', $user2[0]);

        $user = $this->dm->createQuery('Documents\User')
            ->where('aliasTest', 'ok')
            ->getSingleResult();

        $this->assertTrue($user instanceof User);
    }
}