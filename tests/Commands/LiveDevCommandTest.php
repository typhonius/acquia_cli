<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;
use AcquiaCli\Tests\Traits\CommandTesterTrait;
use AcquiaCli\Commands\LivedevCommand;

class LiveDevCommandTest extends AcquiaCliTestCase
{
    use CommandTesterTrait;

    public function setUp(): void
    {
        $this->setupCommandTester(LivedevCommand::class);
    }

    /**
     * @dataProvider liveDevProvider
     */
    public function testLiveDevInfo($command, $arguments, $expected)
    {
        list($actualResponse, $statusCode) = $this->executeCommand($command, $arguments);
        $this->assertSame($expected, $actualResponse);
    }

    public function liveDevProvider()
    {

        return [
            [
                'livedev:enable',
                ['uuid' => 'devcloud:devcloud2', 'environment' => 'dev'],
                '>  Enabling livedev for Dev environment'
            ],
            [
                'livedev:disable',
                ['uuid' => 'devcloud:devcloud2', 'environment' => 'dev'],
                '>  Disabling livedev for Dev environment'
            ]
        ];
    }
}
