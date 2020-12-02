<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;
use AcquiaCli\Tests\Traits\CommandTesterTrait;
use AcquiaCli\Commands\VariablesCommand;

class VariablesCommandTest extends AcquiaCliTestCase
{
    use CommandTesterTrait;

    public function setUp(): void
    {
        $this->setupCommandTester(VariablesCommand::class);
    }

    /**
     * @dataProvider variablesProvider
     */
    public function testVariablesCommands($command, $arguments, $expected)
    {
        list($actualResponse, $statusCode) = $this->executeCommand($command, $arguments);
        $this->assertSame($expected, $actualResponse);
    }

    public function variablesProvider()
    {

        $variablesList = <<<TABLE
+----------------+--------------------+
| Name           | Value              |
+----------------+--------------------+
| variable_one   | Sample Value One   |
| variable_two   | Sample Value Two   |
| variable_three | Sample Value Three |
+----------------+--------------------+
TABLE;

        return [
            [
                'variable:create',
                ['uuid' => 'devcloud:devcloud2', 'environment' => 'dev', 'name' => 'variable_one', 'value' => 'Sample Value One'],
                '>  Adding variable variable_one:Sample Value One to Dev environment'
            ],
            [
                'variable:delete',
                ['uuid' => 'devcloud:devcloud2', 'environment' => 'dev', 'name' => 'variable_one'],
                '>  Removing variable variable_one from Dev environment'
            ],
            [
                'variable:info',
                ['uuid' => 'devcloud:devcloud2', 'environment' => 'dev', 'name' => 'variable_one'],
                '>  Sample Value One'
            ],
            [
                'variable:list',
                ['uuid' => 'devcloud:devcloud2', 'environment' => 'dev'],
                $variablesList
            ],
            [
                'variable:update',
                ['uuid' => 'devcloud:devcloud2', 'environment' => 'dev', 'name' => 'variable_one', 'value' => 'Sample Value One'],
                '>  Updating variable variable_one:Sample Value One on Dev environment'
            ]
        ];
    }
}
