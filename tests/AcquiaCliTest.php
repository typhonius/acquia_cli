<?php

namespace AcquiaCli\Tests;

use Robo\Robo;
use Robo\Config\Config;
use Robo\Common\ConfigAwareTrait;
use Symfony\Component\Lock\Factory;
use Symfony\Component\Lock\Store\SemaphoreStore;
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
     * AcquiaCli constructor.
     * @param Config               $config
     * @param InputInterface|null  $input
     * @param OutputInterface|null $output
     */
    public function __construct(InputInterface $input = null, OutputInterface $output = null, $client)
    {
        $application = new Application(self::NAME, self::VERSION);

        // Create and configure container.
        $container = Robo::createDefaultContainer($input, $output, $application);
        // Connector should be a mock instead
        $container->add('client', $client);
        // $container->add('environment', $environment);
        // $container->add('applications', $applications);

        $container->add('cloudApi', \AcquiaCli\Tests\CloudApiTest::class)
            ->withArgument('config')
            ->withArgument('client');
            // ->withArgument('applications');


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
