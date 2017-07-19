<?php

namespace AcquiaCli\Commands;

/**
 * Class LivedevCommand
 * @package AcquiaCli\Commands
 */
class LivedevCommand extends AcquiaCommand
{

    /**
     * Enable livedev for an environment.
     *
     * @param string $site
     * @param string $environment
     *
     * @command livedev:enable
     */
    public function acquiaLivedevEnable($site, $environment = 'dev')
    {
        $this->say("Enabling livedev for the ${environment} environment");
        $task = $this->cloudapi->enableLiveDev($site, $environment);
        $this->waitForTask($site, $task);
    }

    /**
     * Disable livedev for an environment.
     *
     * @param string $site
     * @param string $environment
     *
     * @command livedev:disable
     */
    public function acquiaRemoveDomain($site, $environment = 'dev')
    {
        if ($this->confirm('Are you sure you want to disable livedev? Uncommitted work will be lost.')) {
            $this->say("Disabling livedev for the ${environment} environment");
            $task = $this->cloudapi->disableLiveDev($site, $environment);
            $this->waitForTask($site, $task);
        }
    }
}
