<?php

namespace AcquiaCli\Tests;

use AcquiaCli\Cli\Config;
use AcquiaCli\Cli\CloudApi;
use Symfony\Component\Console\Input\ArgvInput;
use AcquiaCli\Cli\AcquiaCli;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Output\OutputInterface;

class AcquiaCliApplicationTest extends AcquiaCliTestCase
{
    use LockableTrait;

    private $root;

    public function setUp()
    {
        $this->root = dirname(__DIR__);

        parent::setUp();
    }

    public function testDefaultConfig()
    {
        $config = new Config($this->root);

        $defaultAcquiaConfig = [
            'key' => 'd0697bfc-7f56-4942-9205-b5686bf5b3f5',
            'secret' => 'D5UfO/4FfNBWn4+0cUwpLOoFzfP7Qqib4AoY+wYGsKE='
        ];

        $defaultExtraConfig = [
            'timezone' => 'Australia/Sydney',
            'format' => 'Y-m-d H:i:s',
            'taskwait' => 5,
            'timeout' => 300
        ];

        $this->assertEquals($defaultAcquiaConfig, $config->get('acquia'));
        $this->assertEquals($defaultExtraConfig, $config->get('extraconfig'));
    }

    public function testEnvironmentConfig()
    {
        // Set environment variables so we can test configuration overrides.
        putenv('ACQUIACLI_KEY=16fd1cde-1e66-b113-8e98-5ff9d444d54f');
        putenv('ACQUIACLI_SECRET=SDtg0o83TrZm5gVckpaZynCxpikMqcht9u3fexWIHm7');
        putenv('ACQUIACLI_TIMEZONE=Australia/Hobart');
        putenv('ACQUIACLI_FORMAT=Y-m-d H:i:s (U)');
        putenv('ACQUIACLI_TASKWAIT=2');
        putenv('ACQUIACLI_TIMEOUT=400');

        $config = new Config($this->root);

        $environmentAcquiaConfig = [
            'key' => '16fd1cde-1e66-b113-8e98-5ff9d444d54f',
            'secret' => 'SDtg0o83TrZm5gVckpaZynCxpikMqcht9u3fexWIHm7'
        ];

        $environmentExtraConfig = [
            'timezone' => 'Australia/Hobart',
            'format' => 'Y-m-d H:i:s (U)',
            'taskwait' => 2,
            'timeout' => 400
        ];

        $this->assertEquals($environmentAcquiaConfig, $config->get('acquia'));
        $this->assertEquals($environmentExtraConfig, $config->get('extraconfig'));

        // Remove them at the end of the test so they do not interfere with other tests.
        putenv('ACQUIACLI_KEY=');
        putenv('ACQUIACLI_SECRET=');
        putenv('ACQUIACLI_TIMEZONE=');
        putenv('ACQUIACLI_FORMAT=');
        putenv('ACQUIACLI_TASKWAIT=');
        putenv('ACQUIACLI_TIMEOUT=');
    }

    public function testVersion()
    {
        $versionFile = sprintf('%s/VERSION', $this->root);
        $version = file_get_contents($versionFile);

        $command = ['--version'];
        $actualValue = $this->execute($command);

        $this->assertEquals(sprintf('AcquiaCli %s', $version), $actualValue);
    }

    public function testMissingVersion()
    {
        $versionFile = sprintf('%s/VERSION', $this->root);
        $versionFileBak = sprintf('%s.bak', $versionFile);
        rename($versionFile, $versionFileBak);

        try {
            $command = ['--version'];
            $this->execute($command);
        } catch (\Exception $e) {
            $this->assertEquals('Exception', get_class($e));
            $this->assertEquals('No VERSION file', $e->getMessage());
        }
        rename($versionFileBak, $versionFile);
    }

    public function testLock()
    {
        // Obtain an identical lock and then attempt to run acquiacli.
        $this->lock('acquia-cli-command');
        $command = ['--version'];
        $actualValue = $this->execute($command);
        $this->assertContains('The command is already running in another process.', $actualValue);

        // Use --no-lock to override the lock.
        $command[] = '--no-lock';
        $actualValue = $this->execute($command);
        $this->assertContains('AcquiaCli', $actualValue);

        // Unlock to ensure tests are able to continue.
        $this->release();
    }

    public function testClientOptions()
    {
        $command = ['application:list', '--sort=label', '--filter=label=@*sample*', '--limit=2'];

        $this->execute($command);

        $expectedQuery = [
            'limit' => '2',
            'sort' => 'label',
            'filter' => 'label=@*sample*'
        ];

        $actualQuery = $this->client->getQuery();
        $this->assertSame($expectedQuery, $actualQuery);
    }

