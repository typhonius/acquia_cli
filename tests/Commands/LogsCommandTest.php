<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;

class LogsCommandTest extends AcquiaCliTestCase
{

    public function testDownloadLogsCommands()
    {
        $command = ['log:download', 'devcloud:devcloud2', 'dev', 'apache-access'];
        $actualResponse = $this->execute($command);

        $this->assertEquals(
            preg_match('@>  Log downloaded to ((\S+)dev-apache-access-(\w+).tar.gz)@', $actualResponse, $matches),
            1
        );

        $this->assertStringStartsWith('>  Log downloaded to ', $actualResponse);
        $this->assertStringContainsString(sys_get_temp_dir(), $matches[2]);

        $path = sprintf(
            '%s/vendor/typhonius/acquia-php-sdk-v2/tests/Fixtures/Endpoints/%s',
            dirname(dirname(__DIR__)),
            'Logs/downloadLog.dat'
        );
        $this->assertFileExists($path);
        $contents = file_get_contents($path);
        if ($contents) {
            $this->assertStringEqualsFile($matches[1], $contents);
        }
    }

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
                ['log:list', 'devcloud:devcloud2', 'dev'],
                $logsList . PHP_EOL
            ],
            [
                ['log:snapshot', 'devcloud:devcloud2', 'dev', 'apache-access'],
                '>  Creating snapshot for apache-access in Dev environment' . PHP_EOL
            ],
        ];
    }
}
