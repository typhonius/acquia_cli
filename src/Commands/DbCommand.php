<?php

namespace AcquiaCli\Commands;

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
     * @param string          $uuid
     * @param StreamInterface $environment
     *
     * @command db:backup
     */
    public function acquiaBackupDb($uuid, $environment)
    {
        $this->backupAllEnvironmentDbs($uuid, $environment);
    }

    /**
     * Shows a list of database backups for all databases in an environment.
     *
     * @param string $uuid
     * @param string $environment
     *
     * @command db:backup:list
     */
    public function acquiaDbBackupList($uuid, $environment)
    {

        $databases = $this->cloudapi->environmentDatabases($environment->id);

        $table = new Table($this->output());
        $table->setHeaders(array('ID', 'Type', 'Timestamp'));

        foreach ($databases as $database) {
            $dbName = $database->name;
            $this->yell($dbName);
            $backups = $this->cloudapi->databaseBackups($environment->id, $dbName);
            foreach ($backups as $backup) {
                $table
                    ->addRows(array(
                        array($backup->id, ucfirst($backup->type), $backup->completed_at),
                    ));
            }
        }
        $table->render();
    }

    /**
     * Provides a database backup link.
     *
     * @param string $uuid
     * @param string $environment
     * @param int    $backupId
     *
     * @command db:backup:link
     */
    public function acquiaDbBackupLink($uuid, $environment, $backupId)
    {
        $id = $environment->id;
        $this->say($this->cloudapi::BASE_URI . "/environments/${id}/database-backups/${backupId}/actions/download");
    }
}
