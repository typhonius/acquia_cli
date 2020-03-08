<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;

class ApplicationCommandTest extends AcquiaCliTestCase
{

    /**
     * @dataProvider applicationProvider
     */
    public function testApplicationCommands($command, $expected)
    {
        $actualResponse = $this->execute($command);
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
                ['application:list'],
                $getAllApplications . PHP_EOL
            ],
            [
                ['application:info', 'uuid'],
                $applicationInfo . PHP_EOL
            ],
            [
                ['application:tags', 'uuid'],
                $getTags . PHP_EOL
            ],
            [
                ['application:tag:create', 'uuid', 'name', 'color'],
                '>  Creating application tag name:color' . PHP_EOL
            ],
            [
                ['application:tag:delete', 'uuid', 'name'],
                '>  Deleting application tag name' . PHP_EOL
            ]
        ];
    }
}
