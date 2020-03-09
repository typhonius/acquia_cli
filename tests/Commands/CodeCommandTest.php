<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;

class CodeCommandTest extends AcquiaCliTestCase
{

    /**
     * @dataProvider codeProvider
     */
    public function testCodeCommands($command, $expected)
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

        $codeDeploy = '>  Backing up DB (database1) on Stage
>  Backing up DB (database2) on Stage
>  Deploying code from the Dev environment to the Stage environment';

        $codeSwitch = '>  Backing up DB (database1) on Production
>  Backing up DB (database2) on Production
>  Switching Production enviroment to master branch';

        return [
            [
                ['code:deploy', 'devcloud:devcloud2', 'dev', 'test'],
                $codeDeploy . PHP_EOL
            ],
            [
                ['code:list', 'devcloud:devcloud2'],
                $codeList . PHP_EOL
            ],
            [
                ['code:list', 'devcloud:devcloud2', 'master'],
                $codeList . PHP_EOL
            ],
            [
                ['code:switch', 'devcloud:devcloud2', 'prod', 'master'],
                $codeSwitch . PHP_EOL
            ]
        ];
    }
}
