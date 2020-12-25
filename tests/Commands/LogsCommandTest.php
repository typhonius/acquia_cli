<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;
use AcquiaCli\Tests\Traits\CommandTesterTrait;
use AcquiaCli\Commands\LogsCommand;

class LogsCommandTest extends AcquiaCliTestCase
{
    use CommandTesterTrait;

    public function setUp(): void
    {
        $this->setupCommandTester(LogsCommand::class);
    }

    public function testDownloadLogsCommands()
    {
        $command = 'log:download';
        $arguments = ['uuid' => 'devcloud:devcloud2', 'environment' => 'dev', 'logType' => 'apache-access'];
        list($actualResponse, $statusCode) = $this->executeCommand($command, [], $arguments);

        $this->assertEquals(
            preg_match('@>  Log downloaded to ((\S+)dev-apache-access(\w+).tar.gz)@', $actualResponse, $matches),
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

    public function testDownloadLogsCommandsWithOptions()
    {
        $command = 'log:download';
        $arguments = [
            'uuid' => 'devcloud:devcloud2',
            'environment' => 'dev',
            'logType' => 'apache-access',
            '--filename' => 'bar',
            '--path' => '/tmp'
        ];

        list($actualResponse, $statusCode) = $this->executeCommand($command, [], $arguments);

        $this->assertEquals(
            preg_match(
                '@>  Log downloaded to ((/tmp/)bar.tar.gz)@',
                $actualResponse,
                $matches
            ),
            1
        );

        $this->assertStringStartsWith('>  Log downloaded to ', $actualResponse);
        $this->assertStringContainsString('/tmp/', $matches[2]);
    }

    public function testLogstream()
    {

        $command = 'log:stream';
        $arguments = [
            'uuid' => 'devcloud:devcloud2',
            'environment' => 'dev',
            '--colourise' => true,
            '--logtypes' => ['apache-access'],
            '--servers' => ['web-1234']
        ];

        list($actualResponse, $statusCode) = $this->executeCommand($command, [], $arguments);

        $authArray = [
            'site' => 'clouduidev:qa4',
            'd' => 'd8b940bb5a1865e57b22734d541ed981c89f952e527b0a983d0e457437a43c23',
            't' => 1516990002,
            'env' => 'prod',
            'cmd' => 'stream-environment'
        ];
        $this->assertSame('1.1.1.1', $this->logstream->getDns());
        $this->assertSame(['apache-access'], $this->logstream->getLogTypeFilter());
        $this->assertSame(['web-1234'], $this->logstream->getLogServerFilter());
        $this->assertSame(10, $this->logstream->getTimeout());
        $this->assertSame(true, $this->logstream->getColourise());

        $class = new \ReflectionClass(get_class($this->logstream));
        $method = $class->getMethod('getAuthArray');
        $method->setAccessible(true);
        $output = $method->invoke($this->logstream);

        $this->assertEquals($authArray, $output);
    }

    /**
     * @dataProvider logsProvider
     */
    public function testLogsCommands($command, $arguments, $expected)
    {
        list($actualResponse, $statusCode) = $this->executeCommand($command, $arguments);
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
                'log:list',
                ['uuid' => 'devcloud:devcloud2', 'environment' => 'dev'],
                $logsList
            ],
            [
                'log:snapshot',
                ['uuid' => 'devcloud:devcloud2', 'environment' => 'dev', 'logType' => 'apache-access'],
                '>  Creating snapshot for apache-access in Dev environment'
            ],
        ];
    }
}
