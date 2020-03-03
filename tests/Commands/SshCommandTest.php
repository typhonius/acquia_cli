<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;

class SshCommandTest extends AcquiaCliTestCase
{

    public function testSshInfo()
    {

        $command = [
            'ssh:info',
            'a47ac10b-58cc-4372-a567-0e02b2c3d470'
        ];

        $getAllFixture = $this->getPsr7JsonResponseForFixture('Environments/getAllEnvironments.json');
        $client = $this->getMockClient($getAllFixture);

        $actualResponse = $this->execute($client, $command);
        $expectedResponse = '>  dev: ssh 
>  prod: ssh 
>  test: ssh 
';

        $this->assertSame($expectedResponse, $actualResponse);
    }
}
