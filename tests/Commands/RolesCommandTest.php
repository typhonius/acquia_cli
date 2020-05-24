<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;

class RolesCommandTest extends AcquiaCliTestCase
{

    /**
     * @dataProvider roleProvider
     */
    public function testRoleCommands($command, $expected)
    {
        $actualResponse = $this->execute($command);
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
                ['role:add', 'Sample organization', 'name', 'permissions'],
                '>  Creating new role (name) and adding it to organisation.' . PHP_EOL
            ],
            [
                ['role:delete', 'roleUuid'],
                '>  Deleting role' . PHP_EOL
            ],
            [
                ['role:list', 'Sample organization'],
                $roleList . PHP_EOL
            ],
            [
                ['role:update:permissions', 'roleUuid', 'permissions'],
                '>  Updating role permissions' . PHP_EOL
            ]
        ];
    }
}
