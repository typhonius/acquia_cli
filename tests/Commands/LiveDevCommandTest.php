<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;

class LiveDevCommandTest extends AcquiaCliTestCase
{

    /**
     * @dataProvider liveDevProvider
     */
    public function testLiveDevInfo($command, $expected)
    {
        $actualResponse = $this->execute($command);
        $this->assertSame($expected, $actualResponse);
    }

    public function liveDevProvider()
    {

        return [
            [
                ['livedev:enable', 'uuid', 'environment'],
                '>  Enabling livedev for Mock Env environment' . PHP_EOL
            ],
            [
                ['livedev:disable', 'uuid', 'environment'],
                '>  Disabling livedev for Mock Env environment' . PHP_EOL
            ]
        ];
    }
}
