<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;
use AcquiaCli\Tests\Traits\CommandTesterTrait;
use AcquiaCli\Commands\IdesCommand;

class IdesCommandTest extends AcquiaCliTestCase
{
    use CommandTesterTrait;

    public function setUp(): void
    {
        $this->setupCommandTester(IdesCommand::class);
    }

    /**
     * @dataProvider idesProvider
     */
    public function testIdesCommands($command, $arguments, $expected)
    {
        list($actualResponse, $statusCode) = $this->executeCommand($command, $arguments);
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
                'ide:create',
                ['uuid' => 'devcloud:devcloud2', 'label' => 'Example IDE'],
                '>  Creating IDE (Example IDE)'
            ],
            [
                'ide:delete',
                ['ideUuid' => '215824ff-272a-4a8c-9027-df32ed1d68a9'],
                '>  Deleting IDE (215824ff-272a-4a8c-9027-df32ed1d68a9)'
            ],
            [
                'ide:list',
                ['uuid' => 'devcloud:devcloud2'],
                $idesTable
            ]
        ];
    }
}
