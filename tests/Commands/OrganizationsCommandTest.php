<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;
use AcquiaCli\Tests\Traits\CommandTesterTrait;
use AcquiaCli\Commands\OrganizationsCommand;

class OrganizationsCommandTest extends AcquiaCliTestCase
{
    use CommandTesterTrait;

    public function setUp(): void
    {
        $this->setupCommandTester(OrganizationsCommand::class);
    }

    /**
     * @dataProvider organizationsProvider
     */
    public function testOrganizationsCommands($command, $arguments, $expected)
    {
        list($actualResponse, $statusCode) = $this->executeCommand($command, $arguments);
        $this->assertSame($expected, $actualResponse);
    }

    public function organizationsProvider()
    {
        $getOrganizations = <<<TABLE
+--------------------------------------+-----------------------+-----------+------+--------+-------+-------+-------+
| UUID                                 | Organization          | Owner     | Subs | Admins | Users | Teams | Roles |
+--------------------------------------+-----------------------+-----------+------+--------+-------+-------+-------+
| g47ac10b-58cc-4372-a567-0e02b2c3d470 | Sample organization   | user.name | 115  | 2      | 82    | 13    | 4     |
| g47ac10b-58cc-4372-a567-0e02b2c3d471 | Sample organization 2 | user.name | 4    | 0      | 0     | 0     | 0     |
| g47ac10b-58cc-4372-a567-0e02b2c3d472 | Sample organization 3 | user.name | 4    | 0      | 0     | 0     | 0     |
+--------------------------------------+-----------------------+-----------+------+--------+-------+-------+-------+
TABLE;

        $organizationTeams = <<<TABLE
>  Teams in organisation: g47ac10b-58cc-4372-a567-0e02b2c3d470
+--------------------------------------+-------------+
| UUID                                 | Name        |
+--------------------------------------+-------------+
| abcd1234-82b5-11e3-9170-12313920a02c | Team Name 1 |
| 1234abcd-82b5-11e3-9170-12313920a02c | Team Name 2 |
+--------------------------------------+-------------+
TABLE;

        $organizationMembers = <<<TABLE
>  Members in organisation: g47ac10b-58cc-4372-a567-0e02b2c3d470
+--------------------------------------+-----------------+-----------------------------+-------------------------+
|                 UUID                 | Username        | Mail                        | Teams(s)                |
+--------------------------------------+-----------------+-----------------------------+-------------------------+
|                                          Organisation Administrators                                           |
+--------------------------------------+-----------------+-----------------------------+-------------------------+
| 5aa902c5-f1c1-6c94-edfa-86bc58d0dce3 | james.kirk      | james.kirk@example.com      | admin                   |
| 30dacb5e-4122-11e1-9eb5-12313928d3c2 | chris.pike      | chris.pike@example.com      | admin                   |
| 3bcddc3a-52ba-4cce-aaa3-9adf721c1b52 | jonathan.archer | jonathan.archer@example.com | admin                   |
+--------------------------------------+-----------------+-----------------------------+-------------------------+
|                                              Organisation Members                                              |
+--------------------------------------+-----------------+-----------------------------+-------------------------+
| 5aa902c5-f1c1-6c94-edfa-86bc58d0dce3 | james.kirk      | james.kirk@example.com      | Team Name 1             |
| 30dacb5e-4122-11e1-9eb5-12313928d3c2 | chris.pike      | chris.pike@example.com      | Team Name 2             |
| 3bcddc3a-52ba-4cce-aaa3-9adf721c1b52 | jonathan.archer | jonathan.archer@example.com | Team Name 1,Team Name 2 |
+--------------------------------------+-----------------+-----------------------------+-------------------------+
TABLE;

        $organizationApplications = <<<TABLE
>  Applications in organisation: g47ac10b-58cc-4372-a567-0e02b2c3d470
+--------------------------------------+----------------------+------+--------------------+
| UUID                                 | Name                 | Type | Hosting ID         |
+--------------------------------------+----------------------+------+--------------------+
| a47ac10b-58cc-4372-a567-0e02b2c3d470 | Sample application 1 | acp  | devcloud:devcloud2 |
| a47ac10b-58cc-4372-a567-0e02b2c3d471 | Sample application 2 | free | devcloud:devcloud2 |
+--------------------------------------+----------------------+------+--------------------+
TABLE;

        return [
            [
                'organization:applications',
                ['organization' => 'Sample organization'],
                $organizationApplications
            ],
            [
                'organization:list',
                [],
                $getOrganizations
            ],
            [
                'organization:members',
                ['organization' => 'Sample organization'],
                $organizationMembers
            ],
            [
                'organization:teams',
                ['organization' => 'Sample organization'],
                $organizationTeams
            ]
        ];
    }
}
