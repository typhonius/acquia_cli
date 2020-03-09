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

        $domainPurge = <<<PURGE
>  Purging domain: www.example.com
>  Purging domain: other.example.com
>  Purging domain: *.example.com
PURGE;

        return [
            [
                ['domain:create', 'uuid', 'dev', 'domain'],
                '>  Adding domain to environment Dev' . PHP_EOL
            ],
            [
                ['domain:delete', 'uuid', 'test', 'domain'],
                '>  Removing domain from environment Stage' . PHP_EOL
            ],
            [
                ['domain:info', 'uuid', 'prod', 'domain'],
                $domainInfo . PHP_EOL
            ],
            [
                ['domain:list', 'uuid', 'dev'],
                $domainsList . PHP_EOL
            ],
            [
                ['domain:move', 'uuid', 'domain', 'dev', 'test'],
                '>  Moving domain from Dev to Stage' . PHP_EOL
            ],
            [
                ['domain:purge', 'uuid', 'dev'],
                $domainPurge . PHP_EOL
            ],
            [
                ['domain:purge', 'uuid', 'dev', 'www.domain1.com'],
                '>  Purging domain: www.domain1.com' . PHP_EOL
            ]
        ];
    }
}
