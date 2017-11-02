<?php

namespace AcquiaCli\Commands;

/**
 * Class FilesCommand
 * @package AcquiaCli\Commands
 */
class FilesCommand extends AcquiaCommand
{
    /**
     * Backs up all DBs in an environment.
     *
     * @param string $uuid
     * @param string $environmentFrom
     * @param string $environmentTo
     *
     * @command files:copy
     */
    public function acquiaFilesCopy($uuid, $environmentFrom, $environmentTo)
    {
        if (!preg_match(self::UUIDv4, $uuid)) {
            $uuid = $this->getUuidFromHostingName($uuid);
        }

        $idFrom = $this->getIdFromEnvironmentName($uuid, $environmentFrom);
        $idTo = $this->getIdFromEnvironmentName($uuid, $environmentTo);

        $this->backupFiles($uuid, $idFrom, $idTo);
    }
}
