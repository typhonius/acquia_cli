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
                ['cron:create', 'devcloud:devcloud2', 'dev', 'commandString', 'frequency', 'label'],
                '>  Adding new cron task on dev environment' . PHP_EOL
            ],
            [
                ['cron:delete', 'devcloud:devcloud2', 'dev', 'cronId'],
                '>  Deleting cron task cronId from Dev' . PHP_EOL
            ],
            [
                ['cron:disable', 'devcloud:devcloud2', 'dev', 'cronId'],
                '>  Disabling cron task cronId on dev environment' . PHP_EOL
            ],
            [
                ['cron:enable', 'devcloud:devcloud2', 'dev', 'cronId'],
                '>  Enabling cron task cronId on dev environment' . PHP_EOL
            ],
            [
                ['cron:info', 'devcloud:devcloud2', 'dev', 'cronId'],
                $cronInfo . PHP_EOL
            ],
            [
                ['cron:list', 'devcloud:devcloud2', 'dev'],
                $cronList . PHP_EOL
            ]
        ];
    }
}
