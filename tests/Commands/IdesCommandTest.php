<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;

class IdesCommandTest extends AcquiaCliTestCase
{

    /**
     * @dataProvider idesProvider
     */
    public function testIdesCommands($command, $expected)
    {
        $actualResponse = $this->execute($command);
        $this->assertSame($expected, $actualResponse);
    }

    public function idesProvider()
    {

        $idesTable = <<<TABLE
+--------------------------------------+-------------+
| UUID                                 | Label       |
+--------------------------------------+-------------+
| 215824ff-272a-4a8c-9027-df32ed1d68a9 | Example IDE |
+--------------------------------------+-------------+
TABLE;

        return [
            [
                ['ide:create', 'devcloud:devcloud2', 'Example IDE'],
                '>  Creating IDE (Example IDE)' . PHP_EOL
            ],
            [
                ['ide:delete', '215824ff-272a-4a8c-9027-df32ed1d68a9'],
                '>  Deleting IDE (215824ff-272a-4a8c-9027-df32ed1d68a9)' . PHP_EOL
            ],
            [
                ['ide:list', 'devcloud:devcloud2'],
                $idesTable . PHP_EOL
            ]
        ];
    }
}
