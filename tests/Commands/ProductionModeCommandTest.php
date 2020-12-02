<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;
use AcquiaCli\Tests\Traits\CommandTesterTrait;
use AcquiaCli\Commands\ProductionModeCommand;

class ProductionModeCommandTest extends AcquiaCliTestCase
{
    use CommandTesterTrait;

    public function setUp(): void
    {
        $this->setupCommandTester(ProductionModeCommand::class);
    }

    /**
     * @dataProvider productionModeProvider
     */
    public function testProductionModeCommands($command, $arguments, $expected)
    {
        list($actualResponse, $statusCode) = $this->executeCommand($command, $arguments);
        $this->assertSame($expected, $actualResponse);
    }

    public function productionModeProvider()
    {

        $infoResponse = <<<INFO
>  dev: ssh 
>  prod: ssh 
>  test: ssh 
INFO;
        return [
            [
                'productionmode:enable',
                ['uuid' => 'devcloud:devcloud2', 'environment' => 'dev'],
                ' [error]  Production mode may only be enabled/disabled on the prod environment. '
            ],
            [
                'productionmode:disable',
                ['uuid' => 'devcloud:devcloud2', 'environment' => 'dev'],
                ' [error]  Production mode may only be enabled/disabled on the prod environment. '
            ],
            [
                'productionmode:enable',
                ['uuid' => 'devcloud:devcloud2', 'environment' => 'prod'],
                '>  Enabling production mode for Production environment'
            ],
            [
                'productionmode:disable',
                ['uuid' => 'devcloud:devcloud2', 'environment' => 'prod'],
                '>  Disabling production mode for Production environment'
            ]
        ];
    }
}
