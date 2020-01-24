<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Response\EnvironmentResponse;
use AcquiaCloudApi\Endpoints\Domains;
use Symfony\Component\Console\Helper\Table;

/**
 * Class DomainCommand
 * @package AcquiaCli\Commands
 */
class DomainCommand extends AcquiaCommand
{

    /**
     * Lists domains.
     *
     * @param string              $uuid
     * @param EnvironmentResponse $environment
     *
     * @command domain:list
     */
    public function domainList($uuid, $environment)
    {
        $domainAdapter = new Domains($this->cloudapi);
        $domains = $domainAdapter->getAll($environment->uuid);

        $output = $this->output();
        $table = new Table($output);
        $table->setHeaders(['Hostname', 'Default', 'Active', 'Uptime']);
        $table->setColumnStyle(1, 'center-align');
        $table->setColumnStyle(2, 'center-align');
        $table->setColumnStyle(3, 'center-align');

        foreach ($domains as $domain) {
            /** @var DomainResponse $domain */
            $table
                ->addRows([
                    [
                        $domain->hostname,
                        $domain->flags->default ? '✓' : '',
                        $domain->flags->active ? '✓' : '',
                        $domain->flags->uptime ? '✓' : '',
                    ],
                ]);
        }

        $table->render();
    }

    /**
     * Gets information about a domain.
     *
     * @param string              $uuid
     * @param EnvironmentResponse $environment
     * @param string              $domain
     *
     * @command domain:info
     */
    public function domainInfo($uuid, $environment, $domain)
    {
        $domainAdapter = new Domains($this->cloudapi);
        $domain = $domainAdapter->status($environment->uuid, $domain);

        $output = $this->output();
        $table = new Table($output);
        $table->setHeaders(['Hostname', 'Active', 'DNS Resolves', 'IP Addresses', 'CNAMES']);
        $table
            ->addRows([
                [
                    $domain->hostname,
                    $domain->flags->active ? '✓' : '',
                    $domain->flags->dns_resolves ? '✓' : '',
                    implode($domain->ip_addresses, "\n"),
                    implode($domain->cnames, "\n"),
                ],
            ]);

        $table->render();
    }

    /**
     * Add a domain to an environment.
     *
     * @param string              $uuid
     * @param EnvironmentResponse $environment
     * @param string              $domain
     *
     * @command domain:create
     * @alias domain:add
     */
    public function domainCreate($uuid, $environment, $domain)
    {
        $this->say(sprintf('Adding %s to environment %s', $domain, $environment->label));
        $domainAdapter = new Domains($this->cloudapi);
        $response = $domainAdapter->create($environment->uuid, $domain);
        $this->waitForNotification($response);
    }

    /**
     * Remove a domain to an environment.
     *
     * @param string              $uuid
     * @param EnvironmentResponse $environment
     * @param string              $domain
     *
     * @command domain:delete
     * @alias domain:remove
     */
    public function domainDelete($uuid, $environment, $domain)
    {
        if ($this->confirm('Are you sure you want to remove this domain?')) {
            $this->say(sprintf('Removing %s from environment %s', $domain, $environment->label));
            $domainAdapter = new Domains($this->cloudapi);
            $response = $domainAdapter->delete($environment->uuid, $domain);
            $this->waitForNotification($response);
        }
    }

    /**
     * Move a domain from one environment to another.
     *
     * @param string              $uuid
     * @param string              $domain
     * @param EnvironmentResponse $environmentFrom
     * @param EnvironmentResponse $environmentTo
     *
     * @command domain:move
     */
    public function domainMove($uuid, $domain, $environmentFrom, $environmentTo)
    {
        if ($this->confirm(
            sprintf(
                'Are you sure you want to move %s from environment %s to %s?',
                $domain,
                $environmentFrom->label,
                $environmentTo->label
            )
        )) {
            $domainAdapter = new Domains($this->cloudapi);
            $this->say(sprintf('Moving %s from %s to %s', $domain, $environmentFrom->label, $environmentTo->label));

            $deleteResponse = $domainAdapter->delete($environmentFrom->uuid, $domain);
            $this->waitForNotification($deleteResponse);

            $addResponse = $domainAdapter->create($environmentTo->uuid, $domain);
            $this->waitForNotification($addResponse);
        }
    }
}
