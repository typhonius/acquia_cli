<?php

namespace AcquiaCli\Tests\Commands;

use Robo\Robo;
use AcquiaCli\Cli\Config;
use AcquiaCli\Cli\AcquiaCli;
use AcquiaCli\Tests\AcquiaCliTestCase;
use Symfony\Component\Console\Input\ArgvInput;
use Consolidation\Config\Loader\ConfigProcessor;
use Consolidation\Config\Loader\YamlConfigLoader;
use Symfony\Component\Console\Output\BufferedOutput;
use AcquiaCli\Tests\Traits\CommandTesterTrait;
use AcquiaCli\Commands\SetupCommand;

class SetupCommandTest extends AcquiaCliTestCase
{
    use CommandTesterTrait;

    public function setUp(): void
    {
        $this->setupCommandTester(SetupCommand::class);
    }

    public function testSetupConfigViewDefault()
    {
        $command = 'setup:config:view';
        $defaultConfiguration = <<< DEFAULT
Default configuration           
                                             
acquia:
  key: 'd0697bfc-7f56-4942-9205-b5686bf5b3f5'
  secret: 'D5UfO/4FfNBWn4+0cUwpLOoFzfP7Qqib4AoY+wYGsKE='
extraconfig:
  timezone: 'Australia/Sydney'
  format: 'Y-m-d H:i:s'
  taskwait: 5
  timeout: 300

                                             
             Running configuration           
                                             
acquia:
    key: d0697bfc-7f56-4942-9205-b5686bf5b3f5
    secret: D5UfO/4FfNBWn4+0cUwpLOoFzfP7Qqib4AoY+wYGsKE=
extraconfig:
    timezone: Australia/Sydney
    format: 'Y-m-d H:i:s'
    taskwait: 5
    timeout: 300
DEFAULT;

        list($actualResponse, $statusCode) = $this->executeCommand($command);
        $this->assertSame($defaultConfiguration, $actualResponse);
    }

    public function testSetupConfigViewOverwritten()
    {
        $command = 'setup:config:view';
        $overwrittenConfiguration = <<< OVERWRITTEN
Default configuration           
                                             
acquia:
  key: 'd0697bfc-7f56-4942-9205-b5686bf5b3f5'
  secret: 'D5UfO/4FfNBWn4+0cUwpLOoFzfP7Qqib4AoY+wYGsKE='
extraconfig:
  timezone: 'Australia/Sydney'
  format: 'Y-m-d H:i:s'
  taskwait: 5
  timeout: 300

                                             
           Environment configuration         
                                             
extraconfig:
    timezone: Australia/Melbourne
    format: U

                                             
             Running configuration           
                                             
acquia:
    key: d0697bfc-7f56-4942-9205-b5686bf5b3f5
    secret: D5UfO/4FfNBWn4+0cUwpLOoFzfP7Qqib4AoY+wYGsKE=
extraconfig:
    timezone: Australia/Melbourne
    format: U
    taskwait: 5
    timeout: 300
OVERWRITTEN;

        putenv('ACQUIACLI_TIMEZONE=Australia/Melbourne');
        putenv('ACQUIACLI_FORMAT=U');

        list($actualResponse, $statusCode) = $this->executeCommand($command);
        $this->assertSame($overwrittenConfiguration, $actualResponse);

        putenv('ACQUIACLI_TIMEZONE=');
        putenv('ACQUIACLI_FORMAT=');
    }
}
