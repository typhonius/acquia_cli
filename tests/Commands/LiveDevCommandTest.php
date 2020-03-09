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
                ['livedev:enable', 'uuid', 'dev'],
                '>  Enabling livedev for Dev environment' . PHP_EOL
            ],
            [
                ['livedev:disable', 'uuid', 'dev'],
                '>  Disabling livedev for Dev environment' . PHP_EOL
            ]
        ];
    }
}
