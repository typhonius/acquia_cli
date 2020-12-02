<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;
use AcquiaCli\Tests\Traits\CommandTesterTrait;
use AcquiaCli\Commands\DbCommand;

class DbCommandTest extends AcquiaCliTestCase
{
    use CommandTesterTrait;

    public function setUp(): void
    {
        $this->setupCommandTester(DbCommand::class);
    }

    /**
     * @dataProvider dbProvider
     */
    public function testDbCommands($command, $arguments, $expected)
    {
        list($actualResponse, $statusCode) = $this->executeCommand($command, $arguments);
        $this->assertSame($expected, $actualResponse);
    }

    public function dbProvider()
    {

        $dbTable = <<<TABLE
+-----------+
| Databases |
+-----------+
| database1 |
| database2 |
+-----------+
TABLE;

        $dbCopy = <<<TEXT
>  Backing up DB (database1) on Dev
>  Moving DB (database1) from Stage to Dev
>  Backing up DB (database2) on Dev
>  Moving DB (database2) from Stage to Dev
TEXT;

        $dbCopyNoBackup = <<<TEXT
>  Moving DB (database1) from Stage to Dev
>  Moving DB (database2) from Stage to Dev
TEXT;

        return [
            [
                'database:create',
                ['uuid' => 'devcloud:devcloud2', 'dbName' => 'dbName'],
                '>  Creating database (dbName)'
            ],
            [
                'database:delete',
                ['uuid' => 'devcloud:devcloud2', 'dbName' => 'dbName'],
                '>  Deleting database (dbName)'
            ],
            [
                'database:list',
                ['uuid' => 'devcloud:devcloud2'],
                $dbTable
            ],
            [
                'database:truncate',
                ['uuid' => 'devcloud:devcloud2', 'dbName' => 'dbName'],
                '>  Truncate database (dbName)'
            ],
            [
                'database:copy',
                ['uuid' => 'devcloud:devcloud2', 'environmentFrom' => 'test', 'environmentTo' => 'dev', 'dbName' => 'dbName'],
                $dbCopy
            ],
            [
                'database:copy:all',
                ['uuid' => 'devcloud:devcloud2', 'environmentFrom' => 'test', 'environmentTo' => 'dev'],
                $dbCopy
            ],
            [
                'database:copy',
                ['uuid' => 'devcloud:devcloud2', 'environmentFrom' => 'test', 'environmentTo' => 'dev', 'dbName' => 'dbName', '--no-backup' => true],
                $dbCopyNoBackup
            ],
            [
                'database:copy:all',
                ['uuid' => 'devcloud:devcloud2', 'environmentFrom' => 'test', 'environmentTo' => 'dev', '--no-backup' => true],
                $dbCopyNoBackup
            ]
        ];
    }
}
