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
        $psr7Response = $this->getPsr7JsonResponseForFixture($fixture);
        $client = $this->getMockClient($psr7Response);

        $actualResponse = $this->execute($client, $command);

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
            ]
        ];
    }
}
