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

        return [
            [
                ['role:add', 'organisation', 'name', 'permissions'],
                '>  Creating new role (name) and adding it to organisation.' . PHP_EOL
            ],
            [
                ['role:delete', 'roleUuid'],
                '>  Deleting role' . PHP_EOL
            ],
            [
                ['role:update:permissions', 'roleUuid', 'permissions'],
                '>  Updating role permissions' . PHP_EOL
            ]
        ];
    }
}
