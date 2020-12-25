<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;
use AcquiaCli\Tests\Traits\CommandTesterTrait;
use AcquiaCli\Commands\DomainCommand;

class DomainsCommandTest extends AcquiaCliTestCase
{
    use CommandTesterTrait;

    public function setUp(): void
    {
        $this->setupCommandTester(DomainCommand::class);
    }

    /**
     * @dataProvider domainsProvider
     */
    public function testDomainsCommands($command, $arguments, $expected)
    {
        list($actualResponse, $statusCode) = $this->executeCommand($command, $arguments);
        $this->assertSame($expected, $actualResponse);
    }

    public function domainsProvider()
    {

        $domainInfo = <<<TABLE
+-------------+--------+--------------+--------------+---------------------+
| Hostname    | Active | DNS Resolves | IP Addresses | CNAMES              |
+-------------+--------+--------------+--------------+---------------------+
| example.com |   ✓    |      ✓       | 12.23.34.45  | another.example.com |
+-------------+--------+--------------+--------------+---------------------+
TABLE;

        $domainsList = <<<TABLE
+-------------------+---------+--------+--------+
| Hostname          | Default | Active | Uptime |
+-------------------+---------+--------+--------+
| www.example.com   |    ✓    |   ✓    |   ✓    |
| other.example.com |         |        |        |
| *.example.com     |         |        |        |
+-------------------+---------+--------+--------+
TABLE;

        $domainPurge = <<<PURGE
>  Purging domain: www.example.com
>  Purging domain: other.example.com
>  Purging domain: *.example.com
PURGE;

        return [
            [
                'domain:create',
                ['uuid' => 'devcloud:devcloud2', 'environment' => 'dev', 'domain' => 'domain'],
                '>  Adding domain to environment Dev'
            ],
            [
                'domain:delete',
                ['uuid' => 'devcloud:devcloud2', 'environment' => 'test', 'domain' => 'domain'],
                '>  Removing domain from environment Stage'
            ],
            [
                'domain:info',
                ['uuid' => 'devcloud:devcloud2', 'environment' => 'prod', 'domain' => 'domain'],
                $domainInfo
            ],
            [
                'domain:list',
                ['uuid' => 'devcloud:devcloud2', 'environment' => 'dev'],
                $domainsList
            ],
            [
                'domain:move',
                ['uuid' => 'devcloud:devcloud2', 'domain' => 'domain', 'environmentFrom' => 'dev', 'environmentTo' => 'test'],
                '>  Moving domain from Dev to Stage'
            ],
            [
                'domain:purge',
                ['uuid' => 'devcloud:devcloud2', 'environment' => 'dev'],
                $domainPurge
            ],
            [
                'domain:purge',
                ['uuid' => 'devcloud:devcloud2', 'environment' => 'dev', 'domain' => 'www.domain1.com'],
                '>  Purging domain: www.domain1.com'
            ]
        ];
    }
}
