<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Response\EnvironmentResponse;

/**
 * Class FilesCommand
 * @package AcquiaCli\Commands
 */
class FilesCommand extends AcquiaCommand
{
    /**
     * Copies files from one environment to another.
     *
     * @param string              $uuid
     * @param EnvironmentResponse $environmentFrom
     * @param EnvironmentResponse $environmentTo
     *
     * @command files:copy
     */
    public function filesCopy($uuid, EnvironmentResponse $environmentFrom, EnvironmentResponse $environmentTo)
    {
        $labelFrom = $environmentFrom->label;
        $labelTo = $environmentTo->label;
        if ($this->confirm("Are you sure you want to copy files from ${labelFrom} to ${labelTo}?")) {
            $this->say("Copying files from ${labelFrom} to ${labelTo}");
            $this->copyFiles($uuid, $environmentFrom, $environmentTo);
        }
    }
}
