<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;

class DbCommandTest extends AcquiaCliTestCase
{

    /**
     * @dataProvider dbProvider
     */
    public function testDbCommands($command, $fixture, $expected)
    {
        $actualResponse = $this->execute($command);
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
>  Backing up DB (database1) on Mock Env
>  Moving DB (database1) from Mock Env to Mock Env
>  Backing up DB (database2) on Mock Env
>  Moving DB (database2) from Mock Env to Mock Env
TEXT;

        return [
            [
                ['database:create', 'uuid', 'dbName'],
                'Databases/createDatabases.json',
                '>  Creating database (dbName)' . PHP_EOL
            ],
            [
                ['database:delete', 'uuid', 'dbName'],
                'Databases/deleteDatabases.json',
                '>  Deleting database (dbName)' . PHP_EOL
            ],
            [
                ['database:list', 'uuid'],
                'Databases/getAllDatabases.json',
                $dbTable . PHP_EOL
            ],
            [
                ['database:truncate', 'uuid', 'dbName'],
                'Databases/truncateDatabases.json',
                '>  Truncate database (dbName)' . PHP_EOL
            ],
            [
                ['database:copy', 'uuid', 'environmentFrom', 'environmentTo', 'dbName'],
                'Databases/copyDatabases.json',
                $dbCopy . PHP_EOL
            ],
            [
                ['database:copy:all', 'uuid', 'environmentFrom', 'environmentTo'],
                'Databases/copyDatabases.json',
                $dbCopy . PHP_EOL
            ]
        ];
    }
}
