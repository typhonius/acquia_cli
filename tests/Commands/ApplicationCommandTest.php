<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;
use AcquiaCli\Tests\Traits\CommandTesterTrait;
use AcquiaCli\Commands\ApplicationsCommand;

class ApplicationCommandTest extends AcquiaCliTestCase
{
    use CommandTesterTrait;

    public function setUp(): void
    {
        $this->setupCommandTester(ApplicationsCommand::class);
    }

    /**
     * @dataProvider applicationProvider
     */
    public function testApplicationCommands($command, $arguments, $expected)
    {
        list($actualResponse, $statusCode) = $this->executeCommand($command, $arguments);
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

        // phpcs:disable Generic.Files.LineLength.TooLong
        $applicationInfo = <<<TABLE
+----------------------+-----------------------------------------+-----------------+----------------------------------+-------------+
| Environment          | ID                                      | Branch/Tag      | Domain(s)                        | Database(s) |
+----------------------+-----------------------------------------+-----------------+----------------------------------+-------------+
| Dev (dev)            | 24-a47ac10b-58cc-4372-a567-0e02b2c3d470 | master          | sitedev.hosted.acquia-sites.com  | database1   |
|                      |                                         |                 | example.com                      | database2   |
| 🔒  Production (prod) | 15-a47ac10b-58cc-4372-a567-0e02b2c3d470 | tags/01-01-2015 | siteprod.hosted.acquia-sites.com | database1   |
|                      |                                         |                 | example.com                      | database2   |
| Stage (test)         | 32-a47ac10b-58cc-4372-a567-0e02b2c3d470 |                 | sitetest.hosted.acquia-sites.com | database1   |
|                      |                                         |                 | test.example.com                 | database2   |
+----------------------+-----------------------------------------+-----------------+----------------------------------+-------------+
>  🔧  Git URL: qa10@svn-3.networkdev.ahserversdev.com:qa10.git
>  💻  indicates environment in livedev mode.
>  🔒  indicates environment in production mode.
TABLE;
        // phpcs:enable

        $getTags = <<<TABLE
+------+--------+
| Name | Color  |
+------+--------+
| Dev  | orange |
+------+--------+
TABLE;

        return [
            [
                'application:list',
                [],
                $getAllApplications
            ],
            [
                'application:info',
                ['uuid' => 'devcloud:devcloud2'],
                $applicationInfo
            ],
            [
                'application:tags',
                ['uuid' => 'devcloud:devcloud2'],
                $getTags
            ],
            [
                'application:tag:create',
                ['uuid' => 'devcloud:devcloud2', 'name' => 'name', 'color' => 'color'],
                '>  Creating application tag name:color'
            ],
            [
                'application:tag:delete',
                ['uuid' => 'devcloud:devcloud2', 'name' => 'name'],
                '>  Deleting application tag name'
            ],
            [
                'application:rename',
                ['uuid' => 'devcloud:devcloud2', 'name' => 'foobar'],
                '>  Renaming application to foobar'
            ]
        ];
    }
}
