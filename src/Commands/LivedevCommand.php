<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Response\EnvironmentResponse;
use AcquiaCloudApi\Endpoints\Environments;

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
        $this->say(sprintf('Enabling livedev for %s environment', $environment->label));
        $environmentAdapter = new Environments($this->cloudapi);
        $environmentAdapter->enableLiveDev($environment->uuid);
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
            $this->say(sprintf('Disabling livedev for %s environment', $environment->label));
            $environmentAdapter = new Environments($this->cloudapi);
            $environmentAdapter->disableLiveDev($environment->uuid);
        }
    }
}
