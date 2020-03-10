<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Response\EnvironmentResponse;
use AcquiaCloudApi\Endpoints\Environments;

/**
 * Class FilesCommand
 *
 * @package AcquiaCli\Commands
 */
class FilesCommand extends AcquiaCommand
{
    /**
     * Copies files from one environment to another.
     *
     * @param string $uuid
     * @param string $environmentFrom
     * @param string $environmentTo
     *
     * @command files:copy
     * @aliases f:c
     */
    public function filesCopy($uuid, $environmentFrom, $environmentTo)
    {
        $environmentFrom = $this->cloudapiService->getEnvironment($uuid, $environmentFrom);
        $environmentTo = $this->cloudapiService->getEnvironment($uuid, $environmentTo);

        if ($this->confirm(
            sprintf(
                'Are you sure you want to copy files from %s to %s?',
                $environmentFrom->label,
                $environmentTo->label
            )
        )
        ) {
            $this->copyFiles($uuid, $environmentFrom, $environmentTo);
        }
    }
}