    public function testClientUserAgent()
    {
        $command = ['application:list'];

        $this->execute($command);

        $versionFile = sprintf('%s/VERSION', $this->root);
        if ($file = @file_get_contents($versionFile)) {
            $version = trim($file);
        } else {
            throw new \Exception('No VERSION file');
        }
        $expectedUserAgent = sprintf("%s/%s (https://github.com/typhonius/acquia_cli)", 'AcquiaCli', $version);

        $actualOptions = $this->client->getOptions();
        $actualUserAgent = $actualOptions['headers']['User-Agent'];
        $this->assertSame($expectedUserAgent, $actualUserAgent);
    }

    public function testRealm()
    {
        $command = ['application:info', 'devcloud2', '--realm=devcloud'];
        $response = $this->execute($command);
        // We're not looking to test the complete output here, just that we get one.
        $this->assertStringContainsString('24-a47ac10b-58cc-4372-a567-0e02b2c3d470', $response);
    }

    public function testVerbosity()
    {
        $config = new Config($this->root);
        $command = ['acquiacli', '--yes', '--no-wait', 'domain:delete', 'devcloud:devcloud2', 'test', 'domain'];
        $input = new ArgvInput($command);
        $output = new BufferedOutput();

        // Spoof the use of the -v flag here by directly setting the output verbosity.
        // If we do not do this, all subsequent tests run with -v which is weird.
        $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
        $app = new AcquiaCli($config, $this->client, $input, $output);
        $app->run($input, $output);
        $output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);

        $response = $output->fetch();
        $expected = <<< EXPECTED
>  Ignoring confirmation question as --yes option passed.
>  Removing domain from environment Stage
>  Skipping wait for notification.
EXPECTED;

        $this->assertSame($expected . PHP_EOL, $response);

        \Robo\Robo::unsetContainer();
    }

    public function testCloudApi()
    {

        $config = new Config($this->root);
        $cloudApi = new CloudApi($config, $this->client);

        $this->assertInstanceOf('\AcquiaCloudApi\Connector\Client', $cloudApi->getClient());

        $applicationUuid = $cloudApi->getApplicationUuid('devcloud:devcloud2');
        $this->assertSame('a47ac10b-58cc-4372-a567-0e02b2c3d470', $applicationUuid);

        try {
            $applicationError = $cloudApi->getApplicationUuid('devcloud:foobar');
        } catch (\Exception $e) {
            $this->assertSame('Unable to find UUID for application', $e->getMessage());
        }

        $environment = $cloudApi->getEnvironment('a47ac10b-58cc-4372-a567-0e02b2c3d470', 'dev');
        $this->assertInstanceOf('\AcquiaCloudApi\Response\EnvironmentResponse', $environment);
        $this->assertSame('24-a47ac10b-58cc-4372-a567-0e02b2c3d470', $environment->uuid);
        $this->assertSame('dev', $environment->name);
        $this->assertSame('Dev', $environment->label);
        $this->assertSame('us-east-1', $environment->region);
        $this->assertSame('normal', $environment->status);

        try {
            $environmentError = $cloudApi->getEnvironment('a47ac10b-58cc-4372-a567-0e02b2c3d470', 'foobar');
        } catch (\Exception $e) {
            $this->assertSame('Unable to find environment from environment name', $e->getMessage());
        }


        $command = ['organization:members', 'foobar'];
        $organizationError = $this->execute($command);
        $this->assertSame(
            ' [error]  Unable to find organization from organization name ' . PHP_EOL,
            $organizationError
        );
    }

    public function testWaitForNotifications()
    {
        $config = new Config($this->root);

        $command = ['acquiacli', '--yes', 'db:backup', 'devcloud:devcloud2', 'dev', 'database1'];
        $input = new ArgvInput($command);
        $output = new BufferedOutput();
        $app = new AcquiaCli($config, $this->client, $input, $output);

        // Time the command to make sure the sleep is included.
        $stopwatch = new Stopwatch();
        $stopwatch->start('5s-sleep', 'notifications');
        $app->run($input, $output);
        $sleep5 = $stopwatch->stop('5s-sleep');
        $this->assertGreaterThan(5000, $sleep5->getDuration());
        $sleep5Output = $output->fetch();

        // Change the task wait threshold to 2s and try again.
        $defaultConfig = ['taskwait' => 2, 'timeout' => 300];
        $config->set('extraconfig', $defaultConfig);

        // Test for less than 5 seconds as the app takes ~2s to boot.
        $stopwatch->start('2s-sleep', 'notifications');
        $app->run($input, $output);
        $sleep2 = $stopwatch->stop('2s-sleep');
        $this->assertLessThan(3000, $sleep2->getDuration());
        $sleep2Output = $output->fetch();

        \Robo\Robo::unsetContainer();

        $notificationOutput = <<< OUTPUT
>  Backing up DB (database1) on Dev
 Looking up notification                      
< 1 sec [➤⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬]   0%

OUTPUT;

        $this->assertSame($notificationOutput . PHP_EOL, $sleep5Output, 'Testing 5s sleep output');
        $this->assertSame($notificationOutput . PHP_EOL, $sleep2Output, 'Testing 2s sleep output');
    }
}
