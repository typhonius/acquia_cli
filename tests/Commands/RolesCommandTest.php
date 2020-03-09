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
+--------------------+-----------+------------------+-----------+
| Permission         | Team Lead | Senior Developer | Developer |
+--------------------+-----------+------------------+-----------+
| administer alerts  |           |                  |           |
| deploy to non-prod |           |                  |           |
| deploy to prod     |           |                  |           |
| pull from prod     |           |                  |           |
+--------------------+-----------+------------------+-----------+
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
