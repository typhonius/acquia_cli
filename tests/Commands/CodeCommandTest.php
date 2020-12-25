<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;
use AcquiaCli\Tests\Traits\CommandTesterTrait;
use AcquiaCli\Commands\CodeCommand;

class CodeCommandTest extends AcquiaCliTestCase
{
    use CommandTesterTrait;

    public function setUp(): void
    {
        $this->setupCommandTester(CodeCommand::class);
    }

    /**
     * @dataProvider codeProvider
     */
    public function testCodeCommands($command, $arguments, $expected)
    {
        list($actualResponse, $statusCode) = $this->executeCommand($command, $arguments);
        $this->assertSame($expected, $actualResponse);
    }

    public function codeProvider()
    {

        $codeList = <<<LIST
+-------------------+-----+
| Name              | Tag |
+-------------------+-----+
| master            |     |
| feature-branch    |     |
| tags/2014-09-03   | ✓   |
| tags/2014-09-03.0 | ✓   |
+-------------------+-----+
LIST;

        $codeDeploy = '>  Backing up DB (database1) on Stage
>  Backing up DB (database2) on Stage
>  Deploying code from the Dev environment to the Stage environment';

        $codeDeployNoBackup = '>  Deploying code from the Dev environment to the Stage environment';

        $codeSwitch = '>  Backing up DB (database1) on Production
>  Backing up DB (database2) on Production
>  Switching Production enviroment to master branch';

        $codeSwitchNoBackup = '>  Switching Production enviroment to master branch';

        return [
            [
                'code:deploy',
                ['uuid' => 'devcloud:devcloud2', 'environmentFrom' => 'dev', 'environmentTo' => 'test'],
                $codeDeploy
            ],
            [
                'code:deploy',
                ['uuid' => 'devcloud:devcloud2', 'environmentFrom' => 'dev', 'environmentTo' => 'test', '--no-backup' => true],
                $codeDeployNoBackup
            ],
            [
                'code:list',
                ['uuid' => 'devcloud:devcloud2'],
                $codeList
            ],
            [
                'code:switch',
                ['uuid' => 'devcloud:devcloud2', 'environment' => 'prod', 'branch' => 'master'],
                $codeSwitch
            ],
            [
                'code:switch',
                ['uuid' => 'devcloud:devcloud2', 'environment' => 'prod', 'branch' => 'master', '--no-backup' => true],
                $codeSwitchNoBackup
            ]
        ];
    }
}
