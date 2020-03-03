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
 * Class DomainCommand
 * @package AcquiaCli\Commands
 */
class DbCommand extends AcquiaCommand
{

    protected $databaseAdapter;

    public function __construct()
    {
        parent::__construct();

        $this->databaseAdapter = new Databases($this->getCloudApi());
    }

    /**
     * Shows all databases.
     *
     * @param string              $uuid
     *
     * @command database:list
     * @aliases db:list
     */
    public function dbList($uuid)
    {
        $databases = $this->databaseAdapter->getAll($uuid);
        $table = new Table($this->output());
        $table->setHeaders(['Databases']);
        foreach ($databases as $database) {
            echo $database->name;
            $table
                ->addRows([
                    [
                        $database->name,
                    ]
                ]);
        }
        $table->render();
    }

    /**
     * Creates a database.
     *
     * @param string              $uuid
     * @param string              $dbName
     *
     * @command database:create
     * @aliases database:add,db:create,db:add
     */
    public function dbCreate($uuid, $dbName)
    {
        $response = $this->databaseAdapter->create($uuid, $dbName);
        $this->waitForNotification($response);
    }

    /**
     * Deletes a database.
     *
     * @param string              $uuid
     * @param string              $dbName
     *
     * @command database:delete
     * @aliases database:remove,db:remove,db:delete
     */
    public function dbDelete($uuid, $dbName)
    {
        if ($this->confirm('Are you sure you want to delete this database?')) {
            $this->say(sprintf('Deleting database (%s)', $dbName));
            $response = $this->databaseAdapter->delete($uuid, $dbName);
            $this->waitForNotification($response);
        }
    }

    /**
     * Truncaates a database.
     *
     * @param string              $uuid
     * @param string              $dbName
     *
     * @command database:truncate
     * @aliases db:truncate
     */
    public function dbTruncate($uuid, $dbName)
    {
        if ($this->confirm('Are you sure you want to truncate this database?')) {
            $this->say(sprintf('Truncate database (%s)', $dbName));
            $response = $this->databaseAdapter->truncate($uuid, $dbName);
            $this->waitForNotification($response);
        }
    }

    /**
     * Copies a database from one environment to another.
     *
     * @param string              $uuid
     * @param EnvironmentResponse $environmentFrom
     * @param EnvironmentResponse $environmentTo
     * @param string              $dbName
     *
     * @command database:copy
     * @aliases db:copy
     */
    public function dbCopy($uuid, $environmentFrom, $environmentTo, $dbName)
    {
        if ($this->confirm(
            sprintf(
                'Are you sure you want to copy database %s from %s to %s?',
                $dbName,
                $environmentFrom->label,
                $environmentTo->label
            )
        )) {
            $response = $this->databaseAdapter->copy($environmentFrom->uuid, $dbName, $environmentTo->uuid);
            $this->waitForNotification($response);
        }
    }

    /**
     * Copies all DBs from one environment to another environment.
     *
     * @param string              $uuid
     * @param EnvironmentResponse $environmentFrom
     * @param EnvironmentResponse $environmentTo
     *
     * @command database:copy:all
     * @aliases db:copy:all
     */
    public function dbCopyAll($uuid, EnvironmentResponse $environmentFrom, EnvironmentResponse $environmentTo)
    {
        $this->backupAndMoveDbs($uuid, $environmentFrom, $environmentTo);
    }
}
