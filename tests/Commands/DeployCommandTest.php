<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;

class DeployCommandTest extends AcquiaCliTestCase
{

    /**
     * @dataProvider deployProvider
     */
    public function testDeployInfo($command, $expected)
    {
        $actualResponse = $this->execute($command);
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
                ['deploy:prepare', 'devcloud:devcloud2', 'dev', 'prod'],
                $deployResponseDev . PHP_EOL
            ],
            [
                ['deploy:prepare', 'devcloud:devcloud2', 'test'],
                $deployResponseTest . PHP_EOL
            ],
            [
                ['deploy:prepare', 'devcloud:devcloud2', 'prod'],
                $deployResponseProd . PHP_EOL
            ]
        ];
    }
}
