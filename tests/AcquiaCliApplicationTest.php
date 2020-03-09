<?php

namespace AcquiaCli\Tests;

use AcquiaCli\Tests\AcquiaCliTestCase;
use AcquiaCli\Cli\Config;
use AcquiaCli\Cli\CloudApi;

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
            'timeout' => 300,
            'configsyncdir' => 'sync'
        ];

        $this->assertEquals($defaultAcquiaConfig, $config->get('acquia'));
        $this->assertEquals($defaultExtraConfig, $config->get('extraconfig'));
    }

    public function testVersion()
    {
        $command = ['--version'];

        $actualValue = $this->execute($command);

        $this->assertSame('AcquiaCli 2.0.0-dev' . PHP_EOL, $actualValue);
    }

    public function testCloudApi()
    {

        $config = new Config($this->root);
        $cloudApi = new CloudApi($config, $this->client);

        $applicationUuid = $cloudApi->getApplicationUuid('devcloud:devcloud2');
        $this->assertSame('a47ac10b-58cc-4372-a567-0e02b2c3d470', $applicationUuid);

        try {
            $applicationError = $cloudApi->getApplicationUuid('devcloud:foobar');
        } catch (\Exception $e) {
            $this->assertSame('Unable to find UUID for application', $e->getMessage());
        }

        $environment = $cloudApi->getEnvironment('uuid', 'dev');
        $this->assertInstanceOf('\AcquiaCloudApi\Response\EnvironmentResponse', $environment);
        $this->assertSame('24-a47ac10b-58cc-4372-a567-0e02b2c3d470', $environment->uuid);
        $this->assertSame('dev', $environment->name);
        $this->assertSame('Dev', $environment->label);
        $this->assertSame('us-east-1', $environment->region);
        $this->assertSame('normal', $environment->status);

        try {
            $environmentError = $cloudApi->getEnvironment('uuid', 'foobar');
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
}
