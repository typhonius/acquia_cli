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

        $actualResponse = $this->execute($command);

        $expectedResponse = '>  dev: ssh 
>  prod: ssh 
>  test: ssh 
';

        $this->assertSame($expectedResponse, $actualResponse);
    }
}
