<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;

class DomainsCommandTest extends AcquiaCliTestCase
{

    /**
     * @dataProvider domainsProvider
     */
    public function testDomainsCommands($command, $expected)
    {
        $actualResponse = $this->execute($command);
        $this->assertSame($expected, $actualResponse);
    }

    public function domainsProvider()
    {

        $domainInfo = <<<TABLE
+-------------+--------+--------------+--------------+---------------------+
| Hostname    | Active | DNS Resolves | IP Addresses | CNAMES              |
+-------------+--------+--------------+--------------+---------------------+
| example.com | ✓      | ✓            | 12.23.34.45  | another.example.com |
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

        return [
            [
                ['domain:create', 'uuid', 'environment', 'domain'],
                '>  Adding domain to environment Mock Env' . PHP_EOL
            ],
            [
                ['domain:delete', 'uuid', 'environment', 'domain'],
                '>  Removing domain from environment Mock Env' . PHP_EOL
            ],
            [
                ['domain:info', 'uuid', 'environment', 'domain'],
                $domainInfo . PHP_EOL
            ],
            [
                ['domain:list', 'uuid', 'environment'],
                $domainsList . PHP_EOL
            ],
            [
                ['domain:move', 'uuid', 'domain', 'environmentFrom', 'environmentTo'],
                '>  Moving domain from Mock Env to Mock Env' . PHP_EOL
            ],
            [
                ['domain:purge', 'uuid', 'environment', 'www.domain1.com'],
                '>  Purging domain: www.domain1.com' . PHP_EOL
            ]
        ];
    }
}
