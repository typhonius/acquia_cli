<?php

namespace AcquiaCli\Commands;

use Psr\Http\Message\StreamInterface;

/**
 * Class LivedevCommand
 * @package AcquiaCli\Commands
 */
class LivedevCommand extends AcquiaCommand
{

    /**
     * Enable livedev for an environment.
     *
     * @param string          $uuid
     * @param StreamInterface $environment
     *
     * @command livedev:enable
     */
    public function acquiaLivedevEnable($uuid, $environment)
    {
        $label = $environment->label;
        $this->say("Enabling livedev for ${label} environment");
        $this->cloudapi->enableLiveDev($environment->id);
        $this->waitForTask($uuid, 'LiveDevEnabled');
    }

    /**
     * Disable livedev for an environment.
     *
     * @param string          $uuid
     * @param StreamInterface $environment
     *
     * @command livedev:disable
     */
    public function acquiaRemoveDomain($uuid, $environment)
    {
        if ($this->confirm('Are you sure you want to disable livedev? Uncommitted work will be lost.')) {
            $label = $environment->label;
            $this->say("Disabling livedev for ${label} environment");
            $this->cloudapi->disableLiveDev($environment->id);
            $this->waitForTask($uuid, 'LiveDevDisabled');
        }
    }
}
