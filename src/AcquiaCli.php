<?php

namespace AcquiaCli;

use Robo\Robo;
use Robo\Config\Config;
use Robo\Common\ConfigAwareTrait;
use Robo\Runner as RoboRunner;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Consolidation\AnnotatedCommand\CommandFileDiscovery;

class AcquiaCli {

    use ConfigAwareTrait;

    private $runner;

    public function __construct(
        Config $config,
        InputInterface $input = NULL,
        OutputInterface $output = NULL
    ) {

        // Create application.
        $this->setConfig($config);
        $application = new Application('Acquia Cli', '0.0.3');

        // Create and configure container.
        $container = Robo::createDefaultContainer($input, $output, $application, $config);

        $discovery = new CommandFileDiscovery();
        $discovery->setSearchPattern('*Command.php');
        $commandClasses = $discovery->discover(__DIR__ . '/Commands', '\AcquiaCli\Commands');

        // Instantiate Robo Runner.
        $this->runner = new RoboRunner([]);
        $this->runner->setContainer($container);
        $this->runner->registerCommandClasses($application, $commandClasses);
    }

    public function run(InputInterface $input, OutputInterface $output) {
        $status_code = $this->runner->run($input, $output);

        return $status_code;
    }
}