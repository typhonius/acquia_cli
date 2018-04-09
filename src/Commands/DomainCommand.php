<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Response\EnvironmentResponse;

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
        $this->cloudapi->createDomain($environment->uuid, $domain);
        $this->waitForTask($uuid, 'DomainAdded');
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
            $this->cloudapi->deleteDomain($environment->uuid, $domain);
            $this->waitForTask($uuid, 'DomainRemoved');
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
        if ($this->confirm("Are you sure you want to move ${domain} from ${environmentFromLabel} to ${environmentToLabel}?")) {
            $this->say("Moving ${domain} from ${environmentFromLabel} to ${environmentToLabel}");
            $this->cloudapi->deleteDomain($environmentFrom->uuid, $domain);
            $this->waitForTask($uuid, 'DomainRemoved');
            $this->cloudapi->createDomain($environmentTo->uuid, $domain);
            $this->waitForTask($uuid, 'DomainAdded');
        }
    }
}
