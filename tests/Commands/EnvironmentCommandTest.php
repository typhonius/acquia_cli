<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;
use AcquiaCli\Tests\Traits\CommandTesterTrait;
use AcquiaCli\Commands\EnvironmentsCommand;

class EnvironmentCommandTest extends AcquiaCliTestCase
{
    use CommandTesterTrait;

    public function setUp(): void
    {
        $this->setupCommandTester(EnvironmentsCommand::class);
    }

    /**
     * @dataProvider environmentProvider
     */
    public function testEnvironmentCommands($command, $arguments, $expected)
    {
        list($actualResponse, $statusCode) = $this->executeCommand($command, $arguments);
        $this->assertSame($expected, $actualResponse);
    }

    public function environmentProvider()
    {
        $getAllEnvironments = <<<TABLE
+-----------------------------------------+------+------------+----------------------------------+
| UUID                                    | Name | Label      | Domains                          |
+-----------------------------------------+------+------------+----------------------------------+
| 24-a47ac10b-58cc-4372-a567-0e02b2c3d470 | dev  | Dev        | sitedev.hosted.acquia-sites.com  |
|                                         |      |            | example.com                      |
| 15-a47ac10b-58cc-4372-a567-0e02b2c3d470 | prod | Production | siteprod.hosted.acquia-sites.com |
|                                         |      |            | example.com                      |
| 32-a47ac10b-58cc-4372-a567-0e02b2c3d470 | test | Stage      | sitetest.hosted.acquia-sites.com |
|                                         |      |            | test.example.com                 |
+-----------------------------------------+------+------------+----------------------------------+
TABLE;

        $getEnvironmentInfo = <<<TABLE
Dev environment              
                                             
>  Environment ID: 24-a47ac10b-58cc-4372-a567-0e02b2c3d470
+---------+-------+--------------------------+-----------+-----------+----------+----------+--------+---------+-----+
| Role(s) | Name  | FQDN                     | AMI       | Region    | IP       | Memcache | Active | Primary | EIP |
+---------+-------+--------------------------+-----------+-----------+----------+----------+--------+---------+-----+
| web, db | ded-6 | ded-6.servers.acquia.com | c1.medium | us-west-1 | 10.0.0.1 | ✓        | ✓      | ✓       |     |
| bal     | bal-4 | bal-4.servers.acquia.com | m1.small  | us-west-1 | 10.0.0.2 |          |        | ✓       |     |
+---------+-------+--------------------------+-----------+-----------+----------+----------+--------+---------+-----+
+--------+-----+-------------+--------------+---------+------+---------------+
| Branch | CDE | PHP Version | Memory Limit | OpCache | APCu | Sendmail Path |
+--------+-----+-------------+--------------+---------+------+---------------+
| master |     | 7.2         | 128          | 96      | 32   |               |
+--------+-----+-------------+--------------+---------+------+---------------+
                                             
             Production environment          
                                             
>  Environment ID: 15-a47ac10b-58cc-4372-a567-0e02b2c3d470
>  🔒  Production mode enabled.
+---------+-------+--------------------------+-----------+-----------+----------+----------+--------+---------+-----+
| Role(s) | Name  | FQDN                     | AMI       | Region    | IP       | Memcache | Active | Primary | EIP |
+---------+-------+--------------------------+-----------+-----------+----------+----------+--------+---------+-----+
| web, db | ded-6 | ded-6.servers.acquia.com | c1.medium | us-west-1 | 10.0.0.1 | ✓        | ✓      | ✓       |     |
| bal     | bal-4 | bal-4.servers.acquia.com | m1.small  | us-west-1 | 10.0.0.2 |          |        | ✓       |     |
+---------+-------+--------------------------+-----------+-----------+----------+----------+--------+---------+-----+
+-----------------+-----+-------------+--------------+---------+------+---------------+
| Branch          | CDE | PHP Version | Memory Limit | OpCache | APCu | Sendmail Path |
+-----------------+-----+-------------+--------------+---------+------+---------------+
| tags/01-01-2015 |     |             |              |         |      |               |
+-----------------+-----+-------------+--------------+---------+------+---------------+
                                             
               Stage environment             
                                             
>  Environment ID: 32-a47ac10b-58cc-4372-a567-0e02b2c3d470
+---------+-------+--------------------------+-----------+-----------+----------+----------+--------+---------+-----+
| Role(s) | Name  | FQDN                     | AMI       | Region    | IP       | Memcache | Active | Primary | EIP |
+---------+-------+--------------------------+-----------+-----------+----------+----------+--------+---------+-----+
| web, db | ded-6 | ded-6.servers.acquia.com | c1.medium | us-west-1 | 10.0.0.1 | ✓        | ✓      | ✓       |     |
| bal     | bal-4 | bal-4.servers.acquia.com | m1.small  | us-west-1 | 10.0.0.2 |          |        | ✓       |     |
+---------+-------+--------------------------+-----------+-----------+----------+----------+--------+---------+-----+
+--------+-----+-------------+--------------+---------+------+---------------+
| Branch | CDE | PHP Version | Memory Limit | OpCache | APCu | Sendmail Path |
+--------+-----+-------------+--------------+---------+------+---------------+
|        |     |             |              |         |      |               |
+--------+-----+-------------+--------------+---------+------+---------------+
>  Web servers not marked 'Active' are out of rotation.
>  Load balancer servers not marked 'Active' are hot spares
>  Database servers not marked 'Primary' are the passive master
TABLE;

        return [
            [
                'environment:list',
                ['uuid' => 'devcloud:devcloud2'],
                $getAllEnvironments
            ],
            [
                'environment:info',
                ['uuid' => 'devcloud:devcloud2', 'env' => 'dev'],
                $getEnvironmentInfo
            ],
            [
                'environment:branch',
                ['uuid' => 'devcloud:devcloud2', 'environment' => 'dev'],
                '>  master'
            ],
            [
                'environment:rename',
                ['uuid' => 'devcloud:devcloud2', 'environment' => 'dev', 'name' => 'name'],
                '>  Renaming Dev to name'
            ],
            [
                'environment:delete',
                ['uuid' => 'devcloud:devcloud2', 'environment' => 'dev'],
                '>  Deleting Dev environment'
            ],
            [
                'environment:configure',
                ['uuid' => 'devcloud:devcloud2', 'environment' => 'dev', 'key' => 'version', 'value' => '7.4'],
                '>  Configuring Dev with [version => 7.4]'
            ]
        ];
    }
}
