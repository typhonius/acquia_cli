<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Response\EnvironmentResponse;
use AcquiaCloudApi\Endpoints\Environments;

/**
 * Class ProductionModeCommand
 * @package AcquiaCli\Commands
 */
class ProductionModeCommand extends EnvironmentsCommand
{

    /**
     * Enable production mode for an environment.
     *
     * @param string              $uuid
     * @param EnvironmentResponse $environment
     * @throws \Exception
     *
     * @command productionmode:enable
     * @aliases pm:enable
     */
    public function productionModeEnable(Environments $environmentsAdapter, $uuid, $environment)
    {

        if ('prod' !== $environment) {
            throw new \Exception('Production mode may only be enabled/disabled on the prod environment.');
        }

        $environment = $this->cloudapiService->getEnvironment($uuid, $environment);

        $this->say(sprintf('Enabling production mode for %s environment', $environment->label));
        $environmentsAdapter->enableProductionMode($environment->uuid);
    }

    /**
     * Disable production mode for an environment.
     *
     * @param string              $uuid
     * @param EnvironmentResponse $environment
     * @throws \Exception
     *
     * @command productionmode:disable
     * @aliases pm:disable
     */
    public function productionModeDisable(Environments $environmentsAdapter, $uuid, $environment)
    {

        if ('prod' !== $environment) {
            throw new \Exception('Production mode may only be enabled/disabled on the prod environment.');
        }

        $environment = $this->cloudapiService->getEnvironment($uuid, $environment);

        if ($this->confirm('Are you sure you want to disable production mode?')) {
            $this->say(sprintf('Disabling production mode for %s environment', $environment->label));
            $environmentsAdapter->disableProductionMode($environment->uuid);
        }
    }
}
