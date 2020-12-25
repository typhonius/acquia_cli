<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;
use AcquiaCli\Tests\Traits\CommandTesterTrait;
use AcquiaCli\Commands\CronCommand;

class CronCommandTest extends AcquiaCliTestCase
{
    use CommandTesterTrait;

    public function setUp(): void
    {
        $this->setupCommandTester(CronCommand::class);
    }

    /**
     * @dataProvider cronProvider
     */
    public function testCronCommands($command, $arguments, $expected)
    {
        list($actualResponse, $statusCode) = $this->executeCommand($command, $arguments);
        $this->assertSame($expected, $actualResponse);
    }

    public function cronProvider()
    {

        $cronList = <<<LIST
+----+-----------------------------------------------------------------------+------------+
| ID | Command                                                               | Frequency  |
+----+-----------------------------------------------------------------------+------------+
| 24 | /usr/local/bin/drush cc all                                           | 25 7 * * * |
| 25 | /usr/local/bin/drush -r /var/www/html/qa3/docroot ah-db-backup dbname | 12 9 * * * |
+----+-----------------------------------------------------------------------+------------+
>  Cron commands starting with "#" are disabled.
LIST;

        $cronInfo = <<<INFO
>  ID: 24
>  Label: 
>  Environment: dev
>  Command: /usr/local/bin/drush cc all
>  Frequency: 25 7 * * *
>  Enabled: ✓
>  System:  
>  On any web: ✓
INFO;

        return [
            [
                'cron:create',
                ['uuid' => 'devcloud:devcloud2', 'environment' => 'dev', 'commandString' => 'commandString', 'frequency' => 'frequency', 'label' => 'label'],
                '>  Adding new cron task on dev environment'
            ],
            [
                'cron:delete',
                ['uuid' => 'devcloud:devcloud2', 'environment' => 'dev', 'cronId' => 'cronId'],
                '>  Deleting cron task cronId from Dev'
            ],
            [
                'cron:disable',
                ['uuid' => 'devcloud:devcloud2', 'environment' => 'dev', 'cronId' => 'cronId'],
                '>  Disabling cron task cronId on dev environment'
            ],
            [
                'cron:enable',
                ['uuid' => 'devcloud:devcloud2', 'environment' => 'dev', 'cronId' => 'cronId'],
                '>  Enabling cron task cronId on dev environment'
            ],
            [
                'cron:info',
                ['uuid' => 'devcloud:devcloud2', 'environment' => 'dev', 'cronId' => 'cronId'],
                $cronInfo
            ],
            [
                'cron:list',
                ['uuid' => 'devcloud:devcloud2', 'environment' => 'dev'],
                $cronList
            ]
        ];
    }
}
