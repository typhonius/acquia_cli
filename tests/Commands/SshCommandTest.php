<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;
use AcquiaCli\Tests\Traits\CommandTesterTrait;
use AcquiaCli\Commands\SshCommand;

class SshCommandTest extends AcquiaCliTestCase
{
    use CommandTesterTrait;

    public function setUp(): void
    {
        $this->setupCommandTester(SshCommand::class);
    }

    /**
     * @dataProvider sshProvider
     */
    public function testSshInfo($command, $arguments, $expected)
    {
        list($actualResponse, $statusCode) = $this->executeCommand($command, $arguments);
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
                'ssh:info',
                ['uuid' => 'devcloud:devcloud2'],
                $infoResponse
            ],
            [
                'ssh:info',
                ['uuid' => 'devcloud:devcloud2', 'env' => 'dev'],
                $infoResponse,
            ]
        ];
    }
}
