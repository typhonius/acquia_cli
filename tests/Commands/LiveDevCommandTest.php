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
                ['livedev:enable', 'devcloud:devcloud2', 'dev'],
                '>  Enabling livedev for Dev environment' . PHP_EOL
            ],
            [
                ['livedev:disable', 'devcloud:devcloud2', 'dev'],
                '>  Disabling livedev for Dev environment' . PHP_EOL
            ]
        ];
    }
}
