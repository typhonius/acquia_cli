<?php

namespace AcquiaCli\Commands;

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
     * @param string          $uuid
     * @param StreamInterface $environmentFrom
     * @param StreamInterface $environmentTo
     *
     * @command files:copy
     */
    public function acquiaFilesCopy($uuid, $environmentFrom, $environmentTo)
    {
        $this->backupFiles($uuid, $environmentFrom, $environmentTo);
    }
}
