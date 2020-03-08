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
use AcquiaCloudApi\Connector\Client;

/**
 * Class AcquiaCli
 * @package AcquiaCli
 */
class AcquiaCli
{

    use ConfigAwareTrait;

    private $runner;

    const NAME = 'AcquiaCli';

    /**
     * AcquiaCli constructor.
     * @param Config               $config
     * @param InputInterface|null  $input
     * @param OutputInterface|null $output
     */
    public function __construct(
        Config $config,
        Client $client,
        InputInterface $input = null,
        OutputInterface $output = null
    ) {
        if ($file = file_get_contents(dirname(__DIR__) . '/VERSION')) {
            $version = trim($file);
        } else {
            throw new \Exception('No VERSION file');
        }

        // Create application.
        $this->setConfig($config);

        $application = new Application(self::NAME, $version);

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
            new InputOption(
                '--limit',
                '-l',
                InputOption::VALUE_REQUIRED,
                'The maximum number of items to return.'
            ),
            new InputOption(
                '--filter',
                '-f',
                InputOption::VALUE_REQUIRED,
                'The filters query string parameter restricts the data
returned from your request. Filtered queries restrict the rows that do
(or do not) get included in the result by testing each row in the result
against the filters. Not all fields are filterable.'
            ),
            new InputOption(
                '--sort',
                '-s',
                InputOption::VALUE_REQUIRED,
                'A comma-delimited string with fields used for sorting.
The order of the fields is significant. A leading - in the field indicates
the field should be sorted in a descending order. Not all fields are sortable.'
            ),
        ]);

        // Create and configure container.
        $container = $this->getContainer($input, $output, $application, $config, $client);

        $this->injectParameters($container);

        $discovery = new CommandFileDiscovery();
        $discovery->setSearchPattern('*Command.php');
        $commandClasses = $discovery->discover(__DIR__ . '/Commands', '\AcquiaCli\Commands');

        // Instantiate Robo Runner.
        $this->runner = new RoboRunner();
        $this->runner->setContainer($container);
        $this->runner->registerCommandClasses($application, $commandClasses);
    }

    public function getContainer($input, $output, $application, $config, $client)
    {
        $container = Robo::createDefaultContainer($input, $output, $application, $config);
        $container->add('client', $client);

        $container->add('cloudApi', \AcquiaCli\CloudApi::class)
            ->withArgument('config')
            ->withArgument('client');

        return $container;
    }

    public function injectParameters($container)
    {
        $parameterInjection = $container->get('parameterInjection');
        $parameterInjection->register('AcquiaCli\CloudApi', new \AcquiaCli\Injector\AcquiaCliInjector);
        $parameterInjection->register('AcquiaCloudApi\Connector\Client', new \AcquiaCli\Injector\AcquiaCliInjector);
        $parameterInjection->register(
            'AcquiaCloudApi\Endpoints\Applications',
            new \AcquiaCli\Injector\AcquiaCliInjector
        );
        $parameterInjection->register(
            'AcquiaCloudApi\Endpoints\Environments',
            new \AcquiaCli\Injector\AcquiaCliInjector
        );
        $parameterInjection->register('AcquiaCloudApi\Endpoints\Databases', new \AcquiaCli\Injector\AcquiaCliInjector);
        $parameterInjection->register('AcquiaCloudApi\Endpoints\Servers', new \AcquiaCli\Injector\AcquiaCliInjector);
        $parameterInjection->register('AcquiaCloudApi\Endpoints\Domains', new \AcquiaCli\Injector\AcquiaCliInjector);
        $parameterInjection->register('AcquiaCloudApi\Endpoints\Code', new \AcquiaCli\Injector\AcquiaCliInjector);
        $parameterInjection->register(
            'AcquiaCloudApi\Endpoints\DatabaseBackups',
            new \AcquiaCli\Injector\AcquiaCliInjector
        );
        $parameterInjection->register('AcquiaCloudApi\Endpoints\Crons', new \AcquiaCli\Injector\AcquiaCliInjector);
        $parameterInjection->register('AcquiaCloudApi\Endpoints\Account', new \AcquiaCli\Injector\AcquiaCliInjector);
        $parameterInjection->register('AcquiaCloudApi\Endpoints\Roles', new \AcquiaCli\Injector\AcquiaCliInjector);
        $parameterInjection->register(
            'AcquiaCloudApi\Endpoints\Permissions',
            new \AcquiaCli\Injector\AcquiaCliInjector
        );
        $parameterInjection->register('AcquiaCloudApi\Endpoints\Teams', new \AcquiaCli\Injector\AcquiaCliInjector);
        $parameterInjection->register('AcquiaCloudApi\Endpoints\Variables', new \AcquiaCli\Injector\AcquiaCliInjector);
        $parameterInjection->register('AcquiaCloudApi\Endpoints\Logs', new \AcquiaCli\Injector\AcquiaCliInjector);
        $parameterInjection->register(
            'AcquiaCloudApi\Endpoints\Notifications',
            new \AcquiaCli\Injector\AcquiaCliInjector
        );
        $parameterInjection->register('AcquiaCloudApi\Endpoints\Insights', new \AcquiaCli\Injector\AcquiaCliInjector);
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
