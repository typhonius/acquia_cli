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
                ['ssh:info', 'devcloud:devcloud2'],
                $infoResponse . PHP_EOL
            ]
        ];
    }
}
