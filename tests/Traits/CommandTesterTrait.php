<?php

namespace AcquiaCli\Tests\Traits;

use Consolidation\AnnotatedCommand\CommandFileDiscovery;
use Robo\Robo;
use Robo\Runner;
use Symfony\Component\Console\Tester\CommandTester;
use AcquiaCli\Cli\Config;
use AcquiaCli\Cli\AcquiaCli;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;
use AcquiaCloudApi\Connector\Client;
use AcquiaCli\Injector\AcquiaCliInjector;

trait CommandTesterTrait
{
    /** @var string */
    protected $appName;

    /** @var string */
    protected $appVersion;

    /** @var string|array|null */
    protected $commandClasses = null;

    /** @var Runner */
    protected $runner;

    /**
     * Setup the tester.
     *
     * @param string|array|null $commandClasses
     */
    public function setupCommandTester($commandClasses = null)
    {
        // Define our invariants for our test.
        $this->runner = new Runner();
        if (!is_null($commandClasses)) {
            $this->commandClasses = $commandClasses;
        }
    }

    /**
     * @param string $commandString
     * @param array $inputs
     * @param array $command_extra
     * @param string|array|null $commandClasses
     * @return array
     */
    protected function executeCommand($commandString, $inputs = [], $command_extra = [], $commandClasses = null)
    {
        $commandClasses = $commandClasses ?? $this->commandClasses;

        $app = $this->getAppForTesting($this->appName, $this->appVersion, $commandClasses);
        $command = $app->get($commandString);
        $tester = new CommandTester($command);
        $tester->setInputs($inputs);
        $status_code = $tester->execute(array_merge(['command' => $commandString], $command_extra));
        Robo::unsetContainer();
        return [trim($tester->getDisplay()), $status_code];
    }

    public function getAppForTesting($appName = null, $appVersion = null, $commandFile = null, $config = null, $classLoader = null)
    {
        // Create an instance of the application and use some default parameters.
        $root = dirname(dirname(dirname(__DIR__)));
        $config = new Config($root);
        
        $input = new ArgvInput();
        $output = new BufferedOutput();
        $acquiaCli = new AcquiaCli($config, $this->getMockClient(), $input, $output);
        
        // Override the LogstreamManager with a mock in the container.
        $container = Robo::getContainer();
        $container->add('logstream', $this->logstream);
        $parameterInjection = $container->get('parameterInjection');
        $parameterInjection->register('AcquiaLogstream\LogstreamManager', new AcquiaCliInjector());
        Robo::setContainer($container);

        $app = $container->get('application');

        if (!is_null($commandFile) && (is_array($commandFile) || is_string($commandFile))) {
            if (is_string($commandFile)) {
                $commandFile = [$commandFile];
            }
            $this->registerCommandClasses($app, $commandFile);
        }
        return $app;
    }

    public function registerCommandClasses($app, $commandClasses)
    {
        foreach ((array)$commandClasses as $commandClass) {
            $this->registerCommandClass($app, $commandClass);
        }
    }

    public function registerCommandClass($app, $commandClass)
    {
        $container = Robo::getContainer();
        $roboCommandFileInstance = $this->instantiateCommandClass($commandClass);
        if (!$roboCommandFileInstance) {
            return;
        }

        // Register commands for all of the public methods in the RoboFile.
        $commandFactory = $container->get('commandFactory');
        $commandList = $commandFactory->createCommandsFromClass($roboCommandFileInstance);
        foreach ($commandList as $command) {
            $app->add($command);
        }
        return $roboCommandFileInstance;
    }

    protected function instantiateCommandClass($commandClass)
    {
        $container = Robo::getContainer();

        // Register the RoboFile with the container and then immediately
        // fetch it; this ensures that all of the inflectors will run.
        // If the command class is already an instantiated object, then
        // just use it exactly as it was provided to us.

        if (is_string($commandClass)) {
            if (!class_exists($commandClass)) {
                return;
            }
            $reflectionClass = new \ReflectionClass($commandClass);
            if ($reflectionClass->isAbstract()) {
                return;
            }

            $container->share($commandClass, $commandClass);
            $commandClass = $container->get($commandClass);
        }

        return $commandClass;
    }

    protected function getMockClient()
    {
        $connector = $this
            ->getMockBuilder('AcquiaCloudApi\Connector\Connector')
            ->disableOriginalConstructor()
            ->setMethods(['sendRequest'])
            ->getMock();

        $connector
            ->expects($this->any())
            ->method('sendRequest')
            ->will($this->returnCallback(array($this, 'sendRequestCallback')));

        return Client::factory($connector);
    }

    protected function getMockLogstream()
    {
        $logstream = $this
            ->getMockBuilder('AcquiaLogstream\LogstreamManager')
            ->disableOriginalConstructor()
            ->setMethods(['stream'])
            ->getMock();

        $logstream
            ->expects($this->any())
            ->method('stream')
            ->willReturn('');

        return $logstream;
    }
}