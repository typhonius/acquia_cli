<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Response\EnvironmentResponse;

/**
 * Class LivedevCommand
 * @package AcquiaCli\Commands
 */
class LivedevCommand extends AcquiaCommand
{

    /**
     * Enable livedev for an environment.
     *
     * @param string              $uuid
     * @param EnvironmentResponse $environment
     *
     * @command livedev:enable
     */
    public function acquiaLivedevEnable($uuid, EnvironmentResponse $environment)
    {
        $label = $environment->label;
        $this->say("Enabling livedev for ${label} environment");
        $this->cloudapi->enableLiveDev($environment->uuid);
        $this->waitForTask($uuid, 'LiveDevEnabled');
    }

    /**
     * Disable livedev for an environment.
     *
     * @param string              $uuid
     * @param EnvironmentResponse $environment
     *
     * @command livedev:disable
     */
    public function acquiaLivedevDisable($uuid, EnvironmentResponse $environment)
    {
        if ($this->confirm('Are you sure you want to disable livedev? Uncommitted work will be lost.')) {
            $label = $environment->label;
            $this->say("Disabling livedev for ${label} environment");
            $this->cloudapi->disableLiveDev($environment->uuid);
            $this->waitForTask($uuid, 'LiveDevDisabled');
        }
    }
}
