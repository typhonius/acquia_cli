<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Connector\Connector;
use AcquiaCloudApi\Response\EnvironmentResponse;
use AcquiaCloudApi\Endpoints\Databases;
use AcquiaCloudApi\Endpoints\DatabaseBackups;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Class DbCommand
 *
 * @package AcquiaCli\Commands
 */
class DbCommand extends AcquiaCommand
{

    /**
     * Shows all databases.
     *
     * @param string $uuid
     *
     * @command database:list
     * @aliases db:list
     */
    public function dbList(Databases $databaseAdapter, $uuid)
    {
        $databases = $databaseAdapter->getAll($uuid);
        $table = new Table($this->output());
        $table->setHeaders(['Databases']);
        foreach ($databases as $database) {
            $table
                ->addRows(
                    [
                    [
                        $database->name,
                    ]
                    ]
                );
        }
        $table->render();
    }

    /**
     * Creates a database.
     *
     * @param string $uuid
     * @param string $dbName
     *
     * @command database:create
     * @aliases database:add,db:create,db:add
     */
    public function dbCreate(Databases $databaseAdapter, $uuid, $dbName)
    {
        $response = $databaseAdapter->create($uuid, $dbName);
        $this->say(sprintf('Creating database (%s)', $dbName));
        $this->waitForNotification($response);
    }

    /**
     * Deletes a database.
     *
     * @param string $uuid
     * @param string $dbName
     *
     * @command database:delete
     * @aliases database:remove,db:remove,db:delete
     */
    public function dbDelete(Databases $databaseAdapter, $uuid, $dbName)
    {
        if ($this->confirm('Are you sure you want to delete this database?')) {
            $this->say(sprintf('Deleting database (%s)', $dbName));
            $response = $databaseAdapter->delete($uuid, $dbName);
            $this->waitForNotification($response);
        }
    }

    /**
     * Truncates a database (only applicable to Acquia free tier).
     *
     * @param string $uuid
     * @param string $dbName
     *
     * @command database:truncate
     * @aliases db:truncate
     */
    public function dbTruncate(Databases $databaseAdapter, $uuid, $dbName)
    {
        if ($this->confirm('Are you sure you want to truncate this database?')) {
            $this->say(sprintf('Truncate database (%s)', $dbName));
            $response = $databaseAdapter->truncate($uuid, $dbName);
            $this->waitForNotification($response);
        }
    }

    /**
     * Copies a database from one environment to another.
     *
     * @param string $uuid
     * @param string $environmentFrom
     * @param string $environmentTo
     * @param string $dbName
     *
     * @command database:copy
     * @aliases db:copy
     */
    public function dbCopy($uuid, $environmentFrom, $environmentTo, $dbName)
    {
        $environmentFrom = $this->cloudapiService->getEnvironment($uuid, $environmentFrom);
        $environmentTo = $this->cloudapiService->getEnvironment($uuid, $environmentTo);

        if (
            $this->confirm(
                sprintf(
                    'Are you sure you want to copy database %s from %s to %s?',
                    $dbName,
                    $environmentFrom->label,
                    $environmentTo->label
                )
            )
        ) {
            $this->backupAndMoveDbs($uuid, $environmentFrom, $environmentTo, $dbName);
        }
    }

    /**
     * Copies all DBs from one environment to another environment.
     *
     * @param string $uuid
     * @param string $environmentFrom
     * @param string $environmentTo
     *
     * @command database:copy:all
     * @aliases db:copy:all
     */
    public function dbCopyAll($uuid, $environmentFrom, $environmentTo)
    {
        $environmentFrom = $this->cloudapiService->getEnvironment($uuid, $environmentFrom);
        $environmentTo = $this->cloudapiService->getEnvironment($uuid, $environmentTo);

        if (
            $this->confirm(
                sprintf(
                    'Are you sure you want to copy all databases from %s to %s?',
                    $environmentFrom->label,
                    $environmentTo->label
                )
            )
        ) {
            $this->backupAndMoveDbs($uuid, $environmentFrom, $environmentTo);
        }
    }
}
