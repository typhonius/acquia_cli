<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;

class AccountCommandTest extends AcquiaCliTestCase
{

    /**
     * @dataProvider accountProvider
     */
    public function testAccountInfo($command, $expected)
    {
        $actualResponse = $this->execute($command);
        $this->assertSame($expected, $actualResponse);
    }

    public function accountProvider()
    {

        $infoResponse = <<<INFO
>  Name: jane.doe
>  Last login: 2017-03-29 05:07:54
>  Created at: 2017-03-29 05:07:54
>  Status: ✓
>  TFA: ✓
INFO;

        return [
            [
                ['account'],
                $infoResponse . PHP_EOL
            ]
        ];
    }
}
