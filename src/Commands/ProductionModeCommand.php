<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Response\EnvironmentResponse;

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
     *
     * @command productionmode:enable
     * @alias pm:enable
     */
    public function acquiaProductionModeEnable($uuid, EnvironmentResponse $environment)
    {
        // @TODO fail if not prod environment?
        $label = $environment->label;
        $this->say("Enabling production mode for ${label} environment");
        $this->cloudapi->enableProductionMode($environment->uuid);
        $this->waitForTask($uuid, 'ProductionModeEnabled');
    }

    /**
     * Disable production mode for an environment.
     *
     * @param string              $uuid
     * @param EnvironmentResponse $environment
     *
     * @command productionmode:disable
     * @alias pm:disable
     */
    public function acquiaRemoveDomain($uuid, EnvironmentResponse $environment)
    {
        // @TODO fail if not prod environment?

        if ($this->confirm('Are you sure you want to disable production mode?')) {
            $label = $environment->label;
            $this->say("Disabling production mode for ${label} environment");
            $this->cloudapi->disableProductionMode($environment->uuid);
            $this->waitForTask($uuid, 'ProductionModeDisabled');
        }
    }
}
