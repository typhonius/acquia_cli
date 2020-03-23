<?php

namespace AcquiaCli\Tests;

use AcquiaCli\Cli\Config;
use AcquiaCli\Cli\CloudApi;
use Symfony\Component\Console\Input\ArgvInput;
use AcquiaCli\Cli\AcquiaCli;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Stopwatch\Stopwatch;

class AcquiaCliApplicationTest extends AcquiaCliTestCase
{
    private $root;

    public function setUp()
    {
        $this->root = dirname(__DIR__);

        parent::setUp();
    }

    public function testConfig()
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

    public function testVersion()
    {
        $command = ['--version'];

        $actualValue = $this->execute($command);

        $this->assertContains('AcquiaCli 2', $actualValue);
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

        $notificationOutput =<<< OUTPUT
>  Backing up DB (database1) on Dev
 Looking up notification                      
< 1 sec [➤⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬⚬]   0%

OUTPUT;

        $this->assertSame($notificationOutput . PHP_EOL, $sleep5Output, 'Testing 5s sleep output');
        $this->assertSame($notificationOutput . PHP_EOL, $sleep2Output, 'Testing 2s sleep output');
    }
}
