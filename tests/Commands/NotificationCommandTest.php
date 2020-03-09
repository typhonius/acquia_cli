<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;

class NotificationCommandTest extends AcquiaCliTestCase
{

    /**
     * @dataProvider notificationProvider
     */
    public function testNotificationCommands($command, $expected)
    {
        $actualResponse = $this->execute($command);
        $this->assertSame($expected, $actualResponse);
    }

    public function notificationProvider()
    {
        $getAllNotifictions = <<<TABLE
+--------------------------------------+---------------------+-----------------------------------+-----------+
| UUID                                 | Created             | Name                              | Status    |
+--------------------------------------+---------------------+-----------------------------------+-----------+
| 1bd3487e-71d1-4fca-a2d9-5f969b3d35c1 | 2019-07-30 06:47:13 | Application added to recents list | completed |
+--------------------------------------+---------------------+-----------------------------------+-----------+
TABLE;

        $getNotification = <<<INFO
>  ID: f4b37e3c-1g96-4ed4-ad20-3081fe0f9545
>  Event: DatabaseBackupCreated
>  Description: A "sample_db" database backup has been created for "Dev".
>  Status: completed
>  Created: 2019-09-24 01:59:39
>  Completed: 2019-09-24 02:01:16
INFO;

        return [
            [
                ['notification:list', 'devcloud:devcloud2'],
                $getAllNotifictions . PHP_EOL
            ],
            [
                ['notification:info', 'devcloud:devcloud2', 'f4b37e3c-1g96-4ed4-ad20-3081fe0f9545'],
                $getNotification . PHP_EOL
            ]
        ];
    }
}
