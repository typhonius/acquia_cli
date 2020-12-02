<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;
use AcquiaCli\Tests\Traits\CommandTesterTrait;
use AcquiaCli\Commands\NotificationsCommand;

class NotificationCommandTest extends AcquiaCliTestCase
{
    use CommandTesterTrait;

    public function setUp(): void
    {
        $this->setupCommandTester(NotificationsCommand::class);
    }

    /**
     * @dataProvider notificationProvider
     */
    public function testNotificationCommands($command, $arguments, $expected)
    {
        list($actualResponse, $statusCode) = $this->executeCommand($command, $arguments);
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

        $getAllNotifictionsDetails = <<<TABLE
+--------------------------------------+------+---------------------+-----------------------------------+-----------+
| UUID                                 | User | Created             | Name                              | Status    |
+--------------------------------------+------+---------------------+-----------------------------------+-----------+
| 1bd3487e-71d1-4fca-a2d9-5f969b3d35c1 | N/A  | 2019-07-30 06:47:13 | Application added to recents list | completed |
+--------------------------------------+------+---------------------+-----------------------------------+-----------+
TABLE;

        $getNotification = <<<INFO
>  ID: f4b37e3c-1g96-4ed4-ad20-3081fe0f9545
>  User: N/A
>  Event: DatabaseBackupCreated
>  Description: A "sample_db" database backup has been created for "Dev".
>  Status: completed
>  Created: 2019-09-24 01:59:39
>  Completed: 2019-09-24 02:01:16
INFO;

        return [
            [
                'notification:list',
                ['uuid' => 'devcloud:devcloud2'],
                $getAllNotifictions
            ],
            [
                'notification:list',
                ['uuid' => 'devcloud:devcloud2', '--details' => true],
                $getAllNotifictionsDetails
            ],
            [
                'notification:info',
                ['uuid' => 'devcloud:devcloud2', 'notificationUuid' => 'f4b37e3c-1g96-4ed4-ad20-3081fe0f9545'],
                $getNotification
            ]
        ];
    }
}
