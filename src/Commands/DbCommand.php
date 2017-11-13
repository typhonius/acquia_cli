<?php

namespace AcquiaCli\Commands;

use Acquia\Cloud\Api\Response\Database;
use Acquia\Cloud\Api\Response\DatabaseBackup;
use Symfony\Component\Console\Helper\Table;

/**
 * Class DomainCommand
 * @package AcquiaCli\Commands
 */
class DbCommand extends AcquiaCommand
{
    /**
     * Backs up all DBs in an environment.
     *
     * @param string $site
     * @param string $environment
     *
     * @command db:backup
     */
    public function acquiaBackupDb($site, $environment)
    {
        $this->backupAllEnvironmentDbs($site, $environment);
    }

    /**
     * Shows a list of database backups for all databases in an environment.
     *
     * @param string $site
     * @param string $environment
     *
     * @command db:backup:list
     */
    public function acquiaDbBackupList($site, $environment)
    {
        $tz = $this->extraConfig['timezone'];
        $format = $this->extraConfig['format'];
        $table = new Table($this->output());
        $table->setHeaders(array('ID', 'Type', 'Timestamp'));

        $databases = $this->cloudapi->environmentDatabases($site, $environment);
        foreach ($databases as $database) {
            /** @var Database $database */

            $dbName = $database->name();
            $this->yell($dbName);
            $backups = $this->cloudapi->databaseBackups($site, $environment, $dbName);
            foreach ($backups as $backup) {
                /** @var DatabaseBackup $backup */

                $backupDateTime = $backup->completed();
                $backupDateTime->setTimezone(new \DateTimeZone($tz));
                $backupTime = $backupDateTime->format($format);

                $table
                    ->addRows(array(
                        array($backup->id(), ucfirst($backup->type()), $backupTime),
                    ));
            }
        }
        $table->render();
    }

    /**
     * Gets a direct download link to a database backup.
     *
     * @param string $site
     * @param string $environment
     * @param string $database
     * @param int    $id
     *
     * @command db:backup:link
     */
    public function acquiaDbBackupInfo($site, $environment, $database, $id)
    {
        $database = $this->cloudapi->databaseBackup($site, $environment, $database, $id);
        $this->say($database->link());
    }
}
