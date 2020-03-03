<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;
use AcquiaCli\Commands\SetupCommand;

use Robo\Common\OutputAwareTrait;
use Consolidation\AnnotatedCommand\CommandFileDiscovery;
use Symfony\Component\Console\Output\ConsoleOutput;


class SetupCommandTest extends AcquiaCliTestCase
{
    protected function setup()
    {
        parent::setUp();
    }

    public function taestSetupConstructor()
    {
        // $setup = new SetupCommand();

        // $config = $this->getPrivateProperty(get_class($setup), 'configFiles');
        // $this->assertArrayHasKey('global', $config->getValue($setup));
        // $this->assertArrayHasKey('project', $config->getValue($setup));

    }
}
