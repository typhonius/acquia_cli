<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;

class TeamsCommandTest extends AcquiaCliTestCase
{

    /**
     * @dataProvider teamsProvider
     */
    public function testTeamsCommands($command, $expected)
    {
        $actualResponse = $this->execute($command);
        $this->assertSame($expected, $actualResponse);
    }

    public function teamsProvider()
    {

        return [
            [
                ['team:addapplication', 'uuid', 'teamUuid'],
                '>  Adding application to team.' . PHP_EOL
            ],
            [
                ['team:create', 'organizationUuid', 'name'],
                '>  Creating new team.' . PHP_EOL
            ],
            [
                ['team:invite', 'teamUuid', 'email', 'roles'],
                '>  Inviting email to team.' . PHP_EOL
            ]
        ];
    }
}
