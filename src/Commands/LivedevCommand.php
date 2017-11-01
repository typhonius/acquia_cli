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
     * @param string $uuid
     * @param string $environment
     *
     * @command livedev:enable
     */
    public function acquiaLivedevEnable($uuid, $environment)
    {
        $this->say("Enabling livedev for ${environment} environment");
        $id = $this->getIdFromEnvironmentName($uuid, $environment);

        $this->cloudapi->enableLiveDev($id);
    }

    /**
     * Disable livedev for an environment.
     *
     * @param string $id
     *
     * @command livedev:disable
     */
    public function acquiaRemoveDomain($id)
    {
        if ($this->confirm('Are you sure you want to disable livedev? Uncommitted work will be lost.')) {
            $this->say("Disabling livedev for the environment ${id}");
            $this->cloudapi->disableLiveDev($id);
        }
    }
}
