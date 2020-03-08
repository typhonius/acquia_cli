<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;

class LogsCommandTest extends AcquiaCliTestCase
{

    /**
     * @dataProvider logsProvider
     */
    public function testLogsCommands($command, $expected)
    {
        $actualResponse = $this->execute($command);
        $this->assertSame($expected, $actualResponse);
    }

    public function logsProvider()
    {

        $logsList = <<<TABLE
+------------------+------------------+-----------+
| Type             | Label            | Available |
+------------------+------------------+-----------+
| apache-access    | Apache access    |           |
| apache-error     | Apache error     |           |
| drupal-request   | Drupal request   |           |
| drupal-watchdog  | Drupal watchdog  |           |
| php-error        | PHP error        |           |
| mysql-slow-query | MySQL slow query |     ✓     |
+------------------+------------------+-----------+
TABLE;

        return [
            [
                ['log:list', 'uuid', 'environment'],
                $logsList . PHP_EOL
            ],
            [
                ['log:snapshot', 'uuid', 'environment', 'apache-access'],
                '>  Creating snapshot for apache-access in Mock Env environment' . PHP_EOL
            ]
        ];
    }
}
