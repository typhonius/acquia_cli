<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Response\EnvironmentResponse;
use Psr\Http\Message\StreamInterface;

/**
 * Class FilesCommand
 * @package AcquiaCli\Commands
 */
class FilesCommand extends AcquiaCommand
{
    /**
     * Backs up all DBs in an environment.
     *
     * @param string              $uuid
     * @param EnvironmentResponse $environmentFrom
     * @param EnvironmentResponse $environmentTo
     *
     * @command files:copy
     */
    public function acquiaFilesCopy($uuid, EnvironmentResponse $environmentFrom, EnvironmentResponse $environmentTo)
    {
        $this->backupFiles($uuid, $environmentFrom, $environmentTo);
    }
}
