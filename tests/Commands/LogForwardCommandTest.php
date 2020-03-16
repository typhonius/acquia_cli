<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;

class LogForwardCommandTest extends AcquiaCliTestCase
{

    /**
     * @dataProvider logForwardProvider
     */
    public function testLogForwardInfo($command, $expected)
    {
        $actualResponse = $this->execute($command);
        $this->assertSame($expected, $actualResponse);
    }

    public function logForwardProvider()
    {

        $listResponse = <<<LIST
+--------------------------------------+--------------------------+-------------------+-----------+--------+
| UUID                                 |          Label           |      Address      | Consumer  | Active |
+--------------------------------------+--------------------------+-------------------+-----------+--------+
| df4c5428-8d2e-453d-9edf-e412647449b1 |     Test destination     | example.com:1234  | sumologic |   ✓    |
| df4c5428-8d2e-453d-9edf-e412647449b5 | Another test destination | 193.169.2.19:5678 |  syslog   |   ✓    |
+--------------------------------------+--------------------------+-------------------+-----------+--------+
LIST;

        $infoResponse = <<<INFO
                                             
      Log server address: example.com:1234   
                                             
>  Certificate: -----BEGIN CERTIFICATE-----...-----END CERTIFICATE-----
>  Expires at: 2018-07-16T16:15:33+00:00
>  Token: 204d892b449026f6e4ded264c8891c400df8fc8905f07beb5f70d706f6d4d5e5
>  Key: 1d0789d519c0b943cf38f401d30ffbdcd2e0c4cfb7c32ebc0c872bce62aadd4d
>  Sources: 
apache-access
apache-error
>  Health: OK
INFO;

        return [
            [
                ['lf:list', 'devcloud:devcloud2', 'dev'],
                $listResponse . PHP_EOL
            ],
            [
                ['lf:info', 'devcloud:devcloud2', 'dev', 1234],
                $infoResponse . PHP_EOL,
            ]
        ];
    }
}
