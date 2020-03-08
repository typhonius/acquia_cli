<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Endpoints\Environments;
use AcquiaCloudApi\Endpoints\Servers;
use AcquiaCloudApi\Response\EnvironmentResponse;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

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
    public function environmentList(Environments $environmentsAdapter, OutputInterface $output, $uuid)
    {

        $environments = $environmentsAdapter->getAll($uuid);

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
    public function environmentInfo(Environments $environmentsAdapter, Servers $serversAdapter, $uuid, $env = null)
    {

        if (null !== $env) {
            $this->cloudapi->addQuery('filter', "name=${env}");
        }

        $environments = $environmentsAdapter->getAll($uuid);

        $this->cloudapi->clearQuery();

        foreach ($environments as $e) {
            $this->renderEnvironmentInfo($e, $serversAdapter);
        }

        $this->say("Web servers not marked 'Active' are out of rotation.");
        $this->say("Load balancer servers not marked 'Active' are hot spares");
        $this->say("Database servers not marked 'Primary' are the passive master");
    }

    /**
     * @param EnvironmentResponse $environment
     */
    protected function renderEnvironmentInfo(EnvironmentResponse $environment, Servers $serversAdapter)
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

            $servers = $serversAdapter->getAll($environment->uuid);

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

        if (!isset($environment->configuration->php)) {
            $environment->configuration = new \stdClass();
            $environment->configuration->php = new \stdClass();
            $environment->configuration->php->version = ' ';
            $environment->configuration->php->memory_limit = ' ';
            $environment->configuration->php->opcache = ' ';
            $environment->configuration->php->apcu = ' ';
            $environment->configuration->php->sendmail_path = ' ';
        }
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
    public function environmentRename(Environments $environmentsAdapter, $uuid, $environment, $name)
    {
        $environment = $this->cloudapiService->getEnvironment($uuid, $environment);
        $this->say(sprintf('Renaming %s to %s', $environment->label, $name));
        $environments = $environmentsAdapter->rename($environment->uuid, $name);
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
    public function environmentDelete(Environments $environmentsAdapter, $uuid, $environment)
    {
        $environment = $this->cloudapiService->getEnvironment($uuid, $environment);
        if ($this->confirm("Are you sure you want to delete this environment?")) {
            $this->say(sprintf('Deleting %s environment', $environment->label));
            $response = $environmentsAdapter->delete($environment->uuid);
            $this->waitForNotification($response);
        }
    }
}
