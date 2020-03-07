<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;

class CronCommandTest extends AcquiaCliTestCase
{

    /**
     * @dataProvider cronProvider
     */
    public function testCronCommands($command, $expected)
    {
        $actualResponse = $this->execute($command);
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
                ['cron:create', 'uuid', 'environment', 'commandString', 'frequency', 'label'],
                '>  Adding new cron task on mock environment' . PHP_EOL
            ],
            [
                ['cron:delete', 'uuid', 'environment', 'cronId'],
                '>  Deleting cron task cronId from Mock Env' . PHP_EOL
            ],
            [
                ['cron:disable', 'uuid', 'environment', 'cronId'],
                '>  Disabling cron task cronId on mock environment' . PHP_EOL
            ],
            [
                ['cron:enable', 'uuid', 'environment', 'cronId'],
                '>  Enabling cron task cronId on mock environment' . PHP_EOL
            ],
            [
                ['cron:info', 'uuid', 'environment', 'cronId'],
                $cronInfo . PHP_EOL
            ],
            [
                ['cron:list', 'uuid', 'environment'],
                $cronList . PHP_EOL
            ]
        ];
    }
}
