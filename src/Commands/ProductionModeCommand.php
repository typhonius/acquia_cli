<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Response\EnvironmentResponse;
use AcquiaCloudApi\Endpoints\Environments;

/**
 * Class ProductionModeCommand
 * @package AcquiaCli\Commands
 */
class ProductionModeCommand extends AcquiaCommand
{

    /**
     * Enable production mode for an environment.
     *
     * @param string              $uuid
     * @param EnvironmentResponse $environment
     * @throws \Exception
     *
     * @command productionmode:enable
     * @alias pm:enable
     */
    public function productionModeEnable($uuid, EnvironmentResponse $environment)
    {
        if ('prod' !== $environment->name) {
            throw new \Exception('Production mode may only be enabled/disabled on the prod environment.');
        }
        $this->say(sprintf('Enabling production mode for %s environment', $environment->label));
        $environmentAdapter = new Environments($this->cloudapi);
        $environmentAdapter->enableProductionMode($environment->uuid);
    }

    /**
     * Disable production mode for an environment.
     *
     * @param string              $uuid
     * @param EnvironmentResponse $environment
     * @throws \Exception
     *
     * @command productionmode:disable
     * @alias pm:disable
     */
    public function productionModeDisable($uuid, EnvironmentResponse $environment)
    {
        if ('prod' !== $environment->name) {
            throw new \Exception('Production mode may only be enabled/disabled on the prod environment.');
        }

        if ($this->confirm('Are you sure you want to disable production mode?')) {
            $this->say(sprintf('Disabling production mode for %s environment', $environment->label));
            $environmentAdapter = new Environments($this->cloudapi);
            $environmentAdapter->disableProductionMode($environment->uuid);
        }
    }
}
