<?php

namespace AcquiaCli\Commands;

use Symfony\Component\Console\Helper\Table;

class DomainCommand extends AcquiaCommand
{

    /**
     * Add a domain to an environment.
     *
     * @command domain:add
     */
    public function acquiaAddDomain($site, $environment, $domain)
    {
        $this->say("Adding ${domain} to ${environment} environment");
        $task = $this->cloudapi->addDomain($site, $environment, $domain);
        $this->waitForTask($site, $task);
    }

    /**
     * Remove a domain to an environment.
     *
     * @command domain:remove
     */
    public function acquiaRemoveDomain($site, $environment, $domain)
    {
        if ($this->confirm('Are you sure you want to remove this domain?')) {
            $this->say("Removing ${domain} from ${environment} environment");
            $task = $this->cloudapi->deleteDomain($site, $environment, $domain);
            $this->waitForTask($site, $task);
        }
    }

    /**
     * List all domains.
     *
     * @command domain:move
     */
    public function acquiaMoveDomain($site, $domain, $environmentFrom, $environmentTo)
    {
        if ($this->confirm("Are you sure you want to move ${domain} from ${environmentFrom} to ${environmentTo}?")) {
            $this->say("Moving ${domain} from ${environmentFrom} to ${environmentTo}");
            $task = $this->cloudapi->moveDomain($site, $domain, $environmentFrom, $environmentTo);
            $this->waitForTask($site, $task);
        }
    }
}
