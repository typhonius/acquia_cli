<?php

namespace AcquiaCli;

use Robo\Robo;
use Robo\Config\Config;
use Robo\Common\ConfigAwareTrait;
use Robo\Runner as RoboRunner;
use \Robo\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Consolidation\AnnotatedCommand\CommandFileDiscovery;

/**
 * Class AcquiaCli
 * @package AcquiaCli
 */
class AcquiaCli
{

    use ConfigAwareTrait;

    private $runner;

    const NAME = 'AcquiaCli';

    const VERSION = '1.0.1-dev';

    /**
     * AcquiaCli constructor.
     * @param Config               $config
     * @param InputInterface|null  $input
     * @param OutputInterface|null $output
     */
    public function __construct(Config $config, InputInterface $input = null, OutputInterface $output = null)
    {

        // Create application.
        $this->setConfig($config);
        $application = new Application(self::NAME, self::VERSION);

        $application->getDefinition()->addOption(
            new InputOption(
                '--no-wait',
                null,
                InputOption::VALUE_NONE,
                'Run commands without waiting for tasks to complete (risky).'
            )
        );

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

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int
     */
    public function run(InputInterface $input, OutputInterface $output)
    {
        $statusCode = $this->runner->run($input, $output);

        return $statusCode;
    }
}
