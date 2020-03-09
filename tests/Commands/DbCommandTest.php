<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;

class DbCommandTest extends AcquiaCliTestCase
{

    /**
     * @dataProvider dbProvider
     */
    public function testDbCommands($command, $expected)
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
>  Backing up DB (database1) on Dev
>  Moving DB (database1) from Stage to Dev
>  Backing up DB (database2) on Dev
>  Moving DB (database2) from Stage to Dev
TEXT;

        return [
            [
                ['database:create', 'uuid', 'dbName'],
                '>  Creating database (dbName)' . PHP_EOL
            ],
            [
                ['database:delete', 'uuid', 'dbName'],
                '>  Deleting database (dbName)' . PHP_EOL
            ],
            [
                ['database:list', 'uuid'],
                $dbTable . PHP_EOL
            ],
            [
                ['database:truncate', 'uuid', 'dbName'],
                '>  Truncate database (dbName)' . PHP_EOL
            ],
            [
                ['database:copy', 'uuid', 'test', 'dev', 'dbName'],
                $dbCopy . PHP_EOL
            ],
            [
                ['database:copy:all', 'uuid', 'test', 'dev'],
                $dbCopy . PHP_EOL
            ]
        ];
    }
}
