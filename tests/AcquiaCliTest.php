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
use Consolidation\AnnotatedCommand\CommandData;
use AcquiaCli\AcquiaCli;
use Consolidation\Config\Loader\ConfigProcessor;
use Consolidation\Config\Loader\YamlConfigLoader;

/**
 * Class AcquiaCli
 * @package AcquiaCli
 */
class AcquiaCliTest extends AcquiaCli
{

    use ConfigAwareTrait;

    private $runner;

    const NAME = 'AcquiaCli TEST';

    public function getContainer($input, $output, $application, $config, $client)
    {
        $container = Robo::createDefaultContainer($input, $output, $application, $config);
        $container->add('client', $client);

        $container->add('cloudApi', \AcquiaCli\Tests\CloudApiTest::class)
            ->withArgument('config')
            ->withArgument('client');

        return $container;
    }
}
