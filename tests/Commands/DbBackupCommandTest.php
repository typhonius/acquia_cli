<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;

class DbBackupCommandTest extends AcquiaCliTestCase
{

    public function testDownloadDatabaseBackupsCommands()
    {
        $command = ['database:backup:download', 'uuid', 'dev', 'database2'];
        $actualResponse = $this->execute($command);

        $this->assertEquals(
            preg_match(
                '@>  Downloading database backup to ((\S+)dev-database2-(\w+).sql.gz)@',
                $actualResponse,
                $matches
            ),
            1
        );

        $this->assertStringStartsWith('>  Downloading database backup to ', $actualResponse);
        $this->assertStringContainsString(sys_get_temp_dir(), $matches[2]);

        $path = sprintf(
            '%s/vendor/typhonius/acquia-php-sdk-v2/tests/Fixtures/Endpoints/%s',
            dirname(dirname(__DIR__)),
            'DatabaseBackups/downloadDatabaseBackup.dat'
        );
        $this->assertFileExists($path);
        $contents = file_get_contents($path);
        // $this->assertStringEqualsFile($matches[1], $contents);
    }

    /**
     * @dataProvider dbBackupProvider
     */
    public function testDbBackupCommands($command, $expected)
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

        $createBackupText = '>  Backing up DB (database1) on Dev
>  Backing up DB (database2) on Dev';

        $dbLink = sprintf(
            '%s/environments/%s/databases/%s/backups/%s/actions/download',
            '>  https://cloud.acquia.com/api',
            '24-a47ac10b-58cc-4372-a567-0e02b2c3d470',
            'dbName',
            1234
        );

        return [
            [
                ['database:backup:restore', 'uuid', 'dev', 'dbName', 1234],
                '>  Restoring backup 1234 to dbName on Dev' . PHP_EOL
            ],
            [
                ['database:backup', 'uuid', 'dev'],
                $createBackupText . PHP_EOL
            ],            [
                ['database:backup:list', 'uuid', 'dev'],
                $dbBackupList . PHP_EOL
            ],
            [
                ['database:backup:link', 'uuid', 'dev', 'dbName', 1234],
                $dbLink . PHP_EOL
            ],
        ];
    }
}
