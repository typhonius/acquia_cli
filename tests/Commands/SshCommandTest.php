<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;

class SshCommandTest extends AcquiaCliTestCase
{

    /**
     * @dataProvider sshProvider
     */
    public function testSshInfo($command, $expected)
    {
        $actualResponse = $this->execute($command);
        $this->assertSame($expected, $actualResponse);
    }

    public function sshProvider()
    {

        $infoResponse = <<<INFO
>  dev: ssh 
>  prod: ssh 
>  test: ssh 
INFO;

        return [
            [
                ['ssh:info', 'a47ac10b-58cc-4372-a567-0e02b2c3d470'],
                $infoResponse . PHP_EOL
            ]
        ];
    }
}
