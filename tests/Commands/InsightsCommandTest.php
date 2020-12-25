<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;
use AcquiaCli\Tests\Traits\CommandTesterTrait;
use AcquiaCli\Commands\InsightsCommand;

class InsightsCommandTest extends AcquiaCliTestCase
{
    use CommandTesterTrait;

    public function setUp(): void
    {
        $this->setupCommandTester(InsightsCommand::class);
    }

    /**
     * @dataProvider insightsProvider
     */
    public function testInsightsCommands($command, $arguments, $expected)
    {
        list($actualResponse, $statusCode) = $this->executeCommand($command, $arguments);
        $this->assertSame($expected, $actualResponse);
    }

    public function insightsProvider()
    {

        // phpcs:disable Generic.Files.LineLength.TooLong
        $insightAlert = <<<ALERT
>  UUID: a47ac10b-58cc-4372-a567-0e02b2c3d470
>  Name: PHP errors visible
>  Message: Your website is configured to display PHP error messages to users. These error messages can reveal sensitive information about your website and its server to site visitors.
ALERT;

        // phpcs:enable

        $insightModules = <<<TABLE
+-----------------+----------------+---------+-------------+
| Name            | Version        | Enabled | Upgradeable |
+-----------------+----------------+---------+-------------+
| Acquia agent    | 7.x-3.0-alpha1 |    ✓    |      ✓      |
| Aggregator      | 7.50           |         |             |
| A Custom Module |                |         |             |
+-----------------+----------------+---------+-------------+
TABLE;

        $insightAlerts = <<<ALERTS
+--------------------------------------+------------------------------+--------+----------+---------+
| UUID                                 | Description                  | Failed | Resolved | Ignored |
+--------------------------------------+------------------------------+--------+----------+---------+
| a47ac10b-58cc-4372-a567-0e02b2c3d470 | PHP errors visible           |   ✓    |          |         |
| f938d912-a6a0-11e2-b0d3-12313931d529 | Scheduler module not enabled |   ✓    |          |         |
| f93dbb1f-a6a0-11e2-b0d3-12313931d529 | CSS optimization disabled    |   ✓    |    ✓     |         |
+--------------------------------------+------------------------------+--------+----------+---------+
ALERTS;

        $insightEnvironmentInfo = <<<INFO
Example local development (local.example.com:8443) Score: 62 
                                                                 
>  Site ID: 50227ff0-2a53-11e9-b210-d663bd873d93
>  Status: ok
+----------------+------+------+---------+-------+----+
| Type           | Pass | Fail | Ignored | Total | %  |
+----------------+------+------+---------+-------+----+
| Best Practices | 5    | 1    | 0       | 6     | 83 |
| Performance    | 9    | 10   | 0       | 19    | 47 |
| Security       | 10   | 10   | 0       | 20    | 50 |
+----------------+------+------+---------+-------+----+
                                                 
    Test Site: prod (test.example.com) Score: 62 
                                                 
>  Site ID: 50227ff0-2a53-11e9-b210-d663bd873d93
>  Status: ok
+----------------+------+------+---------+-------+----+
| Type           | Pass | Fail | Ignored | Total | %  |
+----------------+------+------+---------+-------+----+
| Best Practices | 5    | 1    | 0       | 6     | 83 |
| Performance    | 10   | 9    | 0       | 19    | 53 |
| Security       | 11   | 9    | 0       | 20    | 55 |
+----------------+------+------+---------+-------+----+
INFO;

        $insightApplicationInfo = <<<INFO
Example local development (local.example.com:8443) Score: 62
                                                                 
>  Site ID: 1bc0b462-2665-11e9-ab14-d663bd873d93
>  Status: ok
+----------------+------+------+---------+-------+----+
| Type           | Pass | Fail | Ignored | Total | %  |
+----------------+------+------+---------+-------+----+
| Best Practices | 5    | 1    | 0       | 6     | 83 |
| Performance    | 9    | 10   | 0       | 19    | 47 |
| Security       | 10   | 10   | 0       | 20    | 50 |
+----------------+------+------+---------+-------+----+
                                                 
    Test Site: prod (test.example.com) Score: 62 
                                                 
>  Site ID: 63645c1a-2665-11e9-ab14-d663bd873d93
>  Status: ok
+----------------+------+------+---------+-------+----+
| Type           | Pass | Fail | Ignored | Total | %  |
+----------------+------+------+---------+-------+----+
| Best Practices | 5    | 1    | 0       | 6     | 83 |
| Performance    | 10   | 9    | 0       | 19    | 53 |
| Security       | 11   | 9    | 0       | 20    | 55 |
+----------------+------+------+---------+-------+----+
INFO;


        return [
            [
                'insights:alerts:get',
                ['siteId' => 'siteId', 'alertUuid' => 'alertUuid'],
                $insightAlert
            ],
            [
                'insights:alerts:list',
                ['siteId' => 'siteId'],
                $insightAlerts
            ],
            [
                'insights:info',
                ['uuid' => 'devcloud:devcloud2', 'environment' => 'dev'],
                $insightEnvironmentInfo
            ],
            [
                'insights:info',
                ['uuid' => 'devcloud:devcloud2'],
                $insightApplicationInfo
            ],
            [
                'insights:modules',
                ['siteId' => 'siteId'],
                $insightModules
            ]
        ];
    }
}
