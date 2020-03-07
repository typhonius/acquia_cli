<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;

class DbBackupCommandTest extends AcquiaCliTestCase
{

    /**
     * @dataProvider dbBackupProvider
     */
    public function testDbBackupCommands($command, $fixture, $expected)
    {
        $actualResponse = $this->execute($command);
        $this->assertSame($expected, $actualResponse);
    }

    public function dbBackupProvider()
    {

        $dbBackupList = <<<TABLE
+-----+-------+----------------------+
| ID  | Type  | Timestamp            |
+-----+-------+----------------------+
| database1                          |
+-----+-------+----------------------+
| 1   | Daily | 2012-05-15T12:00:00Z |
| 2   | Daily | 2012-03-28T12:00:01Z |
| 3   | Daily | 2017-01-08T04:00:01Z |
| 4   | Daily | 2017-01-08T05:00:03Z |
| database2                          |
+-----+-------+----------------------+
| 1   | Daily | 2012-05-15T12:00:00Z |
| 2   | Daily | 2012-03-28T12:00:01Z |
| 3   | Daily | 2017-01-08T04:00:01Z |
| 4   | Daily | 2017-01-08T05:00:03Z |
+-----+-------+----------------------+
TABLE;

        $createBackupText = '>  Backing up DB (database1) on Mock Env
>  Backing up DB (database2) on Mock Env';

        $dbLink = sprintf(
            '%s/environments/%s/databases/%s/backups/%s/actions/download',
            '>  https://cloud.acquia.com/api',
            'bfcc7ad1-f987-41b8-9ea5-f26f0ef3838a',
            'dbName',
            1234
        );

        return [
            [
                ['database:backup:restore', 'uuid', 'environment', 'dbName', 1234],
                'DatabaseBackups/restoreDatabaseBackup.json',
                '>  Restoring backup 1234 to dbName on Mock Env' . PHP_EOL
            ],
            [
                ['database:backup', 'uuid', 'environment'],
                'DatabaseBackups/createDatabaseBackup.json',
                $createBackupText . PHP_EOL
            ],            [
                ['database:backup:list', 'uuid', 'environment'],
                'DatabaseBackups/getAllDatabaseBackups.json',
                $dbBackupList . PHP_EOL
            ],
            [
                ['database:backup:link', 'uuid', 'environment', 'dbName', 1234],
                'DatabaseBackups/restoreDatabaseBackup.json',
                $dbLink . PHP_EOL
            ],
        ];
    }
}
