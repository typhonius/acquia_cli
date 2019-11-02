<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Response\EnvironmentResponse;
use Symfony\Component\Console\Helper\Table;

/**
 * Class EnvironmentsCommand
 * @package AcquiaCli\Commands
 */
class EnvironmentsCommand extends AcquiaCommand
{

    /**
     * Shows detailed information about servers in an environment.
     *
     * @param string      $uuid
     * @param string|null $env
     *
     * @command environment:info
     * @alias env:info
     * @alias e:i
     */
    public function acquiaEnvironmentInfo($uuid, $env = null)
    {

        if (null !== $env) {
            $this->cloudapi->addQuery('filter', "name=${env}");
        }

        $environments = $this->cloudapi->environments($uuid);

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

        $environmentName = $environment->label;
        $environmentId = $environment->uuid;

        $this->yell("${environmentName} environment");
        $this->say("Environment ID: ${environmentId}");
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
            $servers = $this->cloudapi->servers($environment->uuid);

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
     * @alias env:rename
     */
    public function acquiaEnvironmentRename($uuid, $environment, $name)
    {
        $this->say('Renaming ' . $environment->label . " to ${name}");
        $this->cloudapi->renameEnvironment($environment->uuid, $name);
    }
}
