<?php

namespace AcquiaCli\Cli;

use Robo\Robo;
use Robo\Application;
use Robo\Runner as RoboRunner;
use Robo\Common\ConfigAwareTrait;
use AcquiaCloudApi\Connector\Client;
use AcquiaLogstream\LogstreamManager;
use AcquiaCli\Injector\AcquiaCliInjector;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Output\OutputInterface;
use Consolidation\AnnotatedCommand\CommandFileDiscovery;

/**
 * Class AcquiaCli
 *
 * @package AcquiaCli
 */
class AcquiaCli
{
    use ConfigAwareTrait;
    use LockableTrait;

    private $runner;

    public const NAME = 'AcquiaCli';

    /**
     * AcquiaCli constructor.
     *
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
        if ($file = @file_get_contents(dirname(dirname(__DIR__)) . '/VERSION')) {
            $version = trim($file);
        } else {
            throw new \Exception('No VERSION file');
        }

        // Configure global client options to set user agent.
        $client->addOption('headers', [
            'User-Agent' => sprintf("%s/%s (https://github.com/typhonius/acquia_cli)", self::NAME, $version)
            ]);

        // Create application.
        $this->setConfig($config);

        $application = new Application(self::NAME, $version);


        $application->getDefinition()->addOptions(
            [
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
                '--no-lock',
                null,
                InputOption::VALUE_NONE,
                'Run commands without locking. Allows multiple instances of commands to run concurrently.'
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
            ]
        );

        // Create and configure container.
        $container = $this->getContainer($input, $output, $application, $config, $client);

        $this->injectParameters($container);

        $discovery = new CommandFileDiscovery();
        $discovery->setSearchPattern('*Command.php');
        $commandClasses = $discovery->discover(dirname(__DIR__) . '/Commands', '\AcquiaCli\Commands');

        // Instantiate Robo Runner.
        $this->runner = new RoboRunner();
        $this->runner->setContainer($container);
        $this->runner->registerCommandClasses($application, $commandClasses);
        $this->runner->setSelfUpdateRepository('typhonius/acquia_cli');
    }

    public function getContainer($input, $output, $application, $config, $client)
    {
        $container = Robo::createDefaultContainer($input, $output, $application, $config);
        $container->add('client', $client);

        $container->add('cloudApi', CloudApi::class)
            ->withArgument('config')
            ->withArgument('client');

        $container->add('logstream', LogstreamManager::class)
            ->withArgument('input')
            ->withArgument('output');

        return $container;
    }

    public function injectParameters($container)
    {
        $parameterInjection = $container->get('parameterInjection');
        $parameterInjection->register('AcquiaCli\Cli\CloudApi', new AcquiaCliInjector());
        $parameterInjection->register('AcquiaCli\Cli\Config', new AcquiaCliInjector());
        $parameterInjection->register('AcquiaLogstream\LogstreamManager', new AcquiaCliInjector());
        $parameterInjection->register('AcquiaCloudApi\Connector\Client', new AcquiaCliInjector());
        $parameterInjection->register('AcquiaCloudApi\Endpoints\Account', new AcquiaCliInjector());
        $parameterInjection->register('AcquiaCloudApi\Endpoints\Applications', new AcquiaCliInjector());
        $parameterInjection->register('AcquiaCloudApi\Endpoints\Code', new AcquiaCliInjector());
        $parameterInjection->register('AcquiaCloudApi\Endpoints\Crons', new AcquiaCliInjector());
        $parameterInjection->register('AcquiaCloudApi\Endpoints\DatabaseBackups', new AcquiaCliInjector());
        $parameterInjection->register('AcquiaCloudApi\Endpoints\Databases', new AcquiaCliInjector());
        $parameterInjection->register('AcquiaCloudApi\Endpoints\Domains', new AcquiaCliInjector());
        $parameterInjection->register('AcquiaCloudApi\Endpoints\Environments', new AcquiaCliInjector());
        $parameterInjection->register('AcquiaCloudApi\Endpoints\Ides', new AcquiaCliInjector());
        $parameterInjection->register('AcquiaCloudApi\Endpoints\Insights', new AcquiaCliInjector());
        $parameterInjection->register('AcquiaCloudApi\Endpoints\LogForwardingDestinations', new AcquiaCliInjector());
        $parameterInjection->register('AcquiaCloudApi\Endpoints\Logs', new AcquiaCliInjector());
        $parameterInjection->register('AcquiaCloudApi\Endpoints\Notifications', new AcquiaCliInjector());
        $parameterInjection->register('AcquiaCloudApi\Endpoints\Organizations', new AcquiaCliInjector());
        $parameterInjection->register('AcquiaCloudApi\Endpoints\Permissions', new AcquiaCliInjector());
        $parameterInjection->register('AcquiaCloudApi\Endpoints\Roles', new AcquiaCliInjector());
        $parameterInjection->register('AcquiaCloudApi\Endpoints\Servers', new AcquiaCliInjector());
        $parameterInjection->register('AcquiaCloudApi\Endpoints\SslCertificates', new AcquiaCliInjector());
        $parameterInjection->register('AcquiaCloudApi\Endpoints\Teams', new AcquiaCliInjector());
        $parameterInjection->register('AcquiaCloudApi\Endpoints\Variables', new AcquiaCliInjector());
    }

    /**
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return int
     */
    public function run(InputInterface $input, OutputInterface $output)
    {
        // Obtain a lock and exit if the command is already running.
        if (!$input->hasParameterOption('--no-lock') && !$this->lock('acquia-cli-command')) {
            $output->writeln('The command is already running in another process.');

            return 0;
        }

        $statusCode = $this->runner->run($input, $output);

        // Specifically release the lock after successful command invocation.
        $this->release();

        return $statusCode;
    }
}
