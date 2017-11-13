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
     * @param string              $environmentUuid
     * @param EnvironmentResponse $environment
     * @param string              $domain
     *
     * @command domain:add
     */
    public function acquiaAddDomain($environmentUuid, $environment, $domain)
    {
        $label = $environment->label;
        $this->say("Adding ${domain} to ${label} environment");
        $this->cloudapi->addDomain($environment->uuid, $domain);
        $this->waitForTask($environmentUuid, 'DomainAdded');
    }

    /**
     * Remove a domain to an environment.
     *
     * @param string              $environmentUuid
     * @param EnvironmentResponse $environment
     * @param string              $domain
     *
     * @command domain:remove
     */
    public function acquiaRemoveDomain($environmentUuid, $environment, $domain)
    {
        if ($this->confirm('Are you sure you want to remove this domain?')) {
            $label = $environment->label;
            $this->say("Removing ${domain} from environment ${label}");
            $this->cloudapi->deleteDomain($environment->uuid, $domain);
            $this->waitForTask($environmentUuid, 'DomainRemoved');
        }
    }

    /**
     * List all domains.
     *
     *
     * @param string $site
     * @param string $domain
     * @param string $environmentFrom
     * @param string $environmentTo
     *
     * @command domain:move
     */
//    public function acquiaMoveDomain($site, $domain, $environmentFrom, $environmentTo)
//    {
//        if ($this->confirm("Are you sure you want to move ${domain} from ${environmentFrom} to ${environmentTo}?")) {
//            $this->say("Moving ${domain} from ${environmentFrom} to ${environmentTo}");
//            $task = $this->cloudapi->moveDomain($site, $domain, $environmentFrom, $environmentTo);
//            $this->waitForTask($site, $task);
//        }
//    }
}
