<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;
use AcquiaCli\Tests\Traits\CommandTesterTrait;
use AcquiaCli\Commands\TeamsCommand;

class TeamsCommandTest extends AcquiaCliTestCase
{
    use CommandTesterTrait;

    public function setUp(): void
    {
        $this->setupCommandTester(TeamsCommand::class);
    }

    /**
     * @dataProvider teamsProvider
     */
    public function testTeamsCommands($command, $arguments, $expected)
    {
        list($actualResponse, $statusCode) = $this->executeCommand($command, $arguments);
        $this->assertSame($expected, $actualResponse);
    }

    public function teamsProvider()
    {

        return [
            [
                'team:addapplication',
                ['uuid' => 'devcloud:devcloud2', 'teamUuid' => 'teamUuid'],
                '>  Adding application to team.'
            ],
            [
                'team:create',
                ['organization' => 'Sample organization', 'name' => 'name'],
                '>  Creating new team.'
            ],
            [
                'team:invite',
                ['teamUuid' => 'teamUuid', 'email' => 'email', 'roleUuids' => 'roles'],
                '>  Inviting email to team.'
            ]
        ];
    }
}
