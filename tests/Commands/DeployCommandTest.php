<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;
use AcquiaCli\Tests\Traits\CommandTesterTrait;
use AcquiaCli\Commands\DeployCommand;

class DeployCommandTest extends AcquiaCliTestCase
{
    use CommandTesterTrait;

    public function setUp(): void
    {
        $this->setupCommandTester(DeployCommand::class);
    }

    /**
     * @dataProvider deployProvider
     */
    public function testDeployInfo($command, $arguments, $expected)
    {
        list($actualResponse, $statusCode) = $this->executeCommand($command, $arguments);
        $this->assertSame($expected, $actualResponse);
    }

    public function deployProvider()
    {

        $deployResponseDev = <<<INFO
>  Backing up DB (database1) on Dev
>  Moving DB (database1) from Production to Dev
>  Backing up DB (database2) on Dev
>  Moving DB (database2) from Production to Dev
>  Copying files from Production to Dev
INFO;

        $deployResponseTest = <<<INFO
>  Backing up DB (database1) on Stage
>  Moving DB (database1) from Production to Stage
>  Backing up DB (database2) on Stage
>  Moving DB (database2) from Production to Stage
>  Copying files from Production to Stage
INFO;

        $deployResponseProd = <<<INFO
 [error]  Cannot use deploy:prepare on the production environment 
INFO;

        return [
            [
                'deploy:prepare',
                ['uuid' => 'devcloud:devcloud2', 'environmentTo' => 'dev', 'environmentFrom' => 'prod'],
                $deployResponseDev
            ],
            [
                'deploy:prepare',
                ['uuid' => 'devcloud:devcloud2', 'environmentTo' => 'test'],
                $deployResponseTest
            ],
            [
                'deploy:prepare',
                ['uuid' => 'devcloud:devcloud2', 'environmentTo' => 'prod'],
                $deployResponseProd
            ]
        ];
    }
}
