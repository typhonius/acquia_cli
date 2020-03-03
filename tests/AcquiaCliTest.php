<?php

namespace AcquiaCli\Tests;

use Robo\Robo;
use Robo\Config\Config;
use Robo\Common\ConfigAwareTrait;
use Robo\Runner as RoboRunner;
use Robo\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Consolidation\AnnotatedCommand\CommandFileDiscovery;
use AcquiaCloudApi\Connector\Client;

/**
 * Class AcquiaCli
 * @package AcquiaCli
 */
class AcquiaCliTest
{

    use ConfigAwareTrait;

    private $runner;

    const NAME = 'AcquiaCli TEST';

    const VERSION = '2.0.0-dev';

    /**
     * AcquiaCliTest constructor.
     * @param InputInterface|null  $input
     * @param OutputInterface|null $output
     */
    public function __construct(InputInterface $input = null, OutputInterface $output = null, $client)
    {
        $application = new Application(self::NAME, self::VERSION);
        $application->getDefinition()->addOptions([
            new InputOption(
                '--no-wait',
                null,
                InputOption::VALUE_NONE,
                'Run commands without waiting for tasks to complete (risky).'
            ),
            new InputOption(
                '--yes',
                '-y',
                InputOption::VALUE_NONE,
                'Automatically respond "yes" to all confirmation questions.'
            )
        ]);

        // Create and configure container.
        $container = Robo::createDefaultContainer($input, $output, $application);
        $container->add('client', $client);

        $container->add('cloudApi', \AcquiaCli\Tests\CloudApiTest::class)
            ->withArgument('config')
            ->withArgument('client');

        $discovery = new CommandFileDiscovery();
        $discovery->setSearchPattern('*Command.php');
        $commandClasses = $discovery->discover(dirname(__DIR__) . '/src/Commands', '\AcquiaCli\Commands');

        // Instantiate Robo Runner.
        $this->runner = new RoboRunner();
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
        return $this->runner->run($input, $output);
    }
}
