<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;

class RoleCommandTest extends AcquiaCliTestCase
{

    /**
     * @dataProvider roleProvider
     */
    public function testRoleCommands($command, $fixture, $expected)
    {
        $actualResponse = $this->execute($command);
        $this->assertSame($expected, $actualResponse);
    }

    public function roleProvider()
    {

        return [
            [
                ['role:add', 'organisation', 'name', 'permissions'],
                'Roles/createRole.json',
                '>  Creating new role (name) and adding it to organisation.' . PHP_EOL
            ],
            [
                ['role:delete', 'roleUuid'],
                'Roles/deleteRole.json',
                '>  Deleting role' . PHP_EOL
            ],
            [
                ['role:update:permissions', 'roleUuid', 'permissions'],
                'Roles/updateRole.json',
                '>  Updating role permissions' . PHP_EOL
            ]
        ];
    }
}
