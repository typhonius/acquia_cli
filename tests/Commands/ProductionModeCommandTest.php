<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;

class ProductionModeCommandTest extends AcquiaCliTestCase
{

    /**
     * @dataProvider productionModeProvider
     */
    public function testProductionModeCommands($command, $expected)
    {
        $actualResponse = $this->execute($command);
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
                ['productionmode:enable', 'uuid', 'dev'],
                ' [error]  Production mode may only be enabled/disabled on the prod environment. ' . PHP_EOL
            ],
            [
                ['productionmode:disable', 'uuid', 'dev'],
                ' [error]  Production mode may only be enabled/disabled on the prod environment. ' . PHP_EOL
            ],
            [
                ['productionmode:enable', 'uuid', 'prod'],
                '>  Enabling production mode for Production environment' . PHP_EOL
            ],
            [
                ['productionmode:disable', 'uuid', 'prod'],
                '>  Disabling production mode for Production environment' . PHP_EOL
            ]
        ];
    }
}
