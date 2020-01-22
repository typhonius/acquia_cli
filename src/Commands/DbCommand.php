<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\CloudApi\Connector;
use AcquiaCloudApi\Response\EnvironmentResponse;
use AcquiaCloudApi\Endpoints\Databases;
use AcquiaCloudApi\Endpoints\DatabaseBackups;
use Symfony\Component\Console\Helper\Table;

/**
 * Class DomainCommand
 * @package AcquiaCli\Commands
 */
class DbCommand extends AcquiaCommand
{
    /**
     * Copies all DBs from one environment to another environment.
     *
     * @param string              $uuid
     * @param EnvironmentResponse $environmentFrom
     * @param EnvironmentResponse $environmentTo
     *
     * @command db:copy:all
     */
    public function acquiaCopyDb($uuid, EnvironmentResponse $environmentFrom, EnvironmentResponse $environmentTo)
    {
        $this->backupAndMoveDbs($uuid, $environmentFrom, $environmentTo);
    }

    /**
     * Backs up all DBs in an environment.
     *
     * @param string              $uuid
     * @param EnvironmentResponse $environment
     *
     * @command db:backup
     */
    public function dbBackup($uuid, $environment)
    {
        $this->backupAllEnvironmentDbs($uuid, $environment);
    }

    /**
     * Shows a list of database backups for all databases in an environment.
     *
     * @param string              $uuid
     * @param EnvironmentResponse $environment
     *
     * @command db:backup:list
     */
    public function dbBackupList($uuid, $environment)
    {
        $dbAdapter = new Databases($this->cloudapi);
        $databases = $dbAdapter->getAll($uuid);

        $table = new Table($this->output());
        $table->setHeaders(['ID', 'Type', 'Timestamp']);

        foreach ($databases as $database) {
            $dbName = $database->name;
            $this->yell($dbName);
            $dbBackupsAdapter = new DatabaseBackups($this->cloudapi);
            $backups = $dbBackupsAdapter->getAll($environment->uuid, $dbName);

            foreach ($backups as $backup) {
                $table
                    ->addRows([
                        [
                            $backup->id,
                            ucfirst($backup->type),
                            $backup->completedAt,
                        ],
                    ]);
            }
        }
        $table->render();
    }

    /**
     * Restores a database from a saved backup.
     *
     * @param string              $uuid
     * @param EnvironmentResponse $environment
     * @param string              $backupId
     *
     * @command db:backup:restore
     */
    public function dbBackupRestore($uuid, $environment, $backupId)
    {
        $environmentName = $environment->label;
        if ($this->confirm("Are you sure you want to restore backup id ${backupId} to ${environmentName}?")) {
            $dbAdapter = new DatabaseBackups($this->cloudapi);
            $response = $dbAdapter->restore($environment->uuid, $backupId);
            $this->waitForNotification($response);
        }
    }

    /**
     * Provides a database backup link.
     *
     * @param string              $uuid
     * @param EnvironmentResponse $environment
     * @param string              $dbName
     * @param int                 $backupId
     *
     * @command db:backup:link
     */
    public function dbBackupLink($uuid, $environment, $dbName, $backupId)
    {
        $environmentUuid = $environment->uuid;
        $this->say(Connector::BASE_URI .
            "/environments/${environmentUuid}/databases/${dbName}/backups/${backupId}/actions/download");
    }

    /**
     * Downloads a database backup.
     *
     * @param string              $uuid
     * @param EnvironmentResponse $environment
     * @param string              $dbName
     * @param int                 $backupId
     *
     * @command db:backup:download
     */
    public function dbBackupDownload($uuid, $environment, $dbName, $backupId, $path = null)
    {

        $dbAdapter = new DatabaseBackups($this->cloudapi);
        $envName = $environment->name;
        $backupName = "${envName}-${dbName}-${backupId}";
        $backup = $dbAdapter->download($environment->uuid, $dbName, $backupId);
        
        if (null === $path) {
            $location = tempnam(sys_get_temp_dir(), $backupName) . '.sql.gz';
        } else {
            $location = $path . $backupName . ".sql.gz";
        }
        if (file_put_contents($location, $backup, LOCK_EX)) {
            $this->say("Database backup downloaded to ${location}");
        } else {
            $this->say('Unable to download database backup.');
        }
    }
}
