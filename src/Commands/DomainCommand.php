<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Response\EnvironmentResponse;
use AcquiaCloudApi\Endpoints\Domains;

/**
 * Class DomainCommand
 * @package AcquiaCli\Commands
 */
class DomainCommand extends AcquiaCommand
{

    /**
     * Add a domain to an environment.
     *
     * @param string              $uuid
     * @param EnvironmentResponse $environment
     * @param string              $domain
     *
     * @command domain:add
     */
    public function acquiaAddDomain($uuid, $environment, $domain)
    {
        $label = $environment->label;
        $this->say("Adding ${domain} to ${label} environment");
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
     * @command domain:remove
     */
    public function acquiaRemoveDomain($uuid, $environment, $domain)
    {
        if ($this->confirm('Are you sure you want to remove this domain?')) {
            $label = $environment->label;
            $this->say("Removing ${domain} from environment ${label}");
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
    public function acquiaMoveDomain($uuid, $domain, $environmentFrom, $environmentTo)
    {
        $environmentFromLabel = $environmentFrom->label;
        $environmentToLabel = $environmentTo->label;
        if ($this->confirm(
            "Are you sure you want to move ${domain} from ${environmentFromLabel} to ${environmentToLabel}?"
        )) {
            $domainAdapter = new Domains($this->cloudapi);
            $this->say("Moving ${domain} from ${environmentFromLabel} to ${environmentToLabel}");

            $deleteResponse = $domainAdapter->delete($environment->uuid, $domain);
            $this->waitForNotification($deleteResponse);

            $addResponse = $domainAdapter->create($environment->uuid, $domain);
            $this->waitForNotification($addResponse);

        }
    }
}
