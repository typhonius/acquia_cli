<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;
use AcquiaCli\Tests\Traits\CommandTesterTrait;
use AcquiaCli\Commands\DbBackupCommand;

class DbBackupCommandTest extends AcquiaCliTestCase
{
    use CommandTesterTrait;

    public function setUp(): void
    {
        $this->setupCommandTester(DbBackupCommand::class);
    }

    public function testDownloadDatabaseBackupsCommands()
    {
        $command = 'database:backup:download';
        $arguments = ['uuid' => 'devcloud:devcloud2', 'environment' => 'dev', 'dbName' => 'database2'];
        list($actualResponse, $statusCode) = $this->executeCommand($command, [], $arguments);

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
    }

    public function testDownloadDatabaseBackupsCommandsWithOptions()
    {
        $command = 'database:backup:download';
        $arguments = [
            'uuid' => 'devcloud:devcloud2',
            'environment' => 'dev',
            'dbName' => 'database2',
            '--backup' => '1',
            '--filename' => 'foo',
            '--path' => '/tmp'
        ];
        list($actualResponse, $statusCode) = $this->executeCommand($command, [], $arguments);

        $this->assertEquals(
            preg_match(
                '@>  Downloading database backup to ((/tmp/)foo.sql.gz)@',
                $actualResponse,
                $matches
            ),
            1
        );

        $this->assertStringStartsWith('>  Downloading database backup to ', $actualResponse);
        $this->assertStringContainsString('/tmp/', $matches[2]);
    }

    /**
     * @dataProvider dbBackupProvider
     */
    public function testDbBackupCommands($command, $arguments, $expected)
    {
        list($actualResponse, $statusCode) = $this->executeCommand($command, $arguments);
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

        $createBackupAllText = '>  Backing up DB (database1) on Dev
>  Backing up DB (database2) on Dev';

        $createBackupText = '>  Backing up DB (database1) on Dev';

        $dbLink = sprintf(
            '%s/environments/%s/databases/%s/backups/%s/actions/download',
            '>  https://cloud.acquia.com/api',
            '24-a47ac10b-58cc-4372-a567-0e02b2c3d470',
            'dbName',
            1234
        );

        return [
            [
                'database:backup:restore',
                ['uuid' => 'devcloud:devcloud2', 'environment' => 'dev', 'dbName' => 'dbName', 'backupId' => '1234'],
                '>  Restoring backup 1234 to dbName on Dev'
            ],
            [
                'database:backup:all',
                ['uuid' => 'devcloud:devcloud2', 'environment' => 'dev'],
                $createBackupAllText
            ],
            [
                'database:backup',
                ['uuid' => 'devcloud:devcloud2', 'environment' => 'dev', 'dbName' => 'database1'],
                $createBackupText
            ],
            [
                'database:backup:list',
                ['uuid' => 'devcloud:devcloud2', 'environment' => 'dev'],
                $dbBackupList
            ],
            [
                'database:backup:list',
                ['uuid' => 'devcloud:devcloud2', 'environment' => 'dev', 'dbName' => 'dbName'],
                $dbBackupList
            ],
            [
                'database:backup:link',
                ['uuid' => 'devcloud:devcloud2', 'environment' => 'dev', 'dbName' => 'dbName', 'backupId' => '1234'],
                $dbLink
            ],
            [
                'database:backup:delete',
                ['uuid' => 'devcloud:devcloud2', 'environment' => 'dev', 'dbName' => 'dbName', 'backupId' => '1234'],
                '>  Deleting backup 1234 to dbName on Dev'
            ],
        ];
    }
}
