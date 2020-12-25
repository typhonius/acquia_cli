<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;
use AcquiaCli\Tests\Traits\CommandTesterTrait;
use AcquiaCli\Commands\TeamsCommand;

class RolesCommandTest extends AcquiaCliTestCase
{
    use CommandTesterTrait;

    public function setUp(): void
    {
        $this->setupCommandTester(TeamsCommand::class);
    }

    /**
     * @dataProvider roleProvider
     */
    public function testRoleCommands($command, $arguments, $expected)
    {
        list($actualResponse, $statusCode) = $this->executeCommand($command, $arguments);
        $this->assertSame($expected, $actualResponse);
    }

    public function roleProvider()
    {

        $roleList = <<<TABLE
>  Team Lead: 312c0121-906b-4498-8402-7b479172768c
>  Senior Developer: 5f7da0a9-9ff0-4db8-802e-9d2b9969efc2
>  Developer: d33cd9ff-281d-4bcf-9f89-b10b249caa35
+-----------------------------+-----------+------------------+-----------+
| Permission                  | Team Lead | Senior Developer | Developer |
+-----------------------------+-----------+------------------+-----------+
| administer alerts           |           |                  |           |
| revoke insight installs     |           |                  |           |
| deploy to non-prod          |           |                  |           |
| deploy to prod              |           |                  |           |
| pull from prod              |           |                  |           |
| move file to non-prod       |           |                  |           |
| move file to prod           |           |                  |           |
| move file from prod         |           |                  |           |
| move file from non-prod     |           |                  |           |
| clear varnish on non-prod   |           |                  |           |
| clear varnish on prod       |           |                  |           |
| configure prod env          |           |                  |           |
| configure non-prod env      |           |                  |           |
| add an environment          |           |                  |           |
| delete an environment       |           |                  |           |
| administer domain non-prod  |           |                  |           |
| administer domain prod      |           |                  |           |
| administer ssl prod         |           |                  |           |
| administer ssl non-prod     |           |                  |           |
| reboot server               |           |                  |           |
| resize server               |           |                  |           |
| suspend server              |           |                  |           |
| configure server            |           |                  |           |
| download logs non-prod      |           |                  |           |
| download logs prod          |           |                  |           |
| add database                |           |                  |           |
| remove database             |           |                  |           |
| view database connection    |           |                  |           |
| download db backup non-prod |           |                  |           |
| download db backup prod     |           |                  |           |
| create db backup non-prod   |           |                  |           |
| create db backup prod       |           |                  |           |
| restore db backup non-prod  |           |                  |           |
| restore db backup prod      |           |                  |           |
| administer team             | ✓         | ✓                |           |
| access cloud api            |           | ✓                | ✓         |
| administer cron non-prod    |           |                  |           |
| administer cron prod        |           |                  |           |
| search limit increase       |           |                  |           |
| search schema edit          |           |                  |           |
| create support ticket       |           |                  |           |
| edit any support ticket     |           |                  |           |
| administer ssh keys         |           |                  |           |
| view build plans            |           |                  |           |
| edit build plans            |           |                  |           |
| run build plans             |           |                  |           |
| add ssh key to git          |           |                  |           |
| add ssh key to non-prod     |           |                  |           |
| add ssh key to prod         |           |                  |           |
| view remote administration  |           |                  |           |
| edit remote administration  |           |                  |           |
+-----------------------------+-----------+------------------+-----------+
TABLE;

        return [
            [
                'role:add',
                ['organization' => 'Sample organization', 'name' => 'name', 'permissions' => 'permissions'],
                '>  Creating new role (name) and adding it to organisation.'
            ],
            [
                'role:delete',
                ['roleUuid' => 'roleUuid'],
                '>  Deleting role'
            ],
            [
                'role:list',
                ['organization' => 'Sample organization'],
                $roleList
            ],
            [
                'role:update:permissions',
                ['roleUuid' => 'roleUuid', 'permissions' => 'permissions'],
                '>  Updating role permissions'
            ]
        ];
    }
}
