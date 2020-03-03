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

    protected $domainAdapter;

    public function __construct()
    {
        parent::__construct();

        $this->domainAdapter = new Domains($this->getCloudApi());
    }

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
        $domains = $this->domainAdapter->getAll($environment->uuid);

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
        $domain = $this->domainAdapter->status($environment->uuid, $domain);

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
        $response = $this->domainAdapter->create($environment->uuid, $domain);
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
            $response = $this->domainAdapter->delete($environment->uuid, $domain);
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
            $this->say(sprintf('Moving %s from %s to %s', $domain, $environmentFrom->label, $environmentTo->label));

            $deleteResponse = $this->domainAdapter->delete($environmentFrom->uuid, $domain);
            $this->waitForNotification($deleteResponse);

            $addResponse = $this->domainAdapter->create($environmentTo->uuid, $domain);
            $this->waitForNotification($addResponse);
        }
    }

    /**
     * Clears varnish cache for a specific domain.
     *
     * @param string $uuid
     *
     * @command domain:purge
     */
    public function domainPurge($uuid, $environment, $domain = null)
    {
        if ($environment->name === 'prod' &&
            !$this->confirm("Are you sure you want to purge varnish on the production environment?")) {
            return;
        }

        if (null === $domain) {
            $domains = $this->domainAdapter->getAll($environment->uuid);
            $domainNames = array_map(function ($domain) {
                $this->say(sprintf('Purging domain: %s', $domain->hostname));
                return $domain->hostname;
            }, $domains->getArrayCopy());
        } else {
            $this->say(sprintf('Purging domain: %s', $domain));
            $domainNames = [$domain];
        }

        $response = $this->domainAdapter->purge($environment->uuid, $domainNames);
        $this->waitForNotification($response);
    }
}
