<?php

namespace AcquiaCli\Tests;

use AcquiaCli\Tests\AcquiaCliTestCase;
use AcquiaCli\Cli\Config;

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
}
