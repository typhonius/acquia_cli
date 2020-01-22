<?php

namespace AcquiaCli;

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

/**
 * Class AcquiaCli
 * @package AcquiaCli
 */
class AcquiaCli
{

    use ConfigAwareTrait;

    private $runner;

    const NAME = 'AcquiaCli';

    const VERSION = '1.0.4-dev';

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

        $application->getDefinition()->addOptions([
            new InputOption(
                '--no-wait',
                null,
                InputOption::VALUE_NONE,
                'Run commands without waiting for tasks to complete (risky).'
            ),
            new InputOption(
                '--realm',
                '-r',
                InputOption::VALUE_REQUIRED,
                'Specify an alternate realm to use for API calls.',
                'prod'
            ),
            new InputOption(
                '--yes',
                '-y',
                InputOption::VALUE_NONE,
                'Automatically respond "yes" to all confirmation questions.'
            ),
        ]);

        // Create and configure container.
        $container = Robo::createDefaultContainer($input, $output, $application, $config);

        $discovery = new CommandFileDiscovery();
        $discovery->setSearchPattern('*Command.php');
        $commandClasses = $discovery->discover(__DIR__ . '/Commands', '\AcquiaCli\Commands');

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
        // Obtain a lock and exit if the command is already running.
        $store = new SemaphoreStore();
        $factory = new Factory($store);
        $lock = $factory->createLock('acquia-cli-command');

        if (!$lock->acquire()) {
            $output->writeln('The command is already running in another process.');

            return 0;
        }

        $statusCode = $this->runner->run($input, $output);

        // Specifically release the lock after successful command invocation.
        $lock->release();

        return $statusCode;
    }
}
