<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Endpoints\Environments;
use AcquiaCloudApi\Endpoints\Domains;
use AcquiaCloudApi\Endpoints\Servers;
use AcquiaCloudApi\Response\EnvironmentResponse;
use Symfony\Component\Console\Helper\Table;

/**
 * Class EnvironmentsCommand
 * @package AcquiaCli\Commands
 */
class EnvironmentsCommand extends AcquiaCommand
{

    /**
     * Shows list of environments in an application.
     *
     * @param string      $uuid
     *
     * @command environment:list
     * @aliases env:list,e:l
     */
    public function environmentList($uuid)
    {

        $environmentAdapter = new Environments($this->cloudapi);
        $environments = $environmentAdapter->getAll($uuid);

        $output = $this->output();

        $table = new Table($output);
        $table->setHeaders([
            'UUID',
            'Name',
            'Label',
            'Domains',
        ]);

        foreach ($environments as $environment) {
            /** @var EnvironmentResponse $environment */
            $table
            ->addRows([
                [
                    $environment->uuid,
                    $environment->name,
                    $environment->label,
                    implode($environment->domains, "\n"),
                ],
            ]);
        }

        $table->render();
    }

    /**
     * Shows detailed information about servers in an environment.
     *
     * @param string      $uuid
     * @param string|null $env
     *
     * @command environment:info
     * @aliases env:info,e:i
     */
    public function environmentInfo($uuid, $env = null)
    {

        if (null !== $env) {
            $this->cloudapi->addQuery('filter', "name=${env}");
        }

        $environmentAdapter = new Environments($this->cloudapi);
        $environments = $environmentAdapter->getAll($uuid);

        $this->cloudapi->clearQuery();

        foreach ($environments as $e) {
            $this->renderEnvironmentInfo($e);
        }

        $this->say("Web servers not marked 'Active' are out of rotation.");
        $this->say("Load balancer servers not marked 'Active' are hot spares");
        $this->say("Database servers not marked 'Primary' are the passive master");
    }

    /**
     * @param EnvironmentResponse $environment
     */
    protected function renderEnvironmentInfo(EnvironmentResponse $environment)
    {

        $this->yell(sprintf('%s environment', $environment->label));
        $this->say(sprintf('Environment ID: %s', $environment->uuid));
        if ($environment->flags->livedev) {
            $this->say('💻  Livedev mode enabled.');
        }
        if ($environment->flags->production_mode) {
            $this->say('🔒  Production mode enabled.');
        }

        $output = $this->output();

        if (!$environment->flags->cde) {
            $serverTable = new Table($output);
            // needs AZ?
            $serverTable->setHeaders([
                'Role(s)',
                'Name',
                'FQDN',
                'AMI',
                'Region',
                'IP',
                'Memcache',
                'Active',
                'Primary',
                'EIP'
            ]);

            $serverAdapter = new Servers($this->cloudapi);
            $servers = $serverAdapter->getAll($environment->uuid);

            foreach ($servers as $server) {
                $memcache = $server->flags->memcache ? '✓' : ' ';
                $active = $server->flags->active_web || $server->flags->active_bal ? '✓' : ' ';
                $primaryDb = $server->flags->primary_db ? '✓' : ' ';
                $eip = $server->flags->elastic_ip ? '✓' : ' ';

                $serverTable
                    ->addRows([
                        [
                            implode(', ', $server->roles),
                            $server->name,
                            $server->hostname,
                            $server->amiType,
                            $server->region,
                            $server->ip,
                            $memcache,
                            $active,
                            $primaryDb,
                            $eip
                        ],
                    ]);
            }
            $serverTable->render();
        }

        $environmentTable = new Table($output);
        $environmentTable->setHeaders([
            'Branch',
            'CDE',
            'PHP Version',
            'Memory Limit',
            'OpCache',
            'APCu',
            'Sendmail Path'
        ]);
        $environmentTable
            ->addRows([
                [
                    $environment->vcs->path,
                    $environment->flags->cde ? $environment->name : ' ',
                    $environment->configuration->php->version,
                    $environment->configuration->php->memory_limit,
                    $environment->configuration->php->opcache,
                    $environment->configuration->php->apcu,
                    $environment->configuration->php->sendmail_path
                ],
            ]);
        $environmentTable->render();
    }

    /**
     * Renames an environment.
     *
     * @param string $uuid
     * @param EnvironmentResponse $environment
     * @param string $name
     *
     * @command environment:rename
     * @alias env:rename,e:rename
     */
    public function environmentRename($uuid, $environment, $name)
    {
        $this->say(sprintf('Renaming %s to %s', $environment->label, $name));
        $environmentAdapter = new Environments($this->cloudapi);
        $environments = $environmentAdapter->rename($environment->uuid, $name);
    }

    /**
     * Deletes an environment.
     *
     * @param string $uuid
     * @param EnvironmentResponse $environment
     *
     * @command environment:delete
     * @alias env:delete,e:d,environment:remove,env:remove
     */
    public function environmentDelete($uuid, $environment)
    {
        if ($this->confirm("Are you sure you want to delete this environment?")) {
            $environmentAdapter = new Environments($this->cloudapi);
            $response = $environmentAdapter->delete($environment->uuid);
            $this->waitForNotification($response);
        }
    }
}
