<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;

class ApplicationCommandTest extends AcquiaCliTestCase
{

    /**
     * @dataProvider applicationProvider
     */
    public function testApplicationCommands($command, $fixture, $expected)
    {
        $psr7Response = $this->getPsr7JsonResponseForFixture($fixture);
        $client = $this->getMockClient($psr7Response);

        $actualResponse = $this->execute($client, $command);

        $this->assertSame($expected, $actualResponse);
    }

    public function applicationProvider()
    {
        $getAllApplications = <<<TABLE
+----------------------+--------------------------------------+--------------------+
| Name                 | UUID                                 | Hosting ID         |
+----------------------+--------------------------------------+--------------------+
| Sample application 1 | a47ac10b-58cc-4372-a567-0e02b2c3d470 | devcloud:devcloud2 |
| Sample application 2 | a47ac10b-58cc-4372-a567-0e02b2c3d471 | devcloud:devcloud2 |
+----------------------+--------------------------------------+--------------------+
TABLE;

        $getTags = <<<TABLE
+------+--------+
| Name | Color  |
+------+--------+
| Dev  | orange |
+------+--------+
TABLE;

        return [
            [
                ['application:list'],
                'Applications/getAllApplications.json',
                $getAllApplications . PHP_EOL

            ],
            [
                ['application:tags', 'uuid'],
                'Applications/getAllTags.json',
                $getTags . PHP_EOL
            ],
            [
                ['application:tag:create', 'uuid', 'name', 'color'],
                'Applications/createTag.json',
                '>  Creating application tag name:color' . PHP_EOL
            ],
            [
                ['application:tag:delete', 'uuid', 'name'],
                'Applications/deleteTag.json',
                '>  Deleting application tag name' . PHP_EOL
            ]
        ];
    }
}
