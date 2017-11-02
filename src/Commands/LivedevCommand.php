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
        if (!preg_match(self::UUIDv4, $uuid)) {
            $uuid = $this->getUuidFromHostingName($uuid);
        }

        $this->say("Enabling livedev for ${environment} environment");
        $id = $this->getIdFromEnvironmentName($uuid, $environment);

        $this->cloudapi->enableLiveDev($id);
        $this->waitForTask($uuid, 'LiveDevEnabled');
    }

    /**
     * Disable livedev for an environment.
     *
     * @param string $uuid
     * @param string $environment
     *
     * @command livedev:disable
     */
    public function acquiaRemoveDomain($uuid, $environment)
    {
        if (!preg_match(self::UUIDv4, $uuid)) {
            $uuid = $this->getUuidFromHostingName($uuid);
        }

        if ($this->confirm('Are you sure you want to disable livedev? Uncommitted work will be lost.')) {
            $this->say("Disabling livedev for the environment ${environment}");
            $id = $this->getIdFromEnvironmentName($uuid, $environment);
            $this->cloudapi->disableLiveDev($id);
            $this->waitForTask($uuid, 'LiveDevDisabled');
        }
    }
}
