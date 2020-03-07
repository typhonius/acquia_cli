<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;

class CodeCommandTest extends AcquiaCliTestCase
{

    /**
     * @dataProvider codeProvider
     */
    public function testCronCommands($command, $expected)
    {
        $actualResponse = $this->execute($command);
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

        $codeDeploy = '>  Backing up DB (database1) on Mock Env
>  Backing up DB (database2) on Mock Env
>  Deploying code from the Mock Env environment to the Mock Env environment';

        $codeSwitch = '>  Backing up DB (database1) on Mock Env
>  Backing up DB (database2) on Mock Env
>  Switching Mock Env enviroment to branch branch';

        return [
            [
                ['code:deploy', 'uuid', 'environmentFrom', 'environmentTo'],
                $codeDeploy . PHP_EOL
            ],
            [
                ['code:list', 'uuid'],
                $codeList . PHP_EOL
            ],
            [
                ['code:switch', 'uuid', 'environment', 'branch'],
                $codeSwitch . PHP_EOL
            ]
        ];
    }
}
